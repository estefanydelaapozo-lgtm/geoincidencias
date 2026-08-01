// js/reportes.js
let chartEstado, chartCategoria, chartPrioridad, chartTendencia;

Chart.defaults.color          = 'rgba(15,23,42,.62)';   // texto legible sobre fondo claro
Chart.defaults.borderColor    = 'rgba(15,23,42,.08)';   // líneas de grilla sutiles
Chart.defaults.font.family    = "'Inter', sans-serif";

const COLORS = ['#6D28D9','#E11D48','#D97706','#16A34A','#8B5CF6','#DB2777','#0EA5E9','#059669'];

// ── Plugin propio: dibuja el porcentaje directamente sobre cada barra/porción,
// para que sea visible siempre y no solo al pasar el mouse (tooltip). ──
const porcentajeLabelsPlugin = {
  id: 'porcentajeLabels',
  afterDatasetsDraw(chart) {
    if (chart.config.type === 'line') return; // en la tendencia no aplica porcentaje
    const { ctx } = chart;
    const meta = chart.getDatasetMeta(0);
    if (!meta || !meta.data || !meta.data.length) return;
    const data = chart.data.datasets[0].data || [];
    const total = data.reduce((a, b) => a + (Number(b) || 0), 0);
    if (!total) return;

    const esDoughnut = chart.config.type === 'doughnut' || chart.config.type === 'pie';
    const esHorizontal = chart.config.type === 'bar' && chart.options.indexAxis === 'y';

    ctx.save();
    ctx.font = '700 11px Inter, sans-serif';
    meta.data.forEach((el, i) => {
      const val = Number(data[i]) || 0;
      if (!val) return;
      const pct = `${Math.round((val / total) * 100)}%`;

      if (esDoughnut) {
        const pos = el.getCenterPoint();
        ctx.fillStyle = '#fff';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(pct, pos.x, pos.y);
      } else if (esHorizontal) {
        ctx.fillStyle = '#0F172A';
        ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
        ctx.fillText(pct, el.x + 8, el.y);
      } else {
        ctx.fillStyle = '#0F172A';
        ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
        ctx.fillText(pct, el.x, el.y - 6);
      }
    });
    ctx.restore();
  }
};
Chart.register(porcentajeLabelsPlugin);

function destroyChart(c) { if (c) { c.destroy(); return null; } return null; }

function aplicarPeriodoRapido() {
  const dias = document.getElementById('periodoRapido').value;
  if (!dias) return;
  const hasta = new Date();
  const desde = new Date();
  desde.setDate(desde.getDate() - parseInt(dias));
  document.getElementById('rDesde').value = desde.toISOString().split('T')[0];
  document.getElementById('rHasta').value = hasta.toISOString().split('T')[0];
  cargarReportes();
}

async function poblarSelect(url, selectId) {
  try {
    const r    = await fetchAPI(url);
    const datos = await r.json();
    const sel  = document.getElementById(selectId);
    datos.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d.id; opt.textContent = d.nombre; sel.appendChild(opt);
    });
  } catch(e) {}
}

async function cargarReportes() {
  const params = new URLSearchParams();
  const desde = document.getElementById('rDesde').value;
  const hasta = document.getElementById('rHasta').value;
  const tipo  = document.getElementById('rTipo').value;
  const zona  = document.getElementById('rZona').value;
  const prioridad = document.getElementById('rPrioridad').value;
  if (desde) params.append('desde', desde);
  if (hasta) params.append('hasta', hasta);
  if (tipo)  params.append('tipo',  tipo);
  if (zona)  params.append('zona',  zona);
  if (prioridad) params.append('prioridad', prioridad);

  try {
    const [resumen, porTipo, porEstado, tendencia, porResponsable] = await Promise.all([
      fetchAPI(`${API}/reportes/resumen?${params}`).then(r=>r.json()),
      fetchAPI(`${API}/reportes/por-categoria?${params}`).then(r=>r.json()),
      fetchAPI(`${API}/reportes/por-estado?${params}`).then(r=>r.json()),
      fetchAPI(`${API}/reportes/tendencia?${params}`).then(r=>r.json()),
      fetchAPI(`${API}/reportes/por-responsable?${params}`).then(r=>r.json()),
    ]);

    // KPIs
    document.getElementById('r_total').textContent      = resumen.total || 0;
    document.getElementById('r_tiempo_prom').textContent= resumen.tiempo_promedio_resolucion
      ? parseFloat(resumen.tiempo_promedio_resolucion).toFixed(1) : '—';
    const tasa = resumen.total > 0 ? ((resumen.resueltas / resumen.total)*100).toFixed(1) : '0';
    document.getElementById('r_tasa').textContent       = `${tasa}%`;
    document.getElementById('r_prio_frec').textContent  = resumen.prioridad_predominante || '—';

    // Gráfica estados
    chartEstado = destroyChart(chartEstado);
    chartEstado = new Chart(document.getElementById('chartEstado'), {
      type: 'doughnut',
      data: {
        labels: porEstado.map(d=>d.estado),
        datasets: [{ data: porEstado.map(d=>d.total), backgroundColor: COLORS, borderWidth: 0 }]
      },
      options: { plugins: { legend:{ position:'bottom', labels:{ padding:16, font:{size:11} } } }, cutout:'65%' }
    });

    // Gráfica categorías
    chartCategoria = destroyChart(chartCategoria);
    chartCategoria = new Chart(document.getElementById('chartCategoria'), {
      type: 'bar',
      data: {
        labels: porTipo.map(d=>d.tipo),
        datasets: [{ data: porTipo.map(d=>d.total), backgroundColor: COLORS, borderRadius:6, borderSkipped:false }]
      },
      options: {
        indexAxis: 'y',
        layout: { padding: { right: 34 } },
        plugins: { legend:{ display:false } },
        scales: {
          x: { grid:{ color:'rgba(15,23,42,.06)' }, ticks:{ font:{size:10} } },
          y: { grid:{ display:false }, ticks:{ font:{size:11} } }
        }
      }
    });

    // Gráfica prioridad
    const prios = porEstado.filter ? [] : [];
    chartPrioridad = destroyChart(chartPrioridad);
    const prioData = resumen.por_prioridad || [];
    chartPrioridad = new Chart(document.getElementById('chartPrioridad'), {
      type: 'bar',
      data: {
        labels: prioData.map(d=>d.prioridad),
        datasets: [{ data: prioData.map(d=>d.total), backgroundColor:['#E11D48','#D97706','#6D28D9','#16A34A'], borderRadius:8, borderSkipped:false }]
      },
      options: {
        layout: { padding: { top: 26 } },
        plugins: { legend:{ display:false } },
        scales: {
          x: { grid:{ display:false }, ticks:{ font:{size:11} } },
          y: { grid:{ color:'rgba(15,23,42,.06)' }, ticks:{ font:{size:10} } }
        }
      }
    });

    // Gráfica tendencia
    chartTendencia = destroyChart(chartTendencia);
    chartTendencia = new Chart(document.getElementById('chartTendencia'), {
      type: 'line',
      data: {
        labels: tendencia.map(d=>d.fecha||d.mes),
        datasets: [{
          data: tendencia.map(d=>d.total),
          borderColor: '#6D28D9', backgroundColor: 'rgba(109,40,217,.08)',
          borderWidth: 2, tension: 0.4, fill: true,
          pointBackgroundColor: '#6D28D9', pointRadius: 3
        }]
      },
      options: {
        plugins: { legend:{ display:false } },
        scales: {
          x: { grid:{ color:'rgba(15,23,42,.06)' }, ticks:{ font:{size:10}, maxTicksLimit:8 } },
          y: { grid:{ color:'rgba(15,23,42,.06)' }, ticks:{ font:{size:10} } }
        }
      }
    });

    // Tabla responsables
    const html = (porResponsable||[]).map(r => {
      const tasa = r.asignadas > 0 ? ((r.resueltas/r.asignadas)*100).toFixed(0) : 0;
      return `<tr>
        <td style="color:var(--text-primary);font-weight:500;">${r.responsable}</td>
        <td class="mono">${r.asignadas}</td>
        <td><span style="color:var(--green);">${r.resueltas}</span></td>
        <td><span style="color:var(--amber);">${r.en_proceso||0}</span></td>
        <td>
          <div class="flex items-center gap-8">
            <div class="progress-track" style="width:80px;"><div class="progress-fill" style="width:${tasa}%;background:${tasa>70?'var(--green)':tasa>40?'var(--amber)':'var(--coral)'};"></div></div>
            <span class="mono" style="font-size:.78rem;">${tasa}%</span>
          </div>
        </td>
      </tr>`;
    }).join('');
    document.getElementById('tbodyResponsables').innerHTML = html ||
      `<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">Sin datos en el período</td></tr>`;

  } catch(e) { console.error(e); mostrarToast('Error al cargar reportes.', 'danger'); }
}

function limpiarFiltros() {
  ['rDesde','rHasta'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('rTipo').value = '';
  document.getElementById('rZona').value = '';
  document.getElementById('periodoRapido').value = '';
  document.getElementById('rPrioridad').value = '';
}

poblarSelect(`${API}/catalogos/tipos`, 'rTipo');
poblarSelect(`${API}/catalogos/zonas`, 'rZona');

// Cargar últimos 30 días por defecto
const hoy   = new Date();
const hace30 = new Date(); hace30.setDate(hoy.getDate()-30);
document.getElementById('rDesde').value = hace30.toISOString().split('T')[0];
document.getElementById('rHasta').value = hoy.toISOString().split('T')[0];
cargarReportes();

exigirSesion();
inyectarSidebar('reportes');
inicializarBarraUsuario();


function exportarExcel() {
  const rows = [['GeoIncidencias - Reporte institucional'],['Generado', new Date().toLocaleString('es-EC')],[],['Indicador','Valor'],['Total incidencias',document.getElementById('r_total').textContent],['Tiempo promedio',document.getElementById('r_tiempo_prom').textContent],['Tasa de resolución',document.getElementById('r_tasa').textContent],['Prioridad predominante',document.getElementById('r_prio_frec').textContent],[],['Responsable','Asignadas','Resueltas','En proceso','Tasa']];
  document.querySelectorAll('#tbodyResponsables tr').forEach(tr=>rows.push([...tr.querySelectorAll('td')].map(td=>td.innerText.trim())));
  const wb=XLSX.utils.book_new(), ws=XLSX.utils.aoa_to_sheet(rows); ws['!cols']=[{wch:34},{wch:18},{wch:16},{wch:16},{wch:14}]; XLSX.utils.book_append_sheet(wb,ws,'Reporte'); XLSX.writeFile(wb,`GeoIncidencias_Reporte_${new Date().toISOString().slice(0,10)}.xlsx`);
}

function exportarPDF() {
  const { jsPDF } = window.jspdf; const doc=new jsPDF();
  doc.setFillColor(8,18,38);doc.rect(0,0,210,34,'F');doc.setTextColor(255,255,255);doc.setFontSize(20);doc.text('GeoIncidencias',14,16);doc.setFontSize(10);doc.text('Reporte institucional de gestión territorial',14,24);
  doc.setTextColor(30,41,59);doc.setFontSize(12);doc.text(`Fecha de generación: ${new Date().toLocaleString('es-EC')}`,14,44);
  const metrics=[['Total incidencias',r_total.textContent],['Tiempo promedio',r_tiempo_prom.textContent],['Tasa de resolución',r_tasa.textContent],['Prioridad predominante',r_prio_frec.textContent]];
  let y=56;metrics.forEach(([a,b])=>{doc.setFont(undefined,'bold');doc.text(a,14,y);doc.setFont(undefined,'normal');doc.text(String(b),85,y);y+=10});
  doc.setFont(undefined,'bold');doc.text('Rendimiento por responsable',14,y+8);y+=16;doc.setFontSize(9);
  document.querySelectorAll('#tbodyResponsables tr').forEach(tr=>{const cells=[...tr.querySelectorAll('td')].map(td=>td.innerText.trim());if(!cells.length)return;doc.text(cells.slice(0,5).join(' | ').slice(0,105),14,y);y+=7;if(y>280){doc.addPage();y=20}});
  doc.save(`GeoIncidencias_Reporte_${new Date().toISOString().slice(0,10)}.pdf`);
}
