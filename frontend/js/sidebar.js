// sidebar.js — inyecta un sidebar con navegación agrupada y propia por rol
// (no es el mismo menú mostrando/ocultando enlaces: cada rol arma su propio
// árbol de secciones, con su acento de color e identidad).

const SIDEBAR_ROLES = {
  admin:      { color: '#D97706', etiqueta: 'Panel de administrador', icono: '👑' },
  supervisor: { color: '#6D28D9', etiqueta: 'Centro de supervisión',  icono: '🧭' },
  tecnico:    { color: '#0EA5E9', etiqueta: 'App de campo — Técnico', icono: '🔧' },
  usuario:    { color: '#16A34A', etiqueta: 'Mi cuenta ciudadana',    icono: '👤' },
};
const SIDEBAR_INSTITUCIONALES = ['policia','bomberos','salud','electrica','agua','obras_publicas','medio_ambiente'];

function sidebarEnlace(activa, href, icono, texto) {
  const on = activa ? 'active' : '';
  return `<a class="nav-link ${on}" href="${href}" data-page="${href}"><span class="nav-icon">${icono}</span> ${texto}</a>`;
}
function sidebarGrupo(titulo, enlacesHtml) {
  return `<div class="nav-section-label">${titulo}</div>${enlacesHtml}`;
}

function construirMenuPorRol(rol, paginaActiva) {
  const e = (href, icono, texto) => sidebarEnlace(paginaActiva === href.replace('.html',''), href, icono, texto);

  if (rol === 'admin') {
    return sidebarGrupo('Principal', e('index.html','⬡','Dashboard'))
      + sidebarGrupo('Incidencias', e('admin.html','⊛','Aprobaciones') + e('incidencias.html','◈','Todas las incidencias'))
      + sidebarGrupo('Personas', e('usuarios.html','◉','Usuarios y roles'))
      + sidebarGrupo('Análisis', e('reportes.html','▣','Reportes') + e('historial.html','⟳','Auditoría / historial'))
      + sidebarGrupo('Otros', e('plan-negocio.html','◇','Plan de negocio'))
      + sidebarGrupo('Cuenta', e('perfil.html','⊙','Perfil'));
  }
  if (rol === 'supervisor') {
    return sidebarGrupo('Principal', e('index.html','⬡','Dashboard'))
      + sidebarGrupo('Supervisión', e('supervisor.html','🧭','Panel Supervisor') + e('incidencias.html','◈','Incidencias') + e('institucion.html','⌂','Gestión operativa'))
      + sidebarGrupo('Análisis', e('reportes.html','▣','Reportes'))
      + sidebarGrupo('Cuenta', e('perfil.html','⊙','Perfil'));
  }
  if (rol === 'tecnico') {
    return sidebarGrupo('Mi trabajo', e('index.html','⬡','Dashboard') + e('institucion.html','🛠','Mis incidencias'))
      + sidebarGrupo('Cuenta', e('perfil.html','⊙','Perfil'));
  }
  if (SIDEBAR_INSTITUCIONALES.includes(rol)) {
    return sidebarGrupo('Mi institución', e('index.html','⬡','Dashboard') + e('institucion.html','⌂','Panel institucional') + e('incidencias.html','◈','Incidencias'))
      + sidebarGrupo('Cuenta', e('perfil.html','⊙','Perfil'));
  }
  // Ciudadano (por defecto)
  return sidebarGrupo('Inicio', e('index.html','⬡','Inicio'))
    + sidebarGrupo('Mis incidencias', e('registrar.html','✛','Reportar nueva') + e('incidencias.html','◈','Mis incidencias') + e('mis-apoyos.html','◎','Mis apoyos'))
    + sidebarGrupo('Cuenta', e('perfil.html','⊙','Perfil'));
}

function inyectarSidebar(paginaActiva) {
  const u = (typeof getUsuario === 'function') ? getUsuario() : null;
  const rol = u?.rol || 'usuario';
  const cfg = SIDEBAR_ROLES[rol] || (SIDEBAR_INSTITUCIONALES.includes(rol)
    ? { color: '#0891B2', etiqueta: 'Panel institucional', icono: '⌂' }
    : SIDEBAR_ROLES.usuario);

  document.documentElement.style.setProperty('--sidebar-accent', cfg.color);

  const sidebar = `
  <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()" style="
    display:none;position:fixed;top:16px;left:16px;z-index:300;
    background:var(--bg-card);border:1px solid var(--border);
    color:var(--text-primary);width:40px;height:40px;border-radius:10px;
    font-size:1.2rem;cursor:pointer;align-items:center;justify-content:center;
  ">☰</button>

  <aside class="sidebar" id="sidebar" style="--role-accent:${cfg.color};">
    <a class="sidebar-logo" href="index.html">
      <div class="logo-icon" style="background:linear-gradient(135deg,${cfg.color},var(--secondary));">📍</div>
      <div>
        <div class="logo-text">GeoIncidencias</div>
        <div class="logo-sub">Control Center</div>
      </div>
    </a>
    <div style="margin:2px 16px 10px;padding:8px 12px;border-radius:10px;background:${cfg.color}14;border:1px solid ${cfg.color}30;font-size:.72rem;font-weight:700;color:${cfg.color};display:flex;align-items:center;gap:6px;">
      <span>${cfg.icono}</span><span>${cfg.etiqueta}</span>
    </div>

    <nav class="sidebar-nav">
      ${construirMenuPorRol(rol, paginaActiva)}
    </nav>

    <div class="sidebar-footer">
      <div class="user-chip" id="userChip" role="button" tabindex="0" title="Abrir mi perfil" onclick="location.href='perfil.html'" onkeydown="if(event.key==='Enter')location.href='perfil.html'">
        <div class="user-avatar" id="avatarInicial" style="background:linear-gradient(135deg,${cfg.color},var(--secondary));">U</div>
        <div class="user-info">
          <div class="user-name" id="nombreUsuarioActual">Cargando...</div>
          <div class="user-role" id="rolUsuarioActual">—</div>
        </div>
        <div class="notif-dot" id="badgeNotif">0</div>
      </div>
      <button id="btnCerrarSesion" class="logout-btn" style="
        width:100%;margin-top:8px;
        background:transparent;border:1px solid rgba(225,29,72,.25);
        border-radius:8px;padding:8px;color:var(--coral);
        font-family:'Plus Jakarta Sans',sans-serif;font-size:.8rem;
        font-weight:600;cursor:pointer;transition:all .2s;
        display:flex;align-items:center;justify-content:center;gap:6px;
      " onmouseover="this.style.background='var(--coral-dim)'" onmouseout="this.style.background='transparent'">
        ↩ Cerrar sesión
      </button>
    </div>
  </aside>
  `;
  document.body.insertAdjacentHTML('afterbegin', sidebar);

  // Responsive toggle
  if (window.innerWidth <= 1024) {
    document.getElementById('menuToggle').style.display = 'flex';
  }
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}
