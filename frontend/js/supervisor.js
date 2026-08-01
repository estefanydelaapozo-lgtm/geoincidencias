// js/supervisor.js — lógica del Panel de Supervisor
// Reutiliza los endpoints ya existentes de incidencias, estado y comentarios.

let supPaginaActual = 1;
const SUP_POR_PAG = 10;
let mapaSupervisor, marcadorSupervisor;
let supIncidenciaActual = null;
let supLatActual = null, supLngActual = null;

// Carga una imagen protegida por token (ej. foto de una incidencia) y la
// asigna a un <img>. Definida aquí mismo (y no solo en auth-guard.js) para
// que este archivo no dependa de que otro esté igual de actualizado.
if (typeof cargarImagenProtegida === 'undefined') {
  var cargarImagenProtegida = async function (url, imgEl) {
    const res = await fetchAPI(url);
    if (!res.ok) throw new Error('No se pudo cargar la imagen');
    const blob = await res.blob();
    imgEl.src = URL.createObjectURL(blob);
  };
}

const SUP_COLOR_ESTADO = {
  'Registrada':  { bg:'rgba(255,59,107,.15)',  color:'#FF3B6B' },
  'En proceso':  { bg:'rgba(245,158,11,.15)', color:'#F59E0B' },
  'Resuelta':    { bg:'rgba(0,229,160,.15)',  color:'#00E5A0' },
  'Cerrada':     { bg:'rgba(232,244,253,.06)', color:'#94a3b8' },
};

// Etiqueta amigable solo para mostrar en pantalla (el valor real del
// estado en el sistema no cambia, así no se rompe la lógica existente).
const SUP_ETIQUETA_ESTADO = { 'Registrada': 'Pendiente' };
function etiquetaEstado(nombre) { return SUP_ETIQUETA_ESTADO[nombre] || nombre; }

function badgeEstadoSup(nombreEstado) {
  const c = SUP_COLOR_ESTADO[nombreEstado] || { bg:'rgba(232,244,253,.06)', color:'#94a3b8' };
  return `<span class="badge" style="background:${c.bg};color:${c.color};border:1px solid ${c.color}30;">${etiquetaEstado(nombreEstado)}</span>`;
}

const SUP_APROBACION = {
  pendiente_revision: { texto: '⏳ Pendiente', color: '#F59E0B' },
  aprobada: { texto: '✓ Aprobada', color: '#00E5A0' },
  rechazada: { texto: '✕ Rechazada', color: '#FF3B6B' },
};
function badgeAprobacionSup(estadoAprobacion) {
  const c = SUP_APROBACION[estadoAprobacion] || { texto: estadoAprobacion, color: '#94a3b8' };
  return `<span class="badge" style="background:${c.color}15;color:${c.color};border:1px solid ${c.color}30;">${c.texto}</span>`;
}

function debounceSup(fn, ms) {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

function escapeHtmlSup(texto) {
  return String(texto).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
}

function cerrarModal(id) { document.getElementById(id).classList.remove('open'); }

function cargarMiniaturasProtegidas() {
  document.querySelectorAll('.foto-thumb-protegida[data-foto-url]').forEach(img => {
    const url = img.getAttribute('data-foto-url');
    img.removeAttribute('data-foto-url');
    cargarImagenProtegida(url, img).catch(() => { img.style.display = 'none'; });
  });
}

async function poblarFiltroEstadoSupervisor() {
  try {
    const estados = await (await fetchAPI(`${API}/catalogos/estados`)).json();
    const sel = document.getElementById('supFiltroEstado');
    estados.forEach(e => sel.insertAdjacentHTML('beforeend', `<option value="${e.nombre}">${etiquetaEstado(e.nombre)}</option>`));
  } catch (e) {}
}

async function cargarSupervisorIncidencias(pag = 1) {
  supPaginaActual = pag;
  const esGestor = ['admin', 'supervisor'].includes(getUsuario()?.rol);
  const params = new URLSearchParams({ pagina: pag, por_pagina: SUP_POR_PAG, todas: esGestor ? 1 : 0 });
  const buscar = document.getElementById('supBuscar').value;
  const estado = document.getElementById('supFiltroEstado').value;
  const aprobacion = document.getElementById('supFiltroAprobacion').value;
  if (buscar) params.append('buscar', buscar);
  if (estado) params.append('estado', estado);
  if (aprobacion) params.append('estado_aprobacion', aprobacion);

  document.getElementById('tbodySupervisor').innerHTML =
    `<tr><td colspan="9" style="text-align:center;padding:50px;color:var(--text-muted);"><span class="spin">⟳</span> Cargando…</td></tr>`;

  try {
    const res = await fetchAPI(`${API}/incidencias?${params}`);
    const datos = await res.json();
    if (!res.ok) throw new Error(datos.mensaje || `Error ${res.status} del servidor.`);
    const incidencias = datos.data || datos;
    if (!Array.isArray(incidencias)) throw new Error(datos.mensaje || 'Respuesta inesperada del servidor.');
    const total = datos.total || incidencias.length;
    const totalPags = Math.ceil(total / SUP_POR_PAG);

    document.getElementById('supTotalLabel').textContent = `${total} resultado${total !== 1 ? 's' : ''}`;
    document.getElementById('supPaginaInfo').textContent = `Página ${pag} de ${totalPags || 1}`;

    const html = incidencias.map(inc => `
      <tr>
        <td class="mono" style="color:var(--text-muted);font-size:.78rem;">#${inc.id_incidencia}</td>
        <td>
          ${inc.foto_url
            ? `<img data-foto-url="${inc.foto_url}" class="foto-thumb-protegida" alt="Foto" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--border-light);"/>`
            : `<span style="color:var(--text-muted);font-size:.75rem;">—</span>`}
        </td>
        <td>
          <div style="font-weight:600;color:var(--text-primary);font-size:.875rem;">${escapeHtmlSup(inc.titulo)}</div>
          <div style="font-size:.75rem;color:var(--text-muted);">${escapeHtmlSup(inc.tipo || '')}${inc.subtipo ? ' / ' + escapeHtmlSup(inc.subtipo) : ''}</div>
        </td>
        <td style="font-size:.82rem;">${escapeHtmlSup(inc.zona || '')}</td>
        <td style="font-size:.82rem;">${escapeHtmlSup(inc.prioridad || '')}</td>
        <td>${badgeEstadoSup(inc.estado)}</td>
        <td>${badgeAprobacionSup(inc.estado_aprobacion)}</td>
        <td class="mono" style="font-size:.78rem;">${inc.fecha_registro ? new Date(inc.fecha_registro).toLocaleString('es-EC') : '—'}</td>
        <td><button class="btn btn-ghost btn-sm" onclick="abrirDetalleSupervisor(${inc.id_incidencia})">🔍 Ver</button></td>
      </tr>`).join('');

    document.getElementById('tbodySupervisor').innerHTML = html ||
      `<tr><td colspan="9" style="text-align:center;padding:50px;color:var(--text-muted);">No hay incidencias con esos filtros</td></tr>`;
    cargarMiniaturasProtegidas();

    let pags = '';
    if (totalPags > 1) {
      pags += `<button class="page-btn" ${pag===1?'disabled':''} onclick="cargarSupervisorIncidencias(${pag-1})">‹</button>`;
      const start = Math.max(1, pag-2), end = Math.min(totalPags, pag+2);
      for (let i = start; i <= end; i++) {
        pags += `<button class="page-btn ${i===pag?'active':''}" onclick="cargarSupervisorIncidencias(${i})">${i}</button>`;
      }
      pags += `<button class="page-btn" ${pag===totalPags?'disabled':''} onclick="cargarSupervisorIncidencias(${pag+1})">›</button>`;
    }
    document.getElementById('supPaginacion').innerHTML = pags;
  } catch (e) {
    console.error('Fallo al cargar incidencias (supervisor):', e);
    const detalle = (e && e.message) ? e.message : (e ? JSON.stringify(e) : 'error desconocido');
    document.getElementById('tbodySupervisor').innerHTML =
      `<tr><td colspan="9" style="text-align:center;padding:50px;color:var(--coral);">Error al cargar incidencias: ${escapeHtmlSup(detalle)}</td></tr>`;
  }
}

function limpiarFiltrosSupervisor() {
  document.getElementById('supBuscar').value = '';
  document.getElementById('supFiltroEstado').value = '';
  document.getElementById('supFiltroAprobacion').value = '';
  cargarSupervisorIncidencias(1);
}

async function abrirDetalleSupervisor(id) {
  try {
    const inc = await (await fetchAPI(`${API}/incidencias/${id}`)).json();
    supIncidenciaActual = inc;
    document.getElementById('sup_id').value = inc.id_incidencia;
    document.getElementById('sup_titulo').textContent = `#${inc.id_incidencia} — ${inc.titulo}`;
    document.getElementById('sup_meta').textContent =
      `${inc.tipo || ''}${inc.subtipo ? ' / ' + inc.subtipo : ''} · ${inc.zona || ''} · Registrada: ${inc.fecha_registro ? new Date(inc.fecha_registro).toLocaleString('es-EC') : '—'}`;
    document.getElementById('sup_badgeEstado').innerHTML = badgeEstadoSup(inc.estado);
    document.getElementById('sup_descripcion').textContent = inc.descripcion || 'Sin descripción adicional.';

    // Foto
    const imgEl = document.getElementById('sup_foto');
    const sinFotoEl = document.getElementById('sup_sinFoto');
    if (inc.foto_url) {
      imgEl.style.display = '';
      sinFotoEl.style.display = 'none';
      cargarImagenProtegida(inc.foto_url, imgEl)
        .then(() => { imgEl.onclick = () => window.open(imgEl.src, '_blank'); })
        .catch(() => { imgEl.style.display = 'none'; sinFotoEl.style.display = ''; });
    } else {
      imgEl.style.display = 'none';
      sinFotoEl.style.display = '';
    }

    // Coordenadas y dirección (editable: clic en el mapa corrige la ubicación)
    supLatActual = inc.latitud ? parseFloat(inc.latitud) : null;
    supLngActual = inc.longitud ? parseFloat(inc.longitud) : null;
    document.getElementById('sup_direccion').value = inc.direccion_texto || '';
    document.getElementById('sup_coords').textContent =
      (supLatActual && supLngActual) ? `Lat: ${supLatActual}, Lng: ${supLngActual}` : 'Sin coordenadas registradas (haz clic en el mapa para asignarlas)';
    const linkComoLlegarSup = document.getElementById('sup_comoLlegar');
    if (inc.como_llegar_url) {
      linkComoLlegarSup.href = inc.como_llegar_url;
      linkComoLlegarSup.style.display = 'inline-flex';
    } else {
      linkComoLlegarSup.style.display = 'none';
    }

    // Aprobación: badge, plazo, historial y acciones
    document.getElementById('sup_badgeAprobacion').innerHTML = badgeAprobacionSup(inc.estado_aprobacion) +
      (inc.aprobacion_automatica ? ' <span style="font-size:.72rem;color:var(--text-muted);">(aprobación automática por vencimiento de 24h)</span>' : '');

    const plazoEl = document.getElementById('sup_plazoAviso');
    if (inc.estado_aprobacion === 'pendiente_revision' && inc.fecha_limite_accion) {
      const limite = new Date(inc.fecha_limite_accion);
      plazoEl.textContent = inc.ventana_accion_activa
        ? `Puede eliminarse o rechazarse hasta: ${limite.toLocaleString('es-EC')}. Vencido ese plazo se aprobará automáticamente.`
        : 'El plazo de 24 horas para eliminar o rechazar ya venció; la incidencia será aprobada automáticamente en breve.';
    } else {
      plazoEl.textContent = '';
    }

    const historialSup = inc.historial_aprobacion || [];
    document.getElementById('sup_historialAprobacion').innerHTML = historialSup.length
      ? historialSup.map(h => `<div>• <strong>${escapeHtmlSup(h.accion)}</strong> por ${escapeHtmlSup(h.usuario)} — ${escapeHtmlSup(h.fecha || '')}${h.motivo ? `: ${escapeHtmlSup(h.motivo)}` : ''}</div>`).join('')
      : '<span style="color:var(--text-muted);">Aún no hay historial de aprobación.</span>';

    let acciones = '';
    if (inc.puede_aprobar) acciones += `<button class="btn btn-ghost btn-sm" style="color:var(--green);border-color:rgba(0,229,160,.25);" onclick="abrirAprobarSupervisor(${inc.id_incidencia})">✓ Aprobar</button>`;
    if (inc.puede_rechazar) acciones += `<button class="btn btn-coral btn-sm" onclick="abrirRechazarSupervisor(${inc.id_incidencia})">✕ Rechazar</button>`;
    if (inc.puede_eliminar) acciones += `<button class="btn btn-ghost btn-sm" style="color:var(--coral);" onclick="abrirEliminarSupervisor(${inc.id_incidencia})">🗑 Eliminar</button>`;
    if (inc.estado_aprobacion === 'aprobada' && ['Resuelta','Cerrada','En verificación'].includes(inc.estado) && getUsuario()?.rol === 'supervisor') {
      acciones += `<button class="btn btn-ghost btn-sm" style="color:var(--amber);" onclick="abrirReabrirSupervisor(${inc.id_incidencia})">↺ Reabrir</button>`;
    }
    document.getElementById('sup_accionesAprobacion').innerHTML = acciones;

    // Estado (solo se listan las transiciones válidas que acepta el backend)
    document.getElementById('sup_bloqueCambiarEstado').style.display = getUsuario()?.rol === 'supervisor' ? '' : 'none';
    const estados = await (await fetchAPI(`${API}/catalogos/estados`)).json();
    const permitidos = inc.siguientes_estados || [];
    const opciones = estados.filter(e => permitidos.includes(e.nombre));
    const selEstado = document.getElementById('sup_estado');
    selEstado.innerHTML = opciones.length
      ? `<option value="">— Selecciona el siguiente estado —</option>` + opciones.map(e => `<option value="${e.id}">${etiquetaEstado(e.nombre)}</option>`).join('')
      : `<option value="">Sin transiciones disponibles</option>`;
    const noAprobadaAun = inc.estado_aprobacion !== 'aprobada';
    const sinTransiciones = opciones.length === 0;
    selEstado.disabled = noAprobadaAun || sinTransiciones;
    document.getElementById('sup_btnActualizarEstado').disabled = noAprobadaAun || sinTransiciones;
    document.getElementById('sup_avisoEstadoBloqueado').textContent = noAprobadaAun
      ? 'El flujo de atención se habilita cuando la incidencia quede aprobada.'
      : (sinTransiciones ? 'Esta incidencia ya está en su estado final (Cerrada) o no tiene más transiciones.' : '');

    document.getElementById('modalSupervisor').classList.add('open');
    cargarObservacionesSupervisor(id);

    // Mapa
    setTimeout(async () => {
      await cargarLeaflet();
      const lat = inc.latitud || -2.9001, lng = inc.longitud || -79.0059;
      if (!mapaSupervisor) {
        mapaSupervisor = L.map('mapaSupervisor').setView([lat, lng], inc.latitud ? 15 : 13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(mapaSupervisor);
        mapaSupervisor.on('click', e => {
          supLatActual = e.latlng.lat;
          supLngActual = e.latlng.lng;
          if (marcadorSupervisor) mapaSupervisor.removeLayer(marcadorSupervisor);
          marcadorSupervisor = L.marker([supLatActual, supLngActual]).addTo(mapaSupervisor);
          document.getElementById('sup_coords').textContent = `Lat: ${supLatActual.toFixed(6)}, Lng: ${supLngActual.toFixed(6)} (sin guardar)`;
        });
      } else {
        mapaSupervisor.setView([lat, lng], inc.latitud ? 15 : 13);
      }
      if (marcadorSupervisor) mapaSupervisor.removeLayer(marcadorSupervisor);
      if (inc.latitud && inc.longitud) {
        marcadorSupervisor = L.marker([lat, lng]).addTo(mapaSupervisor).bindPopup(escapeHtmlSup(inc.titulo));
      }
      mapaSupervisor.invalidateSize();
      setTimeout(() => mapaSupervisor.invalidateSize(), 300);
    }, 250);
  } catch (e) {
    mostrarToast('No se pudo cargar el detalle de la incidencia.', 'danger');
  }
}

function cerrarModalSupervisor() {
  document.getElementById('modalSupervisor').classList.remove('open');
  supIncidenciaActual = null;
}

async function cambiarEstadoSupervisor() {
  const id = document.getElementById('sup_id').value;
  const valorEstado = document.getElementById('sup_estado').value;
  if (!valorEstado) return mostrarToast('Selecciona el siguiente estado.', 'warning');
  const idEstado = parseInt(valorEstado);
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/estado`, {
      method: 'PUT',
      body: JSON.stringify({ id_estado: idEstado, comentario: 'Actualización de estado desde el panel de Supervisor.' })
    });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Estado actualizado.', 'success');
      cargarSupervisorIncidencias(supPaginaActual);
      cerrarModalSupervisor();
    } else {
      mostrarToast(d.mensaje || d.message || 'No se pudo actualizar el estado.', 'danger');
    }
  } catch (e) {
    mostrarToast('Error de conexión al actualizar el estado.', 'danger');
  }
}

// ── Observaciones del supervisor (reutiliza el sistema de comentarios) ──
async function cargarObservacionesSupervisor(id) {
  const contenedor = document.getElementById('sup_comentarios');
  contenedor.innerHTML = '<span style="color:var(--text-muted);font-size:.8rem;">Cargando observaciones…</span>';
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/comentarios`);
    const data = await res.json();
    const comentarios = data.datos || [];
    contenedor.innerHTML = comentarios.length
      ? comentarios.map(c => `<div style="padding:9px 11px;border:1px solid var(--border-light);border-radius:9px;background:rgba(255,255,255,.02);">
          <div style="font-size:.72rem;color:var(--cyan);font-weight:600;">${escapeHtmlSup(c.autor || 'Sistema')} · ${escapeHtmlSup(c.fecha || '')}</div>
          <div style="font-size:.82rem;color:var(--text-secondary);margin-top:3px;white-space:pre-wrap;">${escapeHtmlSup(c.comentario || '')}</div>
        </div>`).join('')
      : '<span style="color:var(--text-muted);font-size:.8rem;">Aún no hay observaciones.</span>';
  } catch (e) {
    contenedor.innerHTML = '<span style="color:var(--coral);font-size:.8rem;">No se pudieron cargar las observaciones.</span>';
  }
}

async function agregarObservacionSupervisor() {
  const id = document.getElementById('sup_id').value;
  const input = document.getElementById('sup_nuevaObs');
  const comentario = (input?.value || '').trim();
  if (comentario.length < 3) {
    mostrarToast('La observación debe tener al menos 3 caracteres.', 'warning');
    return;
  }
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/comentarios`, {
      method: 'POST',
      body: JSON.stringify({ comentario })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.mensaje || data.message || 'No se pudo agregar la observación.');
    input.value = '';
    mostrarToast(data.mensaje || 'Observación agregada.', 'success');
    cargarObservacionesSupervisor(id);
  } catch (e) {
    mostrarToast(e.message || 'Error al agregar la observación.', 'danger');
  }
}

async function guardarUbicacionSupervisor() {
  const id = document.getElementById('sup_id').value;
  if (!supLatActual || !supLngActual) return mostrarToast('Haz clic en el mapa para marcar la ubicación.', 'warning');
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}`, {
      method: 'PUT',
      body: JSON.stringify({
        latitud: supLatActual,
        longitud: supLngActual,
        direccion_texto: document.getElementById('sup_direccion').value.trim() || null,
      })
    });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Ubicación actualizada.', 'success');
      cargarSupervisorIncidencias(supPaginaActual);
      cerrarModalSupervisor();
    } else mostrarToast(d.mensaje || d.message || 'No se pudo actualizar la ubicación.', 'danger');
  } catch (e) { mostrarToast('Error de conexión al guardar la ubicación.', 'danger'); }
}

poblarFiltroEstadoSupervisor();
cargarSupervisorIncidencias(1);
cargarCentroMonitoreo();

async function cargarCentroMonitoreo() {
  try {
    const r = await fetchAPI(`${API}/dashboard/resumen`);
    const d = await r.json();
    document.getElementById('mon_pendientes').textContent = d.pendientes_aprobacion ?? 0;
    document.getElementById('mon_proceso').textContent = d.en_proceso ?? 0;
    document.getElementById('mon_alta').textContent = d.alta_prioridad ?? 0;
  } catch (e) {}

  try {
    const rEstados = await fetchAPI(`${API}/catalogos/estados`);
    const estados = await rEstados.json();
    document.getElementById('supMiniLeyenda').innerHTML = (estados || [])
      .map(e => `<span class="map-leyenda-item"><i style="background:${e.color || '#94a3b8'};"></i>${e.nombre}</span>`).join('');
  } catch (e) {}

  try {
    await cargarLeaflet();
    const cont = document.getElementById('supMiniMapa');
    cont.classList.remove('map-skeleton');
    const miniMapa = L.map(cont, { zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false }).setView([-2.9001, -79.0059], 12);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(miniMapa);
    const rMapa = await fetchAPI(`${API}/incidencias/mapa`);
    const datos = await rMapa.json();
    const puntos = [];
    (datos || []).forEach(inc => {
      if (!inc.latitud || !inc.longitud) return;
      const color = inc.color_estado || '#6D28D9';
      L.circleMarker([inc.latitud, inc.longitud], { radius: 5, color, fillColor: color, fillOpacity: .85, weight: 1 }).addTo(miniMapa);
      puntos.push([inc.latitud, inc.longitud]);
    });
    if (puntos.length) miniMapa.fitBounds(puntos, { padding: [16, 16], maxZoom: 14 });
  } catch (e) {}
}

// ── Aprobación de incidencias (Supervisor y Administrador tienen los mismos permisos) ──
let supIdGestion = null;

// Aprobar es una sola acción sin comentario obligatorio: se confirma y se envía directo.
async function abrirAprobarSupervisor(id) {
  const ok = await confirmarAccion({ titulo: 'Aprobar incidencia', mensaje: '¿Confirmas que esta incidencia es válida y quieres aprobarla?', textoBoton: 'Sí, aprobar' });
  if (!ok) return;
  supIdGestion = id;
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/aprobar`, { method: 'PUT', body: JSON.stringify({}) });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Incidencia aprobada.', 'success');
      cerrarModalSupervisor();
      cargarSupervisorIncidencias(supPaginaActual);
    } else mostrarToast(d.mensaje || d.message || 'No se pudo aprobar la incidencia.', 'danger');
  } catch (e) { mostrarToast('Error de conexión al aprobar.', 'danger'); }
}

// Se conserva por compatibilidad si algún botón antiguo aún la referencia.
async function confirmarAprobarSupervisor() { await abrirAprobarSupervisor(supIdGestion); }

async function abrirReabrirSupervisor(id) {
  const motivo = await pedirTexto({ titulo: 'Reabrir incidencia', mensaje: '¿Por qué reabres esta incidencia?', placeholder: 'Ej: la evidencia no coincide con el problema reportado…', minLen: 5, textoBoton: 'Reabrir' });
  if (motivo === null) return;
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/reabrir`, { method: 'PUT', body: JSON.stringify({ motivo: motivo.trim() }) });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Incidencia reabierta.', 'success');
      cerrarModalSupervisor();
      cargarSupervisorIncidencias(supPaginaActual);
    } else mostrarToast(d.mensaje || d.message || 'No se pudo reabrir la incidencia.', 'danger');
  } catch (e) { mostrarToast('Error de conexión al reabrir.', 'danger'); }
}

function abrirRechazarSupervisor(id) {
  supIdGestion = id;
  document.getElementById('motivoRechazarSup').value = '';
  document.getElementById('modalRechazarSup').classList.add('open');
}

async function confirmarRechazarSupervisor() {
  const motivo = document.getElementById('motivoRechazarSup').value.trim();
  if (motivo.length < 5) return mostrarToast('El motivo del rechazo es obligatorio.', 'warning');
  try {
    const res = await fetchAPI(`${API}/incidencias/${supIdGestion}/rechazar`, { method: 'PUT', body: JSON.stringify({ motivo }) });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Incidencia rechazada.', 'success');
      cerrarModal('modalRechazarSup');
      cerrarModalSupervisor();
      cargarSupervisorIncidencias(supPaginaActual);
    } else mostrarToast(d.mensaje || d.message || 'No se pudo rechazar la incidencia.', 'danger');
  } catch (e) { mostrarToast('Error de conexión al rechazar.', 'danger'); }
}

function abrirEliminarSupervisor(id) {
  supIdGestion = id;
  document.getElementById('modalEliminarSup').classList.add('open');
}

async function confirmarEliminarSupervisor() {
  try {
    const res = await fetchAPI(`${API}/incidencias/${supIdGestion}`, { method: 'DELETE' });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Incidencia eliminada.', 'success');
      cerrarModal('modalEliminarSup');
      cerrarModalSupervisor();
      cargarSupervisorIncidencias(supPaginaActual);
    } else mostrarToast(d.mensaje || d.message || 'No se pudo eliminar la incidencia.', 'danger');
  } catch (e) { mostrarToast('Error de conexión al eliminar.', 'danger'); }
}
