// js/registrar.js
let mapaReg, marcador;
let incentivosPorPrioridad = {};

// Si el usuario escribe la dirección a mano, respetamos lo que escribió y
// dejamos de sobrescribirla en cada clic del mapa.
let direccionEditadaManualmente = false;
document.getElementById('direccion_texto').addEventListener('input', () => {
  direccionEditadaManualmente = true;
});

function marcarUbicacion(lat, lng, mensaje) {
  document.getElementById('latitud').value  = lat.toFixed(6);
  document.getElementById('longitud').value = lng.toFixed(6);
  if (marcador) mapaReg.removeLayer(marcador);
  marcador = L.marker([lat, lng]).addTo(mapaReg).bindPopup('Incidencia aquí').openPopup();
  document.getElementById('ubicacionMsg').innerHTML =
    `<span style="color:var(--green);">✓ ${mensaje}: ${lat.toFixed(5)}, ${lng.toFixed(5)}</span>`;
  // Cada vez que se marca una ubicación NUEVA (clic en el mapa o geolocalización),
  // la dirección debe reflejar ese punto — aunque antes se haya editado a mano.
  direccionEditadaManualmente = false;
  autocompletarDireccion(lat, lng);
}

// Geocodificación inversa best-effort: se actualiza cada vez que se marca un
// nuevo punto en el mapa (para que refleje el lugar recién marcado), salvo
// que el usuario ya haya escrito la dirección a mano. Si el servicio externo
// (OpenStreetMap) no responde, avisamos en vez de dejar el campo en silencio.
async function autocompletarDireccion(lat, lng) {
  const campoDireccion = document.getElementById('direccion_texto');
  const avisoEl = document.getElementById('direccionAviso');
  if (!campoDireccion || direccionEditadaManualmente) return;
  if (avisoEl) avisoEl.textContent = 'Buscando dirección…';
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
      headers: { 'Accept-Language': 'es' }
    });
    if (!res.ok) throw new Error('Servicio no disponible');
    const data = await res.json();
    if (data && data.display_name) {
      campoDireccion.value = data.display_name;
      if (avisoEl) avisoEl.textContent = '';
    } else {
      if (avisoEl) avisoEl.textContent = 'No se encontró una dirección para este punto; puedes escribirla manualmente.';
    }
  } catch (e) {
    if (avisoEl) avisoEl.textContent = 'No se pudo autocompletar la dirección (servicio externo no disponible). Puedes escribirla manualmente.';
  }
}

async function iniciarMapa() {
  await cargarLeaflet();
  document.getElementById('mapaRegistrar').classList.remove('map-skeleton');
  mapaReg = L.map('mapaRegistrar').setView([-2.9001, -79.0059], 13);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap © CARTO', maxZoom: 19
  }).addTo(mapaReg);

  mapaReg.on('click', (e) => {
    marcarUbicacion(e.latlng.lat, e.latlng.lng, 'Ubicación marcada');
  });

  // Si el navegador permite geolocalización, la usamos automáticamente
  // como punto de partida (el usuario aún puede hacer clic para ajustarla).
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        mapaReg.setView([latitude, longitude], 15);
        marcarUbicacion(latitude, longitude, 'Ubicación actual detectada');
      },
      () => { /* el usuario no dio permiso o no está disponible: se marca manualmente */ },
      { enableHighAccuracy: true, timeout: 8000 }
    );
  }
}

// ── Foto de la incidencia ──
let archivoFoto = null;

function quitarFoto() {
  archivoFoto = null;
  document.getElementById('foto').value = '';
  document.getElementById('fotoPreviewWrap').style.display = 'none';
  document.getElementById('fotoPreview').src = '';
}

document.getElementById('foto').addEventListener('change', () => {
  const f = document.getElementById('foto').files[0];
  if (!f) return;
  const tiposValidos = ['image/jpeg', 'image/jpg', 'image/png'];
  if (!tiposValidos.includes(f.type)) {
    mostrarToast('Solo se permiten imágenes JPG, JPEG o PNG.', 'warning');
    quitarFoto();
    return;
  }
  if (f.size > 5 * 1024 * 1024) {
    mostrarToast('La imagen supera el tamaño máximo de 5 MB.', 'warning');
    quitarFoto();
    return;
  }
  archivoFoto = f;
  const lector = new FileReader();
  lector.onload = (e) => {
    document.getElementById('fotoPreview').src = e.target.result;
    document.getElementById('fotoPreviewWrap').style.display = '';
  };
  lector.readAsDataURL(f);
});

async function subirFotoIncidencia(idIncidencia) {
  if (!archivoFoto) return;
  try {
    const fd = new FormData();
    fd.append('foto', archivoFoto);
    const res = await fetchAPI(`${API}/incidencias/${idIncidencia}/foto`, { method: 'POST', body: fd });
    if (!res.ok) {
      const d = await res.json().catch(() => ({}));
      mostrarToast(d.mensaje || 'La incidencia se registró, pero la foto no se pudo subir.', 'warning');
    }
  } catch (e) {
    mostrarToast('La incidencia se registró, pero la foto no se pudo subir.', 'warning');
  }
}

async function poblarSelect(url, selectId) {
  try {
    const r    = await fetchAPI(url);
    const datos = await r.json();
    const sel  = document.getElementById(selectId);
    datos.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d.id; opt.textContent = d.nombre;
      sel.appendChild(opt);
    });
  } catch(e) { console.error(`Error ${selectId}:`, e); }
}

async function cargarSubtipos() {
  const idTipo = document.getElementById('id_tipo').value;
  const selSub = document.getElementById('id_subtipo');
  selSub.innerHTML = '<option value="">Sin subtipo específico</option>';
  if (!idTipo) return;
  try {
    const r    = await fetchAPI(`${API}/catalogos/subtipos/${idTipo}`);
    const datos = await r.json();
    datos.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d.id; opt.textContent = d.nombre;
      selSub.appendChild(opt);
    });
  } catch(e) {}
}

function actualizarIncentivo() {
  const prio  = document.getElementById('prioridad').value;
  const monto = incentivosPorPrioridad[prio];
  const card  = document.getElementById('cardIncentivo');
  if (monto) {
    card.style.display = '';
    document.getElementById('montoIncentivo').textContent = `$${parseFloat(monto).toFixed(2)}`;
  } else {
    card.style.display = 'none';
  }
}

async function cargarIncentivos() {
  try {
    const r    = await fetchAPI(`${API}/catalogos/incentivos`);
    const datos = await r.json();
    datos.forEach(d => { incentivosPorPrioridad[d.prioridad] = d.monto; });
  } catch(e) {}
}

async function guardarIncidencia() {
  const btn = document.getElementById('btnGuardar');
  let ok = true;

  ['titulo','id_tipo','prioridad','fecha_ocurrencia','id_zona'].forEach(id => {
    document.getElementById(id).classList.remove('is-invalid','is-valid');
  });

  const titulo    = document.getElementById('titulo').value.trim();
  const id_tipo   = document.getElementById('id_tipo').value;
  const id_subtipo= document.getElementById('id_subtipo').value;
  const prioridad = document.getElementById('prioridad').value;
  const fecha     = document.getElementById('fecha_ocurrencia').value;
  const zona      = document.getElementById('id_zona').value;
  const lat       = document.getElementById('latitud').value;
  const lng       = document.getElementById('longitud').value;

  if (!titulo)    { document.getElementById('titulo').classList.add('is-invalid'); mostrarToast('El título es obligatorio.', 'warning'); ok = false; }
  if (!id_tipo)   { document.getElementById('id_tipo').classList.add('is-invalid'); if(ok) mostrarToast('Selecciona el tipo.', 'warning'); ok = false; }
  if (!prioridad) { document.getElementById('prioridad').classList.add('is-invalid'); if(ok) mostrarToast('Selecciona la prioridad.', 'warning'); ok = false; }
  if (!fecha)     { document.getElementById('fecha_ocurrencia').classList.add('is-invalid'); if(ok) mostrarToast('La fecha de ocurrencia es obligatoria.', 'warning'); ok = false; }
  if (!zona)      { document.getElementById('id_zona').classList.add('is-invalid'); if(ok) mostrarToast('Selecciona la zona.', 'warning'); ok = false; }
  if (!lat || !lng) { if(ok) mostrarToast('Haz clic en el mapa para marcar la ubicación.', 'warning'); ok = false; }

  if (!ok) return;

  btn.disabled = true;
  btn.innerHTML = '<span class="spin">⟳</span> Registrando…';

  const payload = {
    titulo,
    descripcion:         document.getElementById('descripcion').value.trim(),
    id_tipo:             parseInt(id_tipo),
    id_subtipo:          id_subtipo ? parseInt(id_subtipo) : null,
    prioridad,
    id_zona:             parseInt(zona),
    fecha_ocurrencia:    fecha,
    hora_ocurrencia:     (document.getElementById('hora_ocurrencia').value || '').slice(0, 5) || null,
    latitud:             parseFloat(lat),
    longitud:            parseFloat(lng),
    direccion_texto:     document.getElementById('direccion_texto').value.trim() || null,
    reportante_nombre:   document.getElementById('reportante_nombre').value.trim(),
    reportante_contacto: document.getElementById('reportante_contacto').value.trim(),
  };

  try {
    const res = await fetchAPI(`${API}/incidencias`, {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    const contentType = res.headers.get('content-type') || '';
    const data = contentType.includes('application/json')
      ? await res.json()
      : { mensaje: await res.text() };

    if (res.ok && data.ok) {
      mostrarToast(data.mensaje || 'Incidencia registrada correctamente.', 'success');
      if (data.id && archivoFoto) await subirFotoIncidencia(data.id);
      setTimeout(() => window.location.href = 'incidencias.html', 1500);
    } else {
      const errores = data.errors
        ? Object.values(data.errors).flat().join(' ')
        : null;
      mostrarToast(errores || data.mensaje || data.message || `Error ${res.status} al registrar.`, 'danger');
    }
  } catch(e) {
    console.error('Error al registrar incidencia:', e);
    mostrarToast('No se pudo conectar con el backend. Verifica que Docker esté iniciado.', 'danger');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '✓ Registrar incidencia';
  }
}

// Init - Carga optimizada en paralelo
async function inicializarFormulario() {
  // Cargar todos los catálogos en paralelo para mayor velocidad
  await Promise.all([
    poblarSelect(`${API}/catalogos/tipos`, 'id_tipo'),
    poblarSelect(`${API}/catalogos/zonas`, 'id_zona'),
    cargarIncentivos()
  ]);

  // Configurar fecha actual
  document.getElementById('fecha_ocurrencia').value = new Date().toISOString().split('T')[0];

  // Pre-llenar nombre del usuario actual
  const uActual = getUsuario();
  if (uActual) {
    const elNombre = document.getElementById('reportante_nombre');
    if (elNombre) elNombre.value = uActual.nombre;
  }
}

alEntrarEnVista(document.getElementById('mapaRegistrar'), iniciarMapa);
inicializarFormulario();
