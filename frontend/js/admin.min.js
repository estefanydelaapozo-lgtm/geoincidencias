// js/admin.js
let rechazarApoyoId      = null;

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

const COLOR_PRIO = { 'Crítica':'#FF3B6B','Alta':'#F97316','Media':'#00D4FF','Baja':'#00E5A0' };

function badgePrio(p) {
  const c = COLOR_PRIO[p] || '#94a3b8';
  return `<span class="badge" style="background:${c}15;color:${c};border:1px solid ${c}30;">${p}</span>`;
}

function cambiarTab(tab) {
  const showInc = tab === 'incidencias';
  document.getElementById('panelIncidencias').style.display = showInc ? '' : 'none';
  document.getElementById('panelApoyos').style.display       = showInc ? 'none' : '';
  document.getElementById('tabIncBtn').className = showInc ? 'btn btn-primary' : 'btn btn-ghost';
  document.getElementById('tabApBtn').className  = showInc ? 'btn btn-ghost' : 'btn btn-primary';
  if (!showInc) cargarPendientesApoyos();
}

function cerrarModal(id) {
  document.getElementById(id).classList.remove('open');
}

// ── Incidencias pendientes ──
async function cargarPendientesIncidencias() {
  try {
    const r    = await fetchAPI(`${API}/incidencias/pendientes-aprobacion`);
    const datos = await r.json();
    document.getElementById('cntPendIncidencias').textContent = datos.length;

    const html = datos.map(inc => {
      const limite = inc.fecha_limite_accion ? new Date(inc.fecha_limite_accion).toLocaleString('es-EC') : '—';
      return `
      <tr>
        <td>
          <div style="font-weight:600;color:var(--text-primary);font-size:.875rem;">${inc.titulo}</div>
          <div style="font-size:.75rem;color:var(--text-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            ${inc.descripcion ? inc.descripcion.substring(0,70)+'…' : '—'}
          </div>
          <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;">📍 ${inc.direccion_texto || 'Sin dirección de referencia'}</div>
        </td>
        <td style="font-size:.82rem;">${inc.tipo}${inc.subtipo ? `<span style="color:var(--text-muted);"> / ${inc.subtipo}</span>` : ''}</td>
        <td style="font-size:.82rem;">${inc.zona}</td>
        <td>${badgePrio(inc.prioridad)}</td>
        <td style="font-size:.82rem;">${inc.creado_por || inc.reportante_nombre || '—'}</td>
        <td class="mono" style="font-size:.72rem;">${new Date(inc.fecha_ocurrencia).toLocaleDateString('es-EC')}<br><span style="color:var(--text-muted);">Vence: ${limite}</span></td>
        <td>
          <div class="flex items-center" style="gap:6px;flex-wrap:wrap;">
            <button class="btn btn-ghost btn-sm" title="Ver detalle, mapa y cómo llegar" onclick="abrirDetalleIncidenciaAdmin(${inc.id_incidencia})">🔍 Ver</button>
            ${inc.puede_aprobar ? `<button class="btn btn-ghost btn-sm" style="color:var(--green);border-color:rgba(0,229,160,.25);" onclick="aprobarIncidenciaConfirm(${inc.id_incidencia})">✓ Aprobar</button>` : ''}
            ${inc.puede_rechazar ? `<button class="btn btn-coral btn-sm" onclick="abrirRechazarInc(${inc.id_incidencia})">✕ Rechazar</button>` : ''}
            ${inc.puede_eliminar ? `<button class="btn btn-ghost btn-sm" style="color:var(--coral);" onclick="abrirEliminarInc(${inc.id_incidencia})">🗑</button>` : ''}
          </div>
        </td>
      </tr>`;
    }).join('');

    document.getElementById('tbodyPendInc').innerHTML = html ||
      `<tr><td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">No hay incidencias pendientes 🎉</td></tr>`;
  } catch(e) {
    document.getElementById('tbodyPendInc').innerHTML =
      `<tr><td colspan="7" style="text-align:center;padding:50px;color:var(--coral);">Error al cargar</td></tr>`;
  }
}

let idGestionInc = null;

// Aprobar es una sola acción sin comentario obligatorio: se confirma y se envía directo.
async function aprobarIncidenciaConfirm(id) {
  const idInc = id || idGestionInc;
  const ok = await confirmarAccion({ titulo: 'Aprobar incidencia', mensaje: '¿Confirmas que esta incidencia es válida y quieres aprobarla?', textoBoton: 'Sí, aprobar' });
  if (!ok) return;
  try {
    const res = await fetchAPI(`${API}/incidencias/${idInc}/aprobar`, { method:'PUT', body: JSON.stringify({}) });
    const d   = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Incidencia aprobada.', 'success');
      cerrarModal('modalDetalleInc');
      cargarPendientesIncidencias();
    } else mostrarToast(d.mensaje || d.message || 'Error.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

function abrirEliminarInc(id) {
  idGestionInc = id;
  document.getElementById('modalEliminarInc').classList.add('open');
}

async function eliminarIncidenciaConfirm() {
  try {
    const res = await fetchAPI(`${API}/incidencias/${idGestionInc}`, { method:'DELETE' });
    const d   = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Incidencia eliminada.', 'success');
      cerrarModal('modalEliminarInc');
      cerrarModal('modalDetalleInc');
      cargarPendientesIncidencias();
    } else mostrarToast(d.mensaje || d.message || 'Error.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

// ── Detalle: ubicación, mapa, cómo llegar e historial de aprobación ──
let mapaDetalleInc, marcadorDetalleInc;
let detLatActual = null, detLngActual = null;
const BADGES_APROBACION_ADMIN = {
  pendiente_revision: { texto: '⏳ Pendiente de revisión', color: '#F59E0B' },
  aprobada: { texto: '✓ Aprobada', color: '#00E5A0' },
  rechazada: { texto: '✕ Rechazada', color: '#FF3B6B' },
};

function escapeHtmlAdmin(texto) {
  return String(texto).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
}

async function abrirDetalleIncidenciaAdmin(id) {
  try {
    const inc = await (await fetchAPI(`${API}/incidencias/${id}`)).json();
    idGestionInc = id;

    document.getElementById('det_titulo').textContent = `#${inc.id_incidencia} — ${inc.titulo}`;
    document.getElementById('det_meta').textContent =
      `${inc.tipo || ''}${inc.subtipo ? ' / ' + inc.subtipo : ''} · ${inc.zona || ''} · Registrada: ${inc.fecha_registro ? new Date(inc.fecha_registro).toLocaleString('es-EC') : '—'}`;
    document.getElementById('det_descripcion').textContent = inc.descripcion || 'Sin descripción adicional.';

    const bAprob = BADGES_APROBACION_ADMIN[inc.estado_aprobacion] || { texto: inc.estado_aprobacion, color: '#94a3b8' };
    document.getElementById('det_badgeAprobacion').innerHTML =
      `<span class="badge" style="background:${bAprob.color}15;color:${bAprob.color};border:1px solid ${bAprob.color}30;">${bAprob.texto}</span>`;

    const imgEl = document.getElementById('det_foto');
    const sinFotoEl = document.getElementById('det_sinFoto');
    if (inc.foto_url) {
      imgEl.style.display = '';
      sinFotoEl.style.display = 'none';
      cargarImagenProtegida(inc.foto_url, imgEl)
        .then(() => { imgEl.onclick = () => window.open(imgEl.src, '_blank'); })
        .catch(() => { imgEl.style.display = 'none'; sinFotoEl.style.display = ''; });
    } else { imgEl.style.display = 'none'; sinFotoEl.style.display = ''; }

    document.getElementById('det_direccion').value = inc.direccion_texto || '';
    detLatActual = inc.latitud ? parseFloat(inc.latitud) : null;
    detLngActual = inc.longitud ? parseFloat(inc.longitud) : null;
    document.getElementById('det_coords').textContent =
      (detLatActual && detLngActual) ? `Lat: ${detLatActual}, Lng: ${detLngActual}` : 'Sin coordenadas registradas (haz clic en el mapa para asignarlas)';
    const linkComoLlegarDet = document.getElementById('det_comoLlegar');
    if (inc.como_llegar_url) { linkComoLlegarDet.href = inc.como_llegar_url; linkComoLlegarDet.style.display = 'inline-flex'; }
    else linkComoLlegarDet.style.display = 'none';

    const plazoEl = document.getElementById('det_plazoAviso');
    if (inc.estado_aprobacion === 'pendiente_revision' && inc.fecha_limite_accion) {
      const limite = new Date(inc.fecha_limite_accion);
      plazoEl.textContent = inc.ventana_accion_activa
        ? `Puede eliminarse o rechazarse hasta: ${limite.toLocaleString('es-EC')}. Vencido ese plazo se aprobará automáticamente.`
        : 'El plazo de 24 horas ya venció; la incidencia será aprobada automáticamente en breve.';
    } else plazoEl.textContent = '';

    const historial = inc.historial_aprobacion || [];
    document.getElementById('det_historialAprobacion').innerHTML = historial.length
      ? historial.map(h => `<div>• <strong>${escapeHtmlAdmin(h.accion)}</strong> por ${escapeHtmlAdmin(h.usuario)} — ${escapeHtmlAdmin(h.fecha || '')}${h.motivo ? `: ${escapeHtmlAdmin(h.motivo)}` : ''}</div>`).join('')
      : '<span style="color:var(--text-muted);">Aún no hay historial de aprobación.</span>';

    let acciones = '';
    if (inc.puede_aprobar) acciones += `<button class="btn btn-ghost btn-sm" style="color:var(--green);border-color:rgba(0,229,160,.25);" onclick="aprobarIncidenciaConfirm(${inc.id_incidencia})">✓ Aprobar</button>`;
    if (inc.puede_rechazar) acciones += `<button class="btn btn-coral btn-sm" onclick="abrirRechazarInc(${inc.id_incidencia})">✕ Rechazar</button>`;
    if (inc.puede_eliminar) acciones += `<button class="btn btn-ghost btn-sm" style="color:var(--coral);" onclick="abrirEliminarInc(${inc.id_incidencia})">🗑 Eliminar</button>`;
    document.getElementById('det_acciones').innerHTML = acciones;

    document.getElementById('modalDetalleInc').classList.add('open');

    setTimeout(async () => {
      await cargarLeaflet();
      const lat = inc.latitud || -2.9001, lng = inc.longitud || -79.0059;
      if (!mapaDetalleInc) {
        mapaDetalleInc = L.map('mapaDetalleInc').setView([lat, lng], inc.latitud ? 15 : 13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(mapaDetalleInc);
        mapaDetalleInc.on('click', e => {
          detLatActual = e.latlng.lat;
          detLngActual = e.latlng.lng;
          if (marcadorDetalleInc) mapaDetalleInc.removeLayer(marcadorDetalleInc);
          marcadorDetalleInc = L.marker([detLatActual, detLngActual]).addTo(mapaDetalleInc);
          document.getElementById('det_coords').textContent = `Lat: ${detLatActual.toFixed(6)}, Lng: ${detLngActual.toFixed(6)} (sin guardar)`;
        });
      } else {
        mapaDetalleInc.setView([lat, lng], inc.latitud ? 15 : 13);
      }
      if (marcadorDetalleInc) mapaDetalleInc.removeLayer(marcadorDetalleInc);
      if (inc.latitud && inc.longitud) {
        marcadorDetalleInc = L.marker([lat, lng]).addTo(mapaDetalleInc).bindPopup(escapeHtmlAdmin(inc.titulo));
      }
      mapaDetalleInc.invalidateSize();
      setTimeout(() => mapaDetalleInc.invalidateSize(), 300);
    }, 250);
  } catch (e) {
    mostrarToast('No se pudo cargar el detalle de la incidencia.', 'danger');
  }
}

async function guardarUbicacionInc() {
  if (!detLatActual || !detLngActual) return mostrarToast('Haz clic en el mapa para marcar la ubicación.', 'warning');
  try {
    const res = await fetchAPI(`${API}/incidencias/${idGestionInc}`, {
      method: 'PUT',
      body: JSON.stringify({
        latitud: detLatActual,
        longitud: detLngActual,
        direccion_texto: document.getElementById('det_direccion').value.trim() || null,
      })
    });
    const d = await res.json();
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje || 'Ubicación actualizada.', 'success');
      cargarPendientesIncidencias();
      cerrarModal('modalDetalleInc');
    } else mostrarToast(d.mensaje || d.message || 'No se pudo actualizar la ubicación.', 'danger');
  } catch (e) { mostrarToast('Error de conexión al guardar la ubicación.', 'danger'); }
}

function abrirRechazarInc(id) {
  idGestionInc = id;
  document.getElementById('motivoRechazarInc').value = '';
  document.getElementById('modalRechazarInc').classList.add('open');
}

async function rechazarIncidenciaConfirm() {
  const motivo = document.getElementById('motivoRechazarInc').value.trim();
  if (!motivo) return mostrarToast('El motivo es obligatorio.', 'warning');
  try {
    const res = await fetchAPI(`${API}/incidencias/${idGestionInc}/rechazar`, {
      method:'PUT', body: JSON.stringify({ motivo })
    });
    const d = await res.json();
    if (res.ok) {
      mostrarToast(d.mensaje || 'Incidencia rechazada.', 'success');
      cerrarModal('modalRechazarInc');
      cerrarModal('modalDetalleInc');
      cargarPendientesIncidencias();
    } else mostrarToast(d.mensaje || 'Error.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

// ── Apoyos pendientes ──
async function cargarPendientesApoyos() {
  try {
    const r    = await fetchAPI(`${API}/apoyos/pendientes`);
    const datos = await r.json();
    document.getElementById('cntPendApoyos').textContent = datos.length;

    const html = datos.map(a => `
      <tr>
        <td>
          <div style="font-weight:600;color:var(--text-primary);font-size:.875rem;">${a.titulo}</div>
          <div class="mono" style="font-size:.72rem;color:var(--text-muted);">#${a.id_incidencia}</div>
        </td>
        <td style="font-size:.82rem;">${a.usuario}</td>
        <td>${badgePrio(a.prioridad)}</td>
        <td style="color:var(--green);font-family:'Space Mono',monospace;">$${parseFloat(a.monto||0).toFixed(2)}</td>
        <td class="mono" style="font-size:.78rem;">${new Date(a.created_at).toLocaleDateString('es-EC')}</td>
        <td>
          <div class="flex items-center" style="gap:6px;">
            <button class="btn btn-ghost btn-sm" style="color:var(--green);border-color:rgba(0,229,160,.25);" onclick="aprobarApoyo(${a.id_apoyo})">✓ Aprobar</button>
            <button class="btn btn-coral btn-sm" onclick="abrirRechazarApoyo(${a.id_apoyo})">✕</button>
          </div>
        </td>
      </tr>`).join('');

    document.getElementById('tbodyPendApoyos').innerHTML = html ||
      `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--text-muted);">No hay apoyos pendientes 🎉</td></tr>`;
  } catch(e) {
    document.getElementById('tbodyPendApoyos').innerHTML =
      `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--coral);">Error al cargar</td></tr>`;
  }
}

async function aprobarApoyo(id) {
  try {
    const res = await fetchAPI(`${API}/apoyos/${id}/aprobar`, { method:'PUT' });
    const d   = await res.json();
    if (res.ok) { mostrarToast(d.mensaje || 'Apoyo aprobado.', 'success'); cargarPendientesApoyos(); }
    else mostrarToast(d.mensaje || 'Error.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

function abrirRechazarApoyo(id) {
  rechazarApoyoId = id;
  document.getElementById('motivoRechazarApoyo').value = '';
  document.getElementById('modalRechazarApoyo').classList.add('open');
}

async function rechazarApoyoConfirm() {
  const motivo = document.getElementById('motivoRechazarApoyo').value.trim();
  if (!motivo) return mostrarToast('El motivo es obligatorio.', 'warning');
  try {
    const res = await fetchAPI(`${API}/apoyos/${rechazarApoyoId}/rechazar`, {
      method:'PUT', body: JSON.stringify({ motivo })
    });
    const d = await res.json();
    if (res.ok) {
      mostrarToast(d.mensaje || 'Apoyo rechazado.', 'success');
      cerrarModal('modalRechazarApoyo');
      cargarPendientesApoyos();
    } else mostrarToast(d.mensaje || 'Error.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

// Resumen ejecutivo (KPIs superiores del panel admin)
async function cargarResumenEjecutivo() {
  try {
    const [rResumen, rUsuarios] = await Promise.all([
      fetchAPI(`${API}/dashboard/resumen`),
      fetchAPI(`${API}/admin/usuarios`),
    ]);
    const resumen = await rResumen.json();
    const usuarios = await rUsuarios.json();
    const listaUsuarios = usuarios.data || usuarios || [];
    document.getElementById('ex_total').textContent = resumen.total ?? 0;
    document.getElementById('ex_pendientes').textContent = resumen.pendientes_aprobacion ?? 0;
    document.getElementById('ex_tasa').textContent = `${resumen.porcentaje_resueltas ?? 0}%`;
    document.getElementById('ex_usuarios').textContent = Array.isArray(listaUsuarios) ? listaUsuarios.filter(u => u.activo).length : '—';
  } catch (e) { /* el resto del panel funciona igual si esto falla */ }
}

// Init
cargarPendientesIncidencias();
cargarResumenEjecutivo();
