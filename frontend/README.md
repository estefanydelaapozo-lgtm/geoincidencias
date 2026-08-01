# GeoIncidencias — Frontend v2.0

Sistema de gestión geoespacial de incidencias. Rediseño completo del frontend con identidad visual nueva.

---

## Estructura de archivos

```
frontend/
├── login.html          → Pantalla de acceso / registro
├── index.html          → Dashboard con mapa y estadísticas
├── incidencias.html    → Listado y gestión de incidencias
├── registrar.html      → Formulario de nueva incidencia
├── mis-apoyos.html     → Incidencias disponibles y mis apoyos
├── reportes.html       → Gráficas y análisis estadístico
├── admin.html          → Panel de administración (solo admin)
├── historial.html      → Historial de actividad (solo admin)
├── perfil.html         → Mi perfil y cambio de contraseña
├── styles.css          → Design system completo (paleta, componentes)
└── js/
    ├── auth-guard.js   → ← CONFIGURA AQUÍ LA URL DEL BACKEND
    ├── sidebar.js      → Barra lateral compartida
    ├── incidencias.js  → Lógica del listado de incidencias
    ├── registrar.js    → Lógica del formulario de registro
    ├── mis-apoyos.js   → Lógica de apoyos
    ├── reportes.js     → Gráficas con Chart.js
    ├── admin.js        → Lógica del panel admin
    └── historial.js    → Lógica del historial
```

---

## ⚡ Configuración rápida

### 1. Configura la URL del backend

Abre `js/auth-guard.js` y ajusta la primera línea:

```js
const API = 'http://localhost:8000/api';
// Si el backend está desplegado, cambia a su URL real:
// const API = 'https://tudominio.com/api';
```

### 2. Levanta el backend Laravel

```bash
cd backend-laravel
cp .env.backend .env          # copia las credenciales de BD
composer install
php artisan key:generate
php artisan jwt:secret        # si usas JWT
php artisan migrate --seed    # crea tablas y datos de prueba
php artisan serve             # inicia en http://localhost:8000
```

### 3. Abre el frontend

Usa cualquier servidor estático. Opción más simple:

```bash
# Con Python
python3 -m http.server 5500

# Con Node.js (npx)
npx serve .

# Con VS Code → instala "Live Server" y clic derecho en login.html → Open with Live Server
```

Luego abre: **http://localhost:5500/login.html**

---

## 🔑 Endpoints del backend que consume el frontend

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | /api/auth/login | Iniciar sesión |
| POST | /api/auth/registro | Crear cuenta |
| POST | /api/auth/cambiar-password | Cambiar contraseña |
| GET  | /api/incidencias | Listar (con filtros y paginación) |
| POST | /api/incidencias | Crear incidencia |
| GET  | /api/incidencias/{id} | Detalle de incidencia |
| PUT  | /api/incidencias/{id} | Editar incidencia |
| DELETE | /api/incidencias/{id} | Eliminar incidencia |
| POST | /api/incidencias/{id}/aprobar | Aprobar incidencia (admin) |
| POST | /api/incidencias/{id}/rechazar | Rechazar incidencia (admin) |
| GET  | /api/incidencias/mapa | Marcadores del mapa |
| GET  | /api/incidencias/pendientes-aprobacion | Pendientes (admin) |
| GET  | /api/catalogos/tipos | Catálogo de tipos |
| GET  | /api/catalogos/subtipos/{id} | Subtipos por tipo |
| GET  | /api/catalogos/estados | Catálogo de estados |
| GET  | /api/catalogos/zonas | Catálogo de zonas |
| GET  | /api/catalogos/incentivos | Incentivos por prioridad |
| POST | /api/apoyos | Registrar apoyo |
| GET  | /api/apoyos/mis-apoyos | Mis apoyos |
| GET  | /api/apoyos/mi-saldo | Saldo y estadísticas |
| GET  | /api/apoyos/pendientes | Apoyos pendientes (admin) |
| POST | /api/apoyos/{id}/aprobar | Aprobar apoyo (admin) |
| POST | /api/apoyos/{id}/rechazar | Rechazar apoyo (admin) |
| GET  | /api/dashboard/resumen | KPIs del dashboard |
| GET  | /api/dashboard/por-tipo | Incidencias por categoría |
| GET  | /api/dashboard/ultimas | Últimas incidencias |
| GET  | /api/reportes/resumen | Resumen del período |
| GET  | /api/reportes/por-categoria | Por categoría |
| GET  | /api/reportes/por-estado | Por estado |
| GET  | /api/reportes/tendencia | Tendencia temporal |
| GET  | /api/reportes/por-responsable | Por responsable |
| GET  | /api/historial | Historial de actividad (admin) |
| GET  | /api/admin/usuarios | Lista de usuarios (admin) |
| PUT  | /api/usuarios/{id} | Editar usuario |

---

## 🎨 Design System

- **Fondo base:** `#060B18` (azul noche profundo)
- **Superficie:** `#0C1628` / `#0E1B2E` / `#112038`
- **Acento primario:** `#00D4FF` (cyan eléctrico)
- **Alerta / peligro:** `#FF3B6B` (coral)
- **Éxito:** `#00E5A0` (verde menta)
- **Advertencia:** `#F59E0B` (ámbar)
- **Tipografía display:** Space Grotesk
- **Tipografía body:** Inter
- **Tipografía mono:** Space Mono

---

## Base de datos en la nube

```
Host:     MYSQL5036.site4now.net
Puerto:   3306
BD:       db_acb211_dbweb
Usuario:  acb211_dbweb
Password: daniel2001@
```

---

## CORS en Laravel

Asegúrate de que `config/cors.php` incluya el origen del frontend:

```php
'allowed_origins' => [
    'http://localhost:5500',
    'http://127.0.0.1:5500',
    'null', // para file:// en algunos navegadores
],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```
