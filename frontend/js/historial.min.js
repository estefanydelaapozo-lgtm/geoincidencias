// js/historial.js
let paginaActual = 1;
const POR_PAG = 20;

const ACCION_COLOR = {
  'login':    { color:'#00E5A0', icon:'→' },
  'logout':   { color:'#94a3b8', icon:'←' },
  'crear':    { color:'#00D4FF', icon:'+' },
  'editar':   { color:'#F59E0B', icon:'✎' },
  'eliminar': { color:'#FF3B6B', icon:'✕' },
  'aprobar':  { color:'#00E5A0', icon:'✓' },
  'rechazar': { color:'#FF3B6B', icon:'✕' },
};

function badgeAccion(a) {
  const c = ACCION_COLOR[a] || { color:'#94a3b8', icon:'●' };
  return `<span class="badge" style="background:${c.color}12;color:${c.color};border:1px solid ${c.color}25;font-family:'Space Mono',monospace;font-size:.7rem;">${c.icon} ${a}</span>`;
}

async function cargarHistorial(pag = 1) {
  paginaActual = pag;
  const params = new URLSearchParams({ pagina: pag, por_pagina: POR_PAG });
  const usuario = document.getElementById('filtroUsuario').value;
  const accion  = document.getElementById('filtroAccion').value;
  const desde   = document.getElementById('filtroDesde').value;
  const hasta   = document.getElementById('filtroHasta').value;

  if (usuario) params.append('usuario', usuario);
  if (accion)  params.append('accion',  accion);
  if (desde)   params.append('desde',   desde);
  if (hasta)   params.append('hasta',   hasta);

  document.getElementById('tbodyHistorial').innerHTML =
    `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--text-muted);"><span class="spin">⟳</span> Cargando…</td></tr>`;

  try {
    const r    = await fetchAPI(`${API}/historial?${params}`);
    const datos = await r.json();
    const items = datos.datos || datos.data || datos;
    const total = datos.total || items.length;
    const totalPags = Math.ceil(total / POR_PAG);

    document.getElementById('totalHistorial').textContent = `${total} registro${total !== 1 ? 's' : ''}`;
    document.getElementById('paginaInfoH').textContent = `Página ${pag} de ${totalPags || 1}`;

    const html = items.map(h => {
      const fecha = new Date(h.fecha_hora || h.created_at);
      return `<tr>
        <td>
          <div class="mono" style="font-size:.8rem;color:var(--text-primary);">${fecha.toLocaleDateString('es-EC')}</div>
          <div class="mono" style="font-size:.72rem;color:var(--text-muted);">${fecha.toLocaleTimeString('es-EC',{hour:'2-digit',minute:'2-digit'})}</div>
        </td>
        <td>
          <div style="font-size:.82rem;color:var(--text-primary);font-weight:500;">${h.usuario || '—'}</div>
          <div style="font-size:.72rem;color:var(--text-muted);">${h.rol || ''}</div>
        </td>
        <td>${badgeAccion(h.accion)}</td>
        <td style="font-size:.82rem;color:var(--text-secondary);">${h.incidencia_titulo || h.modulo || h.tabla || '—'}</td>
        <td style="font-size:.8rem;color:var(--text-muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${h.detalle||''}">
          ${h.detalle || '—'}
        </td>
        <td class="mono" style="font-size:.75rem;color:var(--text-muted);">${h.ip_origen || h.ip || '—'}</td>
      </tr>`;
    }).join('');

    document.getElementById('tbodyHistorial').innerHTML = html ||
      `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--text-muted);">No hay registros</td></tr>`;

    // Paginación
    let pags = '';
    if (totalPags > 1) {
      pags += `<button class="page-btn" ${pag===1?'disabled':''} onclick="cargarHistorial(${pag-1})">‹</button>`;
      const start = Math.max(1,pag-2), end = Math.min(totalPags,pag+2);
      for (let i=start; i<=end; i++) {
        pags += `<button class="page-btn ${i===pag?'active':''}" onclick="cargarHistorial(${i})">${i}</button>`;
      }
      pags += `<button class="page-btn" ${pag===totalPags?'disabled':''} onclick="cargarHistorial(${pag+1})">›</button>`;
    }
    document.getElementById('paginacionH').innerHTML = pags;
  } catch(e) {
    document.getElementById('tbodyHistorial').innerHTML =
      `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--coral);">Error al cargar historial</td></tr>`;
  }
}

async function cargarUsuarios() {
  try {
    const r    = await fetchAPI(`${API}/admin/usuarios`);
    const datos = await r.json();
    const sel  = document.getElementById('filtroUsuario');
    (datos.data || datos).forEach(u => {
      const opt = document.createElement('option');
      opt.value = u.id_usuario; opt.textContent = `${u.nombre} ${u.apellido||''}`;
      sel.appendChild(opt);
    });
  } catch(e) {}
}

function limpiar() {
  ['filtroUsuario','filtroAccion','filtroDesde','filtroHasta'].forEach(id => {
    document.getElementById(id).value = '';
  });
  cargarHistorial(1);
}

cargarUsuarios();
cargarHistorial(1);
