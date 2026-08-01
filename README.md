# ðŸ—º GeoIncidencias â€” Proyecto Completo v2.0

Sistema de gestiÃ³n geoespacial de incidencias con backend Laravel 11 + frontend rediseÃ±ado.

---

## ðŸ“ Estructura del proyecto

```
proyecto-completo/
â”œâ”€â”€ frontend/           â† Frontend HTML/JS (nuevo diseÃ±o)
â”‚   â”œâ”€â”€ login.html
â”‚   â”œâ”€â”€ index.html      (Dashboard)
â”‚   â”œâ”€â”€ incidencias.html
â”‚   â”œâ”€â”€ registrar.html
â”‚   â”œâ”€â”€ mis-apoyos.html
â”‚   â”œâ”€â”€ reportes.html
â”‚   â”œâ”€â”€ admin.html
â”‚   â”œâ”€â”€ historial.html
â”‚   â”œâ”€â”€ perfil.html
â”‚   â”œâ”€â”€ styles.css
â”‚   â””â”€â”€ js/
â”‚       â”œâ”€â”€ auth-guard.js   â† CONFIGURAR URL AQUÃ
â”‚       â””â”€â”€ ...
â””â”€â”€ backend-laravel/    â† API REST Laravel 11 + Sanctum
    â”œâ”€â”€ app/
    â”œâ”€â”€ database/
    â”œâ”€â”€ routes/api.php
    â”œâ”€â”€ .env            â† credenciales BD en la nube
    â””â”€â”€ composer.json
```

---

## âš¡ InstalaciÃ³n rÃ¡pida

### 1. Backend Laravel

```bash
cd backend-laravel

# Instalar dependencias
composer install

# Generar clave de aplicaciÃ³n
php artisan key:generate

# Las credenciales de BD ya estÃ¡n en .env
# Si quieres migrar y sembrar datos de prueba:
php artisan migrate:fresh --seed

# Iniciar servidor
php artisan serve
# â†’ API disponible en http://localhost:8000/api
```

### 2. Frontend

Abre `frontend/js/auth-guard.js` y verifica la URL:
```js
const API = 'http://localhost:8000/api';
```

Luego sirve el frontend con cualquier servidor estÃ¡tico:
```bash
cd frontend
python3 -m http.server 5500
# Abre: http://localhost:5500/login.html
```

---

## ðŸ” Usuarios de prueba (post-seed)

| Correo | ContraseÃ±a | Rol |
|--------|-----------|-----|
| admin@geoincidencias.com | 123456 | Admin |
| cmendoza@empresa.com | 123456 | Usuario |
| mgonzalez@empresa.com | 123456 | Usuario |

---

## ðŸ—„ Base de datos en la nube

```
Las credenciales reales no se suben al repositorio; se configuran en backend-laravel/.env (local, ignorado por git).
```

---

## ðŸŽ¨ DiseÃ±o nuevo (frontend v2)

| Elemento | Valor |
|----------|-------|
| Fondo | `#060B18` azul noche |
| Acento | `#00D4FF` cyan elÃ©ctrico |
| Alerta | `#FF3B6B` coral |
| Ã‰xito | `#00E5A0` verde menta |
| TipografÃ­a | Space Grotesk + Inter + Space Mono |
| Mapa | Leaflet con tiles dark CartoDB |
| GrÃ¡ficas | Chart.js 4 |
| Auth | Laravel Sanctum (token) |


