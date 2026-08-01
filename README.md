# 🗺 GeoIncidencias — Proyecto Completo v2.0

Sistema de gestión geoespacial de incidencias con backend Laravel 11 + frontend rediseñado.

---

## 📁 Estructura del proyecto

```
proyecto-completo/
├── frontend/           ← Frontend HTML/JS (nuevo diseño)
│   ├── login.html
│   ├── index.html      (Dashboard)
│   ├── incidencias.html
│   ├── registrar.html
│   ├── mis-apoyos.html
│   ├── reportes.html
│   ├── admin.html
│   ├── historial.html
│   ├── perfil.html
│   ├── styles.css
│   └── js/
│       ├── auth-guard.js   ← CONFIGURAR URL AQUÍ
│       └── ...
└── backend-laravel/    ← API REST Laravel 11 + Sanctum
    ├── app/
    ├── database/
    ├── routes/api.php
    ├── .env            ← credenciales BD en la nube
    └── composer.json
```

---

## ⚡ Instalación rápida

### 1. Backend Laravel

```bash
cd backend-laravel

# Instalar dependencias
composer install

# Generar clave de aplicación
php artisan key:generate

# Las credenciales de BD ya están en .env
# Si quieres migrar y sembrar datos de prueba:
php artisan migrate:fresh --seed

# Iniciar servidor
php artisan serve
# → API disponible en http://localhost:8000/api
```

### 2. Frontend

Abre `frontend/js/auth-guard.js` y verifica la URL:
```js
const API = 'http://localhost:8000/api';
```

Luego sirve el frontend con cualquier servidor estático:
```bash
cd frontend
python3 -m http.server 5500
# Abre: http://localhost:5500/login.html
```

---

## 🔐 Usuarios de prueba (post-seed)

| Correo | Contraseña | Rol |
|--------|-----------|-----|
| admin@geoincidencias.com | 123456 | Admin |
| cmendoza@empresa.com | 123456 | Usuario |
| mgonzalez@empresa.com | 123456 | Usuario |

---

## 🗄 Base de datos en la nube

```
Host:     MYSQL5036.site4now.net
Puerto:   3306
BD:       db_acb211_dbweb
Usuario:  acb211_dbweb
Password: daniel2001@
```

---

## 🎨 Diseño nuevo (frontend v2)

| Elemento | Valor |
|----------|-------|
| Fondo | `#060B18` azul noche |
| Acento | `#00D4FF` cyan eléctrico |
| Alerta | `#FF3B6B` coral |
| Éxito | `#00E5A0` verde menta |
| Tipografía | Space Grotesk + Inter + Space Mono |
| Mapa | Leaflet con tiles dark CartoDB |
| Gráficas | Chart.js 4 |
| Auth | Laravel Sanctum (token) |

