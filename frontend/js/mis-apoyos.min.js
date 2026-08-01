// js/mis-apoyos.js
let incentivosPorPrioridad = {};
let apoyosYaMarcados = new Set();
let tabActual = 'disponibles';

const COLOR_PRIO = { 'Crítica': '#FF3B6B', 'Alta': '#F97316', 'Media': '#00D4FF', 'Baja': '#00E5A0' };
const COLOR_PAGO = {
  'pendiente_aprobacion': { color:'#F59E0B', label:'Registrada' },
  'aprobado':             { color:'#00E5A0', label:'Aprobado' },
  'pagado':               { color:'#00E5A0', label:'Pagado' },
  'rechazado':            { color:'#FF3B6B', label:'Rechazado' },
};

function badgePrio(p) {
  const c = COLOR_PRIO[p] || '#94a3b8';
  return `<span class="badge" style="background:${c}15;color:${c};border:1px solid ${c}30;">${p}</span>`;
}
function badgePago(e) {
  const c = COLOR_PAGO[e] || { color:'#94a3b8', label:e };
  return `<span class="badge" style="background:${c.color}15;color:${c.color};border:1px solid ${c.color}30;">${c.label}</span>`;
}

function cambiarTab(tab) {
  tabActual = tab;
  const showDisp = tab === 'disponibles';
  document.getElementById('panelDisponibles').style.display = showDisp ? '' : 'none';
  document.getElementById('panelMisApoyos').style.display   = showDisp ? 'none' : '';
  document.getElementById('tabDisponibles').className = showDisp ? 'btn btn-primary' : 'btn btn-ghost';
  document.getElementById('tabMisApoyos').className   = showDisp ? 'btn btn-ghost' : 'btn btn-primary';
  if (!showDisp) cargarMisApoyos();
}

async function cargarSaldo() {
  try {
    const r = await fetchAPI(`${API}/apoyos/mi-saldo`);
    const d = await r.json();
    document.getElementById('saldoPagado').textContent       = `$${parseFloat(d.total_pagado||0).toFixed(2)}`;
    document.getElementById('saldoPendiente').textContent    = `$${parseFloat(d.total_pendiente||0).toFixed(2)}`;
    document.getElementById('apoyosCompletados').textContent = d.apoyos_completados || 0;
  } catch(e) {}
}

async function cargarDisponibles() {
  try {
    const [rInc, rApoyos, rInc2] = await Promise.all([
      fetchAPI(`${API}/incidencias?por_pagina=50`).then(r=>r.json()),
      fetchAPI(`${API}/apoyos/mis-apoyos`).then(r=>r.json()).catch(()=>[]),
      fetchAPI(`${API}/catalogos/incentivos`).then(r=>r.json()).catch(()=>[]),
    ]);

    rInc2.forEach(i => { incentivosPorPrioridad[i.prioridad] = i.monto; });
    const misIds = new Set((rApoyos||[]).map(a => a.id_incidencia));
    apoyosYaMarcados = misIds;

    const inc = rInc.data || rInc;
    const disponibles = inc.filter(i => !misIds.has(i.id_incidencia) && i.estado !== 'Resuelta' && i.estado !== 'Cerrada');

    const html = disponibles.map(i => {
      const monto = incentivosPorPrioridad[i.prioridad];
      return `<tr>
        <td>
          <div style="font-weight:600;color:var(--text-primary);font-size:.875rem;">${i.titulo}</div>
          <div class="mono" style="font-size:.72rem;color:var(--text-muted);">#${i.id_incidencia}</div>
        </td>
        <td style="font-size:.82rem;">${i.tipo}</td>
        <td style="font-size:.82rem;">${i.zona}</td>
        <td>${badgePrio(i.prioridad)}</td>
        <td style="color:var(--green);font-family:'Space Mono',monospace;font-size:.85rem;">${monto ? `$${parseFloat(monto).toFixed(2)}` : '—'}</td>
        <td>
          <button class="btn btn-primary btn-sm" onclick="apoyar(${i.id_incidencia},${monto||0})">
            ◎ Apoyar
          </button>
        </td>
      </tr>`;
    }).join('');

    document.getElementById('tbodyDisponibles').innerHTML = html ||
      `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--text-muted);">No hay incidencias disponibles para apoyar</td></tr>`;
  } catch(e) {
    document.getElementById('tbodyDisponibles').innerHTML =
      `<tr><td colspan="6" style="text-align:center;padding:50px;color:var(--coral);">Error al cargar datos</td></tr>`;
  }
}

async function cargarMisApoyos() {
  try {
    const r    = await fetchAPI(`${API}/apoyos/mis-apoyos`);
    const datos = await r.json();
    const html  = (datos||[]).map(a => `<tr>
      <td>
        <div style="font-weight:600;color:var(--text-primary);font-size:.875rem;">${a.titulo}</div>
        <div class="mono" style="font-size:.72rem;color:var(--text-muted);">#${a.id_incidencia}</div>
      </td>
      <td>${badgePrio(a.prioridad)}</td>
      <td style="color:var(--green);font-family:'Space Mono',monospace;">$${parseFloat(a.monto||0).toFixed(2)}</td>
      <td>${badgePago(a.estado_pago)}</td>
      <td class="mono" style="font-size:.78rem;">${new Date(a.created_at).toLocaleDateString('es-EC')}</td>
    </tr>`).join('');
    document.getElementById('tbodyMisApoyos').innerHTML = html ||
      `<tr><td colspan="5" style="text-align:center;padding:50px;color:var(--text-muted);">Aún no has apoyado ninguna incidencia</td></tr>`;
  } catch(e) {}
}

async function apoyar(idInc, monto) {
  try {
    const res = await fetchAPI(`${API}/apoyos`, {
      method:'POST', body: JSON.stringify({ id_incidencia: idInc, monto })
    });
    const d = await res.json();
    if (res.ok && d.ok) { mostrarToast(d.mensaje, 'success'); cargarSaldo(); cargarDisponibles(); }
    else mostrarToast(d.mensaje || 'Error.', 'danger');
  } catch(e) { mostrarToast('Error de conexión.', 'danger'); }
}

cargarSaldo();
cargarDisponibles();
