// js/incidencias.js — lógica de la página incidencias

let paginaActual = 1;
let idEliminar   = null;
const POR_PAG    = 10;
let mapaEditar, marcadorEditar;
let incentivosPorPrioridad = {};
let misApoyosSet = new Set();

const COLOR_ESTADO = {
  'Registrada':  { bg:'rgba(255,59,107,.15)',  color:'#FF3B6B' },
  'Abierta':    { bg:'rgba(255,59,107,.15)',  color:'#FF3B6B' },
  'En proceso': { bg:'rgba(245,158,11,.15)', color:'#F59E0B' },
  'Resuelta':   { bg:'rgba(0,229,160,.15)',  color:'#00E5A0' },
  'Resuelta':   { bg:'rgba(0,229,160,.15)',  color:'#00E5A0' },
  'Cerrada':    { bg:'rgba(232,244,253,.06)', color:'#94a3b8' },
  'Cerrada':    { bg:'rgba(232,244,253,.06)', color:'#94a3b8' },
};
const COLOR_PRIO = {
  'Crítica': '#FF3B6B', 'Alta': '#F97316', 'Media': '#00D4FF', 'Baja': '#00E5A0'
};

function badgeEstado(e) {
  const c = COLOR_ESTADO[e] || { bg:'rgba(232,244,253,.06)', color:'#94a3b8' };
  return `<span class="badge" style="background:${c.bg};color:${c.color};border:1px solid ${c.color}30;">${e}</span>`;
}
function badgePrio(p) {
  const c = COLOR_PRIO[p] || '#94a3b8';
  return `<span class="badge" style="background:${c}15;color:${c};border:1px solid ${c}30;">${p}</span>`;
}

function debounce(fn, ms) {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

async function cargarIncidencias(pag = 1) {
  paginaActual = pag;
  const params = new URLSearchParams({ pagina: pag, por_pagina: POR_PAG });
  const buscar    = document.getElementById('buscar').value;
  const tipo      = document.getElementById('filtroTipo').value;
  const estado    = document.getElementById('filtroEstado').value;
  const prioridad = document.getElementById('filtroPrioridad').value;
  const zona      = document.getElementById('filtroZona').value;
  const desde     = document.getElementById('filtroDesde').value;

  if (buscar)    params.append('buscar',    buscar);
  if (tipo)      params.append('tipo',      tipo);
  if (estado)    params.append('estado',    estado);
  if (prioridad) params.append('prioridad', prioridad);
  if (zona)      params.append('zona',      zona);
  if (desde)     params.append('desde',     desde);

  document.getElementById('tbodyIncidencias').innerHTML =
    `<tr><td colspan="9" style="text-align:center;padding:50px;color:var(--text-muted);"><span class="spin">⟳</span> Cargando…</td></tr>`;

  try {
    const [rInc, rApoyos] = await Promise.all([
      fetchAPI(`${API}/incidencias?${params}`),
      fetchAPI(`${API}/apoyos/mis-apoyos`).catch(() => null),
    ]);

    const datos = await rInc.json();
    if (rApoyos) {
      const ap = await rApoyos.json();
      misApoyosSet = new Set((ap || []).map(a => a.id_incidencia));
    }

    const incidencias = datos.data || datos;
    const total       = datos.total || incidencias.length;
    const totalPags   = Math.ceil(total / POR_PAG);

    document.getElementById('totalLabel').textContent = `${total} resultado${total !== 1 ? 's' : ''}`;
    document.getElementById('paginaInfo').textContent =
      `Página ${pag} de ${totalPags || 1}`;

    const usuario = getUsuario();
    const esAdmin = usuario && usuario.rol === 'admin';
    const puedeAsignar = usuario && usuario.rol === 'supervisor';

    const html = incidencias.map(inc => {
      const yaApoyo = misApoyosSet.has(inc.id_incidencia);
      const incentivo = incentivosPorPrioridad[inc.prioridad];
      const asignados = inc.asignados || [];
      const responsables = asignados.filter(a => a.rol_asignacion === 'responsable');
      // ¿El usuario logueado es una de las personas asignadas a esta incidencia?
      const esAsignado = usuario && asignados.some(a => a.id_usuario === usuario.id_usuario);
      const puedeActualizarEstado = (usuario?.rol === 'supervisor' || esAsignado)
        && (inc.siguientes_estados || []).length > 0;
      return `
        <tr>
          <td class="mono" style="color:var(--text-muted);font-size:.78rem;">#${inc.id_incidencia}</td>
          <td>
            <div style="font-weight:600;color:var(--text-primary);font-size:.875rem;">${inc.titulo}</div>
            ${inc.descripcion ? `<div style="font-size:.75rem;color:var(--text-muted);max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${inc.descripcion}</div>` : ''}
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">
              ${responsables.length
                ? `👤 ${responsables.map(r => escapeHtml(r.nombre || '—')).join(', ')}`
                : `<span style="color:var(--amber);">Sin responsable asignado</span>`}
            </div>
          </td>
          <td style="font-size:.82rem;">${inc.tipo}${inc.subtipo ? `<span style="color:var(--text-muted);"> / ${inc.subtipo}</span>` : ''}</td>
          <td style="font-size:.82rem;">${inc.zona}</td>
          <td>${badgePrio(inc.prioridad)}</td>
          <td>${badgeEstado(inc.estado)}</td>
          <td class="mono" style="font-size:.78rem;">${new Date(inc.fecha_ocurrencia).toLocaleDateString('es-EC')}</td>
          <td style="font-size:.82rem;color:var(--text-muted);">${incentivo ? `$${parseFloat(incentivo).toFixed(2)}` : '—'}</td>
          <td>
            <div class="flex items-center" style="gap:6px;flex-wrap:wrap;">
              ${!yaApoyo && inc.estado !== 'Resuelta' && inc.estado !== 'Cerrada'
                ? `<button class="btn btn-ghost btn-sm" title="Apoyar" onclick="apoyar(${inc.id_incidencia})">◎ Apoyar</button>`
                : yaApoyo ? `<span style="font-size:.75rem;color:var(--green);">✓ Apoyado</span>` : ''}
              ${puedeAsignar
                ? `<button class="btn btn-ghost btn-sm" title="Asignar responsable" onclick="abrirAsignar(${inc.id_incidencia})">👤 Asignar</button>`
                : ''}
              ${puedeActualizarEstado
                ? `<button class="btn btn-ghost btn-sm" title="Actualizar estado" onclick="abrirCambioEstado(${inc.id_incidencia})">▶ Estado</button>`
                : ''}
              ${esAdmin
                ? `<button class="btn btn-ghost btn-sm" title="Editar" onclick="abrirEditar(${inc.id_incidencia})">✎</button>
                   <button class="btn btn-coral btn-sm" title="Eliminar" onclick="abrirEliminar(${inc.id_incidencia})">✕</button>`
                : ''}
            </div>
          </td>
        </tr>`;
    }).join('');

    document.getElementById('tbodyIncidencias').innerHTML = html ||
      `<tr><td colspan="9" style="text-align:center;padding:50px;color:var(--text-muted);">No hay incidencias con esos filtros</td></tr>`;

    // Paginación
    let pags = '';
    if (totalPags > 1) {
      pags += `<button class="page-btn" ${pag===1?'disabled':''} onclick="cargarIncidencias(${pag-1})">‹</button>`;
      const start = Math.max(1, pag-2), end = Math.min(totalPags, pag+2);
      for (let i = start; i <= end; i++) {
        pags += `<button class="page-btn ${i===pag?'active':''}" onclick="cargarIncidencias(${i})">${i}</button>`;
      }
      pags += `<button class="page-btn" ${pag===totalPags?'disabled':''} onclick="cargarIncidencias(${pag+1})">›</button>`;
    }
    document.getElementById('paginacion').innerHTML = pags;

  } catch(e) {
    document.getElementById('tbodyIncidencias').innerHTML =
      `<tr><td colspan="9" style="text-align:center;padding:50px;color:var(--coral);">Error al cargar incidencias</td></tr>`;
  }
}

async function apoyar(idInc) {
  try {
    const inc = await (await fetchAPI(`${API}/incidencias/${idInc}`)).json();
    const incentivo = incentivosPorPrioridad[inc.prioridad] || 0;
    const res = await fetchAPI(`${API}/apoyos`, {
      method: 'POST',
      body: JSON.stringify({ id_incidencia: idInc, monto: incentivo })
    });
    const d = await res.json();
    if (res.ok && d.ok) { mostrarToast(d.mensaje, 'success'); cargarIncidencias(paginaActual); }
    else mostrarToast(d.mensaje || 'Error al registrar apoyo.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

// ── Asignar responsable ──
// Un admin/supervisor designa a la persona (técnico institucional) responsable
// de resolver la incidencia. Esto crea el registro en incidencia_asignaciones
// que alimenta el reporte "Rendimiento por responsable".
async function abrirAsignar(id) {
  document.getElementById('asignar_id').value = id;
  document.getElementById('btnGuardarAsignar').disabled = false;
  try {
    const [inc, usuarios] = await Promise.all([
      fetchAPI(`${API}/incidencias/${id}`).then(r => r.json()),
      fetchAPI(`${API}/catalogos/usuarios?institucional=1`).then(r => r.json()),
    ]);

    const actuales = (inc.asignados || []).filter(a => a.rol_asignacion === 'responsable');
    document.getElementById('asignar_actuales').innerHTML = actuales.length
      ? `Responsable(s) actual(es): <strong>${actuales.map(a => escapeHtml(a.nombre || '—')).join(', ')}</strong>`
      : 'Esta incidencia todavía no tiene un responsable asignado.';

    const sel = document.getElementById('asignar_id_usuario');
    sel.innerHTML = (usuarios || [])
      .map(u => `<option value="${u.id}">${escapeHtml(u.nombre)} (${escapeHtml(u.rol || '')})</option>`)
      .join('') || '<option value="">No hay usuarios institucionales activos</option>';

    document.getElementById('modalAsignar').classList.add('open');
  } catch (e) {
    mostrarToast('Error al cargar datos de asignación.', 'danger');
  }
}

function cerrarModalAsignar() { document.getElementById('modalAsignar').classList.remove('open'); }

async function guardarAsignacion() {
  const id = document.getElementById('asignar_id').value;
  const idUsuario = document.getElementById('asignar_id_usuario').value;
  if (!idUsuario) { mostrarToast('Selecciona una persona responsable.', 'warning'); return; }
  const btn = document.getElementById('btnGuardarAsignar');
  btn.disabled = true; btn.textContent = 'Guardando…';
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/asignar-tecnico`, {
      method: 'PUT', body: JSON.stringify({ id_usuario: parseInt(idUsuario) })
    });
    const d = await res.json();
    if (res.ok && (d.ok ?? true)) {
      mostrarToast('Responsable asignado correctamente.', 'success');
      cerrarModalAsignar();
      cargarIncidencias(paginaActual);
    } else {
      mostrarToast(d.mensaje || 'No se pudo asignar el responsable.', 'danger');
    }
  } catch (e) {
    mostrarToast('Error de conexión.', 'danger');
  } finally {
    btn.disabled = false; btn.textContent = 'Asignar';
  }
}

// ── Actualizar / finalizar estado ──
// Disponible para admin/supervisor y para la persona que fue asignada como
// responsable (o apoyo) de la incidencia, para que pueda marcarla como
// "En proceso", "Resuelta", etc. sin necesitar permisos de edición completa.
async function abrirCambioEstado(id) {
  document.getElementById('estado_id').value = id;
  document.getElementById('btnGuardarEstado').disabled = false;
  try {
    const [inc, rEstados] = await Promise.all([
      fetchAPI(`${API}/incidencias/${id}`).then(r => r.json()),
      fetchAPI(`${API}/catalogos/estados`).then(r => r.json()),
    ]);

    document.getElementById('estado_actual_label').innerHTML =
      `Estado actual: ${badgeEstado(inc.estado)}`;

    const permitidos = inc.siguientes_estados || [];
    const opciones = rEstados.filter(e => permitidos.includes(e.nombre));
    const sel = document.getElementById('estado_nuevo');
    sel.innerHTML = opciones.length
      ? opciones.map(e => `<option value="${e.id}">${e.nombre}</option>`).join('')
      : '<option value="">No hay transiciones disponibles</option>';
    document.getElementById('estado_comentario').value = '';

    document.getElementById('modalEstado').classList.add('open');
  } catch (e) {
    mostrarToast('Error al cargar la incidencia.', 'danger');
  }
}

function cerrarModalEstado() { document.getElementById('modalEstado').classList.remove('open'); }

async function guardarCambioEstado() {
  const id = document.getElementById('estado_id').value;
  const idEstado = document.getElementById('estado_nuevo').value;
  if (!idEstado) { mostrarToast('Selecciona el nuevo estado.', 'warning'); return; }
  const comentario = document.getElementById('estado_comentario').value.trim();
  const btn = document.getElementById('btnGuardarEstado');
  btn.disabled = true; btn.textContent = 'Guardando…';
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/estado`, {
      method: 'PUT',
      body: JSON.stringify({ id_estado: parseInt(idEstado), comentario: comentario || 'Actualización de estado' })
    });
    const d = await res.json();
    if (res.ok && (d.ok ?? true)) {
      mostrarToast(d.mensaje || 'Estado actualizado.', 'success');
      cerrarModalEstado();
      cargarIncidencias(paginaActual);
    } else {
      mostrarToast(d.mensaje || 'No se pudo actualizar el estado.', 'danger');
    }
  } catch (e) {
    mostrarToast('Error de conexión.', 'danger');
  } finally {
    btn.disabled = false; btn.textContent = 'Guardar';
  }
}

// ── Editar ──
let mapaEditInit = false;

async function abrirEditar(id) {
  document.getElementById('btnGuardarEdit').disabled = false;
  try {
    const [rInc, rTipos, rEstados, rZonas] = await Promise.all([
      fetchAPI(`${API}/incidencias/${id}`).then(r=>r.json()),
      fetchAPI(`${API}/catalogos/tipos`).then(r=>r.json()),
      fetchAPI(`${API}/catalogos/estados`).then(r=>r.json()),
      fetchAPI(`${API}/catalogos/zonas`).then(r=>r.json()),
    ]);

    document.getElementById('edit_id').value             = rInc.id_incidencia;
    window.incidenciaEditandoEstado = rInc.id_estado_actual;
    document.getElementById('edit_titulo').value         = rInc.titulo || '';
    document.getElementById('edit_prioridad').value      = rInc.prioridad || '';
    document.getElementById('edit_fecha_ocurrencia').value = (rInc.fecha_ocurrencia||'').split('T')[0];
    document.getElementById('edit_descripcion').value    = rInc.descripcion || '';
    document.getElementById('edit_latitud').value        = rInc.latitud || '';
    document.getElementById('edit_longitud').value       = rInc.longitud || '';
    document.getElementById('edit_direccion_texto').value = rInc.direccion_texto || '';

    const linkComoLlegar = document.getElementById('edit_comoLlegar');
    if (rInc.como_llegar_url) {
      linkComoLlegar.href = rInc.como_llegar_url;
      linkComoLlegar.style.display = 'inline-flex';
    } else {
      linkComoLlegar.style.display = 'none';
    }

    const badgesAprobacion = {
      pendiente_revision: { texto: '⏳ Pendiente de revisión', color: '#F59E0B' },
      aprobada: { texto: '✓ Aprobada', color: '#00E5A0' },
      rechazada: { texto: '✕ Rechazada', color: '#FF3B6B' },
    };
    const bAprob = badgesAprobacion[rInc.estado_aprobacion] || { texto: rInc.estado_aprobacion, color: '#94a3b8' };
    document.getElementById('edit_badgeAprobacion').innerHTML =
      `<span class="badge" style="background:${bAprob.color}15;color:${bAprob.color};border:1px solid ${bAprob.color}30;">${bAprob.texto}</span>` +
      (rInc.aprobacion_automatica ? ' <span style="font-size:.72rem;color:var(--text-muted);">(aprobación automática por vencimiento de 24h)</span>' : '');

    const historial = rInc.historial_aprobacion || [];
    document.getElementById('edit_historialAprobacion').innerHTML = historial.length
      ? historial.map(h => `<div>• <strong>${escapeHtml(h.accion)}</strong> por ${escapeHtml(h.usuario)} — ${escapeHtml(h.fecha || '')}${h.motivo ? `: ${escapeHtml(h.motivo)}` : ''}</div>`).join('')
      : '<span style="color:var(--text-muted);">Aún no hay historial de aprobación.</span>';

    const selTipo = document.getElementById('edit_id_tipo');
    selTipo.innerHTML = rTipos.map(t => `<option value="${t.id}" ${t.id==rInc.id_tipo?'selected':''}>${t.nombre}</option>`).join('');

    const selEst = document.getElementById('edit_id_estado');
    const permitidosEdit = rInc.siguientes_estados || [];
    const opcionesEdit = rEstados.filter(e => permitidosEdit.includes(e.nombre));
    const actualEst = rEstados.find(e => e.id == rInc.id_estado_actual);
    selEst.innerHTML =
      `<option value="${rInc.id_estado_actual}" selected>${actualEst ? actualEst.nombre : 'Estado actual'} (actual)</option>` +
      opcionesEdit.map(e => `<option value="${e.id}">${e.nombre}</option>`).join('');

    const selZona = document.getElementById('edit_id_zona');
    selZona.innerHTML = rZonas.map(z => `<option value="${z.id}" ${z.id==rInc.id_zona?'selected':''}>${z.nombre}</option>`).join('');

    document.getElementById('modalDetalle').classList.add('open');
    cargarComentarios(id);

    setTimeout(async () => {
      if (!mapaEditar) {
        await cargarLeaflet();
        mapaEditar = L.map('mapaEditar').setView([rInc.latitud||(-2.9), rInc.longitud||(-79)], 13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom:19 }).addTo(mapaEditar);
        mapaEditar.on('click', e => {
          document.getElementById('edit_latitud').value  = e.latlng.lat.toFixed(6);
          document.getElementById('edit_longitud').value = e.latlng.lng.toFixed(6);
          if (marcadorEditar) mapaEditar.removeLayer(marcadorEditar);
          marcadorEditar = L.marker([e.latlng.lat, e.latlng.lng]).addTo(mapaEditar);
        });
        mapaEditInit = true;
      } else {
        mapaEditar.setView([rInc.latitud||(-2.9), rInc.longitud||(-79)], 13);
      }
      if (rInc.latitud && rInc.longitud) {
        if (marcadorEditar) mapaEditar.removeLayer(marcadorEditar);
        marcadorEditar = L.marker([rInc.latitud, rInc.longitud]).addTo(mapaEditar);
      }
      mapaEditar.invalidateSize();
    }, 200);
  } catch(e) { mostrarToast('Error al cargar incidencia.', 'danger'); }
}

async function guardarEdicion() {
  const btn = document.getElementById('btnGuardarEdit');
  btn.disabled = true; btn.textContent = 'Guardando…';
  const id = document.getElementById('edit_id').value;
  const payload = {
    titulo:          document.getElementById('edit_titulo').value.trim(),
    id_tipo:         parseInt(document.getElementById('edit_id_tipo').value),
    id_estado:       parseInt(document.getElementById('edit_id_estado').value),
    id_zona:         parseInt(document.getElementById('edit_id_zona').value),
    prioridad:       document.getElementById('edit_prioridad').value,
    fecha_ocurrencia:document.getElementById('edit_fecha_ocurrencia').value,
    descripcion:     document.getElementById('edit_descripcion').value.trim(),
    latitud:         parseFloat(document.getElementById('edit_latitud').value) || null,
    longitud:        parseFloat(document.getElementById('edit_longitud').value) || null,
    direccion_texto: document.getElementById('edit_direccion_texto').value.trim() || null,
    comentario:      document.getElementById('edit_comentario').value.trim(),
  };
  try {
    const estadoActual = window.incidenciaEditandoEstado;
    const nuevoEstado = payload.id_estado;
    delete payload.id_estado;
    const res = await fetchAPI(`${API}/incidencias/${id}`, { method:'PUT', body:JSON.stringify(payload) });
    let d = await res.json();
    if (res.ok && nuevoEstado && nuevoEstado !== estadoActual) {
      const rEstado = await fetchAPI(`${API}/incidencias/${id}/estado`, {
        method:'PUT', body:JSON.stringify({ id_estado:nuevoEstado, comentario:payload.comentario || 'Actualización del proceso de atención' })
      });
      d = await rEstado.json();
      if (!rEstado.ok) throw new Error(d.mensaje || d.message || 'Transición de estado no permitida');
    }
    if (res.ok && d.ok) {
      mostrarToast(d.mensaje, 'success');
      cerrarModal();
      cargarIncidencias(paginaActual);
    } else mostrarToast(d.mensaje || 'Error al guardar.', 'danger');
  } catch(e) { mostrarToast(e.message || 'Error de conexión.', 'danger'); }
  finally { btn.disabled = false; btn.textContent = 'Guardar cambios'; }
}

function cerrarModal() { document.getElementById('modalDetalle').classList.remove('open'); }

// ── Eliminar ──
function abrirEliminar(id) { idEliminar = id; document.getElementById('modalEliminar').classList.add('open'); }
function cerrarModalEliminar() { document.getElementById('modalEliminar').classList.remove('open'); idEliminar = null; }

async function confirmarEliminar() {
  if (!idEliminar) return;
  const btn = document.getElementById('btnConfEliminar');
  btn.disabled = true; btn.textContent = 'Eliminando…';
  try {
    const res = await fetchAPI(`${API}/incidencias/${idEliminar}`, { method:'DELETE' });
    const d   = await res.json();
    if (res.ok) { mostrarToast('Incidencia eliminada.', 'success'); cerrarModalEliminar(); cargarIncidencias(paginaActual); }
    else mostrarToast(d.mensaje || 'Error al eliminar.', 'danger');
  } catch(e) { mostrarToast(e.message || 'Error de conexión.', 'danger'); }
  finally { btn.disabled = false; btn.textContent = 'Eliminar'; }
}

function limpiarFiltros() {
  ['buscar','filtroTipo','filtroEstado','filtroPrioridad','filtroZona','filtroDesde'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const qf = document.getElementById('quickFilters');
  if (qf) qf.querySelectorAll('.qf-chip').forEach((b,i) => b.classList.toggle('active', i===0));
  cargarIncidencias(1);
}

function filtrarRapido(estado, btn) {
  document.getElementById('filtroEstado').value = estado;
  document.querySelectorAll('#quickFilters .qf-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  cargarIncidencias(1);
}

// ── Poblar selects de filtros ──
async function poblarFiltros() {
  try {
    const [rTipos, rEstados, rZonas, rIncentivos] = await Promise.all([
      fetchAPI(`${API}/catalogos/tipos`).then(r=>r.json()),
      fetchAPI(`${API}/catalogos/estados`).then(r=>r.json()),
      fetchAPI(`${API}/catalogos/zonas`).then(r=>r.json()),
      fetchAPI(`${API}/catalogos/incentivos`).then(r=>r.json()).catch(()=>[]),
    ]);
    const selT = document.getElementById('filtroTipo');
    rTipos.forEach(t => selT.insertAdjacentHTML('beforeend', `<option value="${t.id}">${t.nombre}</option>`));
    const selE = document.getElementById('filtroEstado');
    rEstados.forEach(e => selE.insertAdjacentHTML('beforeend', `<option value="${e.nombre}">${e.nombre}</option>`));
    const selZ = document.getElementById('filtroZona');
    rZonas.forEach(z => selZ.insertAdjacentHTML('beforeend', `<option value="${z.nombre}">${z.nombre}</option>`));
    (rIncentivos || []).forEach(i => { incentivosPorPrioridad[i.prioridad] = i.monto; });
  } catch(e) {}
}

poblarFiltros();
cargarIncidencias(1);


// ── Comentarios de seguimiento ──
async function cargarComentarios(id) {
  const contenedor = document.getElementById('listaComentarios');
  if (!contenedor) return;
  contenedor.innerHTML = '<span style="color:var(--text-muted);font-size:.8rem;">Cargando comentarios…</span>';
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/comentarios`);
    const data = await res.json();
    const comentarios = data.datos || [];
    contenedor.innerHTML = comentarios.length
      ? comentarios.map(c => `<div style="padding:9px 11px;border:1px solid var(--border-light);border-radius:9px;background:rgba(255,255,255,.02);">
          <div style="font-size:.72rem;color:var(--cyan);font-weight:600;">${escapeHtml(c.autor || 'Sistema')} · ${escapeHtml(c.fecha || '')}</div>
          <div style="font-size:.82rem;color:var(--text-secondary);margin-top:3px;white-space:pre-wrap;">${escapeHtml(c.comentario || '')}</div>
        </div>`).join('')
      : '<span style="color:var(--text-muted);font-size:.8rem;">Aún no hay comentarios.</span>';
  } catch (e) {
    contenedor.innerHTML = '<span style="color:var(--coral);font-size:.8rem;">No se pudieron cargar los comentarios.</span>';
  }
}

async function agregarComentario() {
  const id = document.getElementById('edit_id').value;
  const input = document.getElementById('nuevoComentario');
  const comentario = (input?.value || '').trim();
  if (comentario.length < 3) {
    mostrarToast('El comentario debe tener al menos 3 caracteres.', 'warning');
    return;
  }
  try {
    const res = await fetchAPI(`${API}/incidencias/${id}/comentarios`, {
      method: 'POST',
      body: JSON.stringify({ comentario })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.mensaje || data.message || 'No se pudo agregar el comentario.');
    input.value = '';
    mostrarToast(data.mensaje || 'Comentario agregado.', 'success');
    cargarComentarios(id);
  } catch (e) {
    mostrarToast(e.message || 'Error al agregar comentario.', 'danger');
  }
}

function escapeHtml(texto) {
  return String(texto).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
}
