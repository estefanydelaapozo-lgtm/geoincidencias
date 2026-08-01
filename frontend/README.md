# GeoIncidencias â€” Frontend v2.0

Sistema de gestiÃ³n geoespacial de incidencias. RediseÃ±o completo del frontend con identidad visual nueva.

---

## Estructura de archivos

```
frontend/
â”œâ”€â”€ login.html          â†’ Pantalla de acceso / registro
â”œâ”€â”€ index.html          â†’ Dashboard con mapa y estadÃ­sticas
â”œâ”€â”€ incidencias.html    â†’ Listado y gestiÃ³n de incidencias
â”œâ”€â”€ registrar.html      â†’ Formulario de nueva incidencia
â”œâ”€â”€ mis-apoyos.html     â†’ Incidencias disponibles y mis apoyos
â”œâ”€â”€ reportes.html       â†’ GrÃ¡ficas y anÃ¡lisis estadÃ­stico
â”œâ”€â”€ admin.html          â†’ Panel de administraciÃ³n (solo admin)
â”œâ”€â”€ historial.html      â†’ Historial de actividad (solo admin)
â”œâ”€â”€ perfil.html         â†’ Mi perfil y cambio de contraseÃ±a
â”œâ”€â”€ styles.css          â†’ Design system completo (paleta, componentes)
â””â”€â”€ js/
    â”œâ”€â”€ auth-guard.js   â†’ â† CONFIGURA AQUÃ LA URL DEL BACKEND
    â”œâ”€â”€ sidebar.js      â†’ Barra lateral compartida
    â”œâ”€â”€ incidencias.js  â†’ LÃ³gica del listado de incidencias
    â”œâ”€â”€ registrar.js    â†’ LÃ³gica del formulario de registro
    â”œâ”€â”€ mis-apoyos.js   â†’ LÃ³gica de apoyos
    â”œâ”€â”€ reportes.js     â†’ GrÃ¡ficas con Chart.js
    â”œâ”€â”€ admin.js        â†’ LÃ³gica del panel admin
    â””â”€â”€ historial.js    â†’ LÃ³gica del historial
```

---

## âš¡ ConfiguraciÃ³n rÃ¡pida

### 1. Configura la URL del backend

Abre `js/auth-guard.js` y ajusta la primera lÃ­nea:

```js
const API = 'http://localhost:8000/api';
// Si el backend estÃ¡ desplegado, cambia a su URL real:
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

Usa cualquier servidor estÃ¡tico. OpciÃ³n mÃ¡s simple:

```bash
# Con Python
python3 -m http.server 5500

# Con Node.js (npx)
npx serve .

# Con VS Code â†’ instala "Live Server" y clic derecho en login.html â†’ Open with Live Server
```

Luego abre: **http://localhost:5500/login.html**

---

## ðŸ”‘ Endpoints del backend que consume el frontend

| MÃ©todo | Ruta | DescripciÃ³n |
|--------|------|-------------|
| POST | /api/auth/login | Iniciar sesiÃ³n |
| POST | /api/auth/registro | Crear cuenta |
| POST | /api/auth/cambiar-password | Cambiar contraseÃ±a |
| GET  | /api/incidencias | Listar (con filtros y paginaciÃ³n) |
| POST | /api/incidencias | Crear incidencia |
| GET  | /api/incidencias/{id} | Detalle de incidencia |
| PUT  | /api/incidencias/{id} | Editar incidencia |
| DELETE | /api/incidencias/{id} | Eliminar incidencia |
| POST | /api/incidencias/{id}/aprobar | Aprobar incidencia (admin) |
| POST | /api/incidencias/{id}/rechazar | Rechazar incidencia (admin) |
| GET  | /api/incidencias/mapa | Marcadores del mapa |
| GET  | /api/incidencias/pendientes-aprobacion | Pendientes (admin) |
| GET  | /api/catalogos/tipos | CatÃ¡logo de tipos |
| GET  | /api/catalogos/subtipos/{id} | Subtipos por tipo |
| GET  | /api/catalogos/estados | CatÃ¡logo de estados |
| GET  | /api/catalogos/zonas | CatÃ¡logo de zonas |
| GET  | /api/catalogos/incentivos | Incentivos por prioridad |
| POST | /api/apoyos | Registrar apoyo |
| GET  | /api/apoyos/mis-apoyos | Mis apoyos |
| GET  | /api/apoyos/mi-saldo | Saldo y estadÃ­sticas |
| GET  | /api/apoyos/pendientes | Apoyos pendientes (admin) |
| POST | /api/apoyos/{id}/aprobar | Aprobar apoyo (admin) |
| POST | /api/apoyos/{id}/rechazar | Rechazar apoyo (admin) |
| GET  | /api/dashboard/resumen | KPIs del dashboard |
| GET  | /api/dashboard/por-tipo | Incidencias por categorÃ­a |
| GET  | /api/dashboard/ultimas | Ãšltimas incidencias |
| GET  | /api/reportes/resumen | Resumen del perÃ­odo |
| GET  | /api/reportes/por-categoria | Por categorÃ­a |
| GET  | /api/reportes/por-estado | Por estado |
| GET  | /api/reportes/tendencia | Tendencia temporal |
| GET  | /api/reportes/por-responsable | Por responsable |
| GET  | /api/historial | Historial de actividad (admin) |
| GET  | /api/admin/usuarios | Lista de usuarios (admin) |
| PUT  | /api/usuarios/{id} | Editar usuario |

---

## ðŸŽ¨ Design System

- **Fondo base:** `#060B18` (azul noche profundo)
- **Superficie:** `#0C1628` / `#0E1B2E` / `#112038`
- **Acento primario:** `#00D4FF` (cyan elÃ©ctrico)
- **Alerta / peligro:** `#FF3B6B` (coral)
- **Ã‰xito:** `#00E5A0` (verde menta)
- **Advertencia:** `#F59E0B` (Ã¡mbar)
- **TipografÃ­a display:** Space Grotesk
- **TipografÃ­a body:** Inter
- **TipografÃ­a mono:** Space Mono

---

## Base de datos en la nube

```
Las credenciales reales no se suben al repositorio; se configuran en backend-laravel/.env (local, ignorado por git).
```

---

## CORS en Laravel

AsegÃºrate de que `config/cors.php` incluya el origen del frontend:

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

