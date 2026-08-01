// GeoIncidencias - conexión robusta con Docker
const API = '/api';

function getToken()   { return localStorage.getItem('gi_token'); }
function getUsuario() { try { return JSON.parse(localStorage.getItem('gi_usuario')); } catch(e) { return null; } }

function cerrarSesion() {
  localStorage.removeItem('gi_token');
  localStorage.removeItem('gi_usuario');
  window.location.href = 'login.html';
}

function exigirSesion() {
  if (!getToken() || !getUsuario()) window.location.href = 'login.html';
}

function exigirAdmin() {
  exigirSesion();
  const u = getUsuario();
  if (!u || u.rol !== 'admin') window.location.href = 'index.html';
}

async function fetchAPI(url, opciones = {}) {
  const token = getToken();
  const isFormData = opciones.body instanceof FormData;
  const headers = {
    'Accept': 'application/json',
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...(opciones.headers || {}),
  };

  let res;
  try {
    res = await fetch(url, { ...opciones, headers, cache: 'no-store', redirect: 'follow' });
  } catch (error) {
    console.error('No se pudo contactar al backend por el proxy de Docker.', error);
    throw new Error('No se pudo conectar con el backend Docker.');
  }
  if (res.type === 'opaqueredirect' || (res.status >= 300 && res.status < 400)) {
    cerrarSesion();
    throw new Error('La sesión venció. Inicia sesión nuevamente.');
  }
  if (res.status === 401) { cerrarSesion(); throw new Error('Sesión expirada'); }
  if (res.status === 403) throw new Error('Acceso denegado');
  return res;
}

function inicializarBarraUsuario() {
  const u = getUsuario();
  if (!u) return;
  const nombreEl = document.getElementById('nombreUsuarioActual');
  if (nombreEl) nombreEl.textContent = `${u.nombre}`;
  const rolEl = document.getElementById('rolUsuarioActual');
  if (rolEl) rolEl.textContent = u.rol_detalle?.nombre || ({admin:'Administrador',usuario:'Ciudadano',policia:'Policía',bomberos:'Bomberos',salud:'Salud / Emergencias',electrica:'Empresa Eléctrica',agua:'Agua Potable',obras_publicas:'Obras Públicas',medio_ambiente:'Medio Ambiente',supervisor:'Supervisor',tecnico:'Técnico general'}[u.rol] || u.rol);
  const avatarEl = document.getElementById('avatarInicial');
  if (avatarEl) avatarEl.textContent = (u.nombre || 'U').charAt(0).toUpperCase();
  if (u.rol !== 'admin') document.querySelectorAll('.solo-admin').forEach(el => el.style.display = 'none');
  const btnLogout = document.getElementById('btnCerrarSesion');
  if (btnLogout) btnLogout.addEventListener('click', cerrarSesion);
  cargarContadorNotificaciones();
  iniciarNotificacionesEnVivo();
  const pagActual = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link[data-page]').forEach(link => {
    if (link.dataset.page === pagActual) link.classList.add('active');
  });
}

async function cargarContadorNotificaciones() {
  try {
    const r = await fetchAPI(`${API}/notificaciones/no-leidas`);
    const d = await r.json();
    const badge = document.getElementById('badgeNotif');
    if (badge) {
      badge.textContent = d.total || 0;
      badge.style.display = d.total > 0 ? 'flex' : 'none';
    }
  } catch(e) {}
}

// Carga una imagen protegida por token (ej. foto de una incidencia) y la
// asigna a un <img>. Una etiqueta <img src="..."> normal NO puede enviar el
// header Authorization, así que sin esto la imagen sale rota (401).
async function cargarImagenProtegida(url, imgEl) {
  const res = await fetchAPI(url);
  if (!res.ok) throw new Error('No se pudo cargar la imagen');
  const blob = await res.blob();
  imgEl.src = URL.createObjectURL(blob);
}

function mostrarToast(msg, tipo = 'success') {
  const icons = { success: '✓', danger: '✕', warning: '⚠' };
  const colors = { success: '#16A34A', danger: '#E11D48', warning: '#D97706' };
  const toast = document.createElement('div');
  toast.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;background:#FFFFFF;border:1px solid rgba(15,23,42,.08);color:#0F172A;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:12px;box-shadow:0 10px 32px rgba(15,23,42,.14);font-family:'Plus Jakarta Sans','Inter',sans-serif;font-size:.9rem;animation:slideIn .3s ease;max-width:420px;border-left:3px solid ${colors[tipo]};`;
  toast.innerHTML = `<span style="color:${colors[tipo]};font-weight:700;font-size:1rem;">${icons[tipo]||'●'}</span><span>${msg}</span>`;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.animation='fadeOut .3s ease forwards'; setTimeout(()=>toast.remove(),300); }, 5000);
}

// ── Reemplazo de prompt()/confirm() nativos del navegador por modales propios
// del sistema, con el mismo estilo que el resto de la interfaz. ──
function confirmarAccion({ titulo = '¿Confirmas esta acción?', mensaje = '', textoBoton = 'Confirmar', peligro = false } = {}) {
  return new Promise((resolve) => {
    const id = 'confirmModal_' + Date.now();
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal-backdrop open" id="${id}">
        <div class="modal-box" style="max-width:400px;">
          <div class="modal-head"><h3>${titulo}</h3><button class="btn-close-modal" data-cancel>✕</button></div>
          <div class="modal-body"><p style="color:var(--text-secondary);font-size:.88rem;margin:0;">${mensaje}</p></div>
          <div class="modal-foot">
            <button class="btn btn-ghost btn-sm" data-cancel>Cancelar</button>
            <button class="btn ${peligro ? 'btn-coral' : 'btn-primary'} btn-sm" data-ok>${textoBoton}</button>
          </div>
        </div>
      </div>`);
    const el = document.getElementById(id);
    const cerrar = (v) => { el.remove(); resolve(v); };
    el.querySelectorAll('[data-cancel]').forEach(b => b.addEventListener('click', () => cerrar(false)));
    el.addEventListener('click', (e) => { if (e.target === el) cerrar(false); });
    el.querySelector('[data-ok]').addEventListener('click', () => cerrar(true));
  });
}

function pedirTexto({ titulo = 'Escribe un comentario', mensaje = '', placeholder = '', minLen = 0, textoBoton = 'Confirmar', peligro = false } = {}) {
  return new Promise((resolve) => {
    const id = 'promptModal_' + Date.now();
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal-backdrop open" id="${id}">
        <div class="modal-box" style="max-width:440px;">
          <div class="modal-head"><h3>${titulo}</h3><button class="btn-close-modal" data-cancel>✕</button></div>
          <div class="modal-body">
            ${mensaje ? `<p style="color:var(--text-secondary);font-size:.85rem;margin:0 0 10px;">${mensaje}</p>` : ''}
            <textarea class="form-control" rows="3" placeholder="${placeholder}" data-input></textarea>
            <div style="display:none;color:var(--coral);font-size:.75rem;margin-top:6px;" data-error></div>
          </div>
          <div class="modal-foot">
            <button class="btn btn-ghost btn-sm" data-cancel>Cancelar</button>
            <button class="btn ${peligro ? 'btn-coral' : 'btn-primary'} btn-sm" data-ok>${textoBoton}</button>
          </div>
        </div>
      </div>`);
    const el = document.getElementById(id);
    const input = el.querySelector('[data-input]');
    const err = el.querySelector('[data-error]');
    const cerrar = (v) => { el.remove(); resolve(v); };
    el.querySelectorAll('[data-cancel]').forEach(b => b.addEventListener('click', () => cerrar(null)));
    el.addEventListener('click', (e) => { if (e.target === el) cerrar(null); });
    el.querySelector('[data-ok]').addEventListener('click', () => {
      const val = input.value.trim();
      if (val.length < minLen) { err.textContent = `Escribe al menos ${minLen} caracteres.`; err.style.display = 'block'; return; }
      cerrar(val);
    });
    setTimeout(() => input.focus(), 50);
  });
}



let notifTimer = null;
let ultimoTotalNotif = null;
function iniciarNotificacionesEnVivo() {
  if (notifTimer) return;
  const consultar = async () => {
    try {
      const r = await fetchAPI(`${API}/notificaciones/no-leidas`);
      const d = await r.json();
      const total = Number(d.total || 0);
      if (ultimoTotalNotif !== null && total > ultimoTotalNotif) {
        mostrarToast(`Tienes ${total - ultimoTotalNotif} notificación(es) nueva(s).`, 'warning');
      }
      ultimoTotalNotif = total;
      const badge = document.getElementById('badgeNotif');
      if (badge) { badge.textContent = total; badge.style.display = total > 0 ? 'flex' : 'none'; }
    } catch (_) {}
  };
  notifTimer = setInterval(consultar, 30000);
}
