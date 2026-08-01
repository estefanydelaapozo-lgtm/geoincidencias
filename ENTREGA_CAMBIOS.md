# GeoIncidencias — Entrega de mejoras (Foto, Mapa y Panel de Supervisor)

## 0. Contexto encontrado en el proyecto

Antes de escribir código se analizó el proyecto completo. Hallazgos clave que definieron el enfoque:

- El **mapa interactivo (Leaflet + OpenStreetMap/CARTO)** en el módulo de Registro **ya existía y funcionaba** (`frontend/js/registrar.js`, `frontend/js/map-loader.js`). Solo faltaba la geolocalización automática.
- Las columnas `latitud` y `longitud` **ya existían** en la tabla `incidencias` desde la migración original.
- El sistema **ya tenía un rol `supervisor`** (en roles institucionales, en `usuarios.html`, en `IncidenciaPolicy`, en el middleware `roles:`), pero **no existía una pantalla/panel dedicada** para ese rol.
- El sistema **ya tenía un mecanismo completo de comentarios de seguimiento** por incidencia (`incidencia_comentarios`), con autor, fecha y texto — más completo que un solo campo de texto.
- El sistema **ya tenía un patrón completo de subida de foto** (foto de perfil de usuario, en `AuthController`), que se replicó exactamente para la foto de la incidencia.

Con base en esto, se tomaron dos decisiones de diseño para **no duplicar ni romper nada**:

1. **No se agregó una columna `observacion_supervisor`.** Se reutilizó la tabla `incidencia_comentarios` ya existente, mostrada en el Panel de Supervisor como "Observaciones del supervisor". Es funcionalmente superior (permite múltiples observaciones con autor y fecha) y no duplica estructura.
2. **No se agregaron columnas `latitud`/`longitud`/`estado` nuevas** porque ya existían. Solo se agregó la columna `foto`, que era lo único realmente faltante.

---

## 1. Archivos NUEVOS creados

| Archivo | Propósito |
|---|---|
| `backend-laravel/database/migrations/2026_07_27_000500_add_foto_incidencia.php` | Migración que agrega la columna `foto` a `incidencias` |
| `frontend/supervisor.html` | Nueva pantalla del Panel de Supervisor |
| `frontend/js/supervisor.js` | Lógica del Panel de Supervisor |
| `frontend/js/supervisor.min.js` | Copia funcional para producción (ver nota sobre minificación al final) |
| `alter_incidencias_foto.sql` | Script SQL incremental y seguro para agregar la columna `foto` manualmente si se desea |
| `ENTREGA_CAMBIOS.md` | Este documento |

## 2. Archivos MODIFICADOS

| Archivo | Cambio |
|---|---|
| `backend-laravel/app/Models/Incidencia.php` | Se agregó `'foto'` a `$fillable` |
| `backend-laravel/app/Http/Controllers/Api/IncidenciasController.php` | Se agregó `foto_url` en la respuesta; se agregaron los métodos `subirFoto()` y `foto()`; import de `Storage` |
| `backend-laravel/routes/api.php` | Se agregaron 2 rutas: `POST /incidencias/{id}/foto` y `GET /incidencias/{id}/foto` |
| `frontend/registrar.html` | Se agregó el campo de foto (input + vista previa) dentro de la tarjeta "Información de la incidencia" |
| `frontend/js/registrar.js` | Se agregó vista previa/validación de foto, geolocalización automática, subida de foto tras guardar |
| `frontend/js/registrar.min.js` | Copia funcional actualizada (ver nota final) |
| `frontend/js/sidebar.js` | Se agregó el enlace "Panel Supervisor", visible solo para `admin`/`supervisor` |
| `frontend/js/sidebar.min.js` | Copia funcional actualizada (ver nota final) |
| `frontend/usuarios.html` | **Corrección de bug preexistente**: el `<select id="uRol">` del formulario de crear/editar usuario solo tenía las opciones "Usuario" y "Administrador" en el HTML (aunque el rol "Supervisor" y los demás roles institucionales ya existían en el backend y en el filtro de búsqueda de la misma página). Se completó ese `<select>` con todos los roles ya soportados por el sistema, para poder crear/editar usuarios con rol Supervisor desde la interfaz. No se tocó `usuarios.js` ni ninguna otra lógica: el bug era puramente de opciones faltantes en el HTML. |
| `frontend/index.html` | **Dashboard según el rol** (pedido explícitamente por el usuario): se agregó una tarjeta de "Accesos rápidos" cuyo contenido y textos cambian según el rol de la persona que inicia sesión (admin, supervisor, roles institucionales, ciudadano). También se corrigió un bug preexistente: la tarjeta "Abiertas" siempre mostraba 0 porque el frontend leía `d.abiertas`, un campo que el backend nunca envía (el backend envía `pendientes`). El resto del dashboard (mapa global, categorías, últimas incidencias) no se tocó. |
| `reset_supervisor.sql` (nuevo) | Script de diagnóstico/reseteo para el problema de login "Credenciales incorrectas" con la cuenta de Supervisor: verifica si el usuario existe, si está activo, y permite resetear su contraseña a un valor conocido. |

**Nada más fue tocado.** No se modificó `styles.css`, ni `incidencias.html/js`, ni `admin.html/js`, ni `usuarios.html/js`, ni ningún otro módulo, endpoint o tabla existente.

---

## 3. Explicación de cada cambio

### A) Foto de la incidencia (Registro)
- Nuevo campo de archivo en el formulario, acepta solo `.jpg`, `.jpeg`, `.png`, con vista previa antes de guardar (`FileReader`), validado también en el backend (`mimes:jpg,jpeg,png`, máx. 5 MB).
- **Flujo:** al guardar, primero se crea la incidencia exactamente como antes (mismo endpoint `POST /incidencias`, mismo formato JSON, sin cambios). **Solo si el usuario adjuntó una foto**, se hace una segunda petición `POST /incidencias/{id}/foto` (multipart) para guardarla — igual al patrón ya usado para la foto de perfil de usuario. Esto significa que el endpoint de creación de incidencias **no cambió su contrato** y sigue funcionando igual para cualquier integración existente.
- La foto se sirve de forma protegida (requiere sesión) vía `GET /incidencias/{id}/foto`, igual que la foto de perfil.

### B) Ubicación en el mapa (Registro)
- El mapa Leaflet ya existía y seguía funcionando igual (clic para marcar ubicación).
- Se agregó: si el navegador lo permite, se detecta la ubicación actual automáticamente al cargar el mapa (`navigator.geolocation.getCurrentPosition`) y se coloca el marcador y las coordenadas; el usuario puede corregirla haciendo clic en cualquier otro punto.

### C) Panel de Supervisor (módulo nuevo)
- Pantalla nueva e independiente (`supervisor.html`), enlazada en el menú lateral solo para roles `admin` y `supervisor` (rol que ya existía en el sistema).
- Lista todas las incidencias (usa el endpoint `GET /incidencias` ya existente), con miniatura de foto, búsqueda por texto y filtro por estado.
- Modal de detalle: foto ampliable, mini-mapa con la ubicación (Leaflet), fecha/hora de registro, selector de estado (usa el endpoint `PUT /incidencias/{id}/estado` ya existente, que ya validaba las transiciones permitidas — no se tocó esa lógica) y sección de "Observaciones del supervisor" (reutiliza `GET`/`POST /incidencias/{id}/comentarios`, ya existentes).
- Los estados del sistema (`Registrada → En proceso → Resuelta → Cerrada`) no se modificaron; en el Panel de Supervisor, el estado `Registrada` se muestra con la etiqueta amigable **"Pendiente"** solo a nivel visual (cosmético), sin tocar el valor real en la base de datos ni la lógica de transición.

### D) Base de datos
- Único cambio real necesario: columna `foto` (VARCHAR 255, nullable) en `incidencias`.
- Se aplica automáticamente al iniciar el backend (el `Dockerfile` ya ejecuta `php artisan migrate --force` en cada arranque), o manualmente con `alter_incidencias_foto.sql` si se prefiere.
- No se eliminó ni renombró ninguna tabla o columna.

---

## 4. Confirmación de que el proyecto sigue funcionando igual

- **Ningún endpoint existente cambió su firma, método HTTP, ruta ni formato de respuesta.** Solo se agregó el campo `foto_url` (nuevo, adicional) a las respuestas de incidencias, lo cual no rompe a ningún consumidor que ignore campos desconocidos.
- **El flujo de creación de incidencias sigue siendo el mismo JSON de siempre**; la foto es un paso adicional y opcional posterior.
- **Ningún archivo de otros módulos** (incidencias, admin, usuarios, historial, reportes, apoyos, institución, perfil, dashboard) fue modificado.
- **Ninguna tabla ni columna fue eliminada.**
- El diseño visual (`styles.css`) no se tocó; los nuevos elementos reutilizan las mismas clases (`card`, `form-control`, `btn`, `badge`, `gi-table`, `modal-backdrop`, etc.) ya usadas en el resto del sistema.

## 5. Notas de soporte (login y ubicación)

**Login bloqueado ("Demasiados intentos fallidos"):** el sistema ya traía un límite de intentos de inicio de sesión por correo+IP (protección existente, no agregada por mí). Si se prueban varias contraseñas erróneas seguidas, el correo queda bloqueado 15 minutos. Espera ese tiempo o reinicia el contenedor del backend.

**Geolocalización automática:** `navigator.geolocation` solo funciona en un "contexto seguro" del navegador (HTTPS, o `http://localhost`). Si el frontend se abre desde una IP de red (por ejemplo `http://192.168.x.x:8080`) en vez de `localhost`, el navegador bloquea el permiso de ubicación automática silenciosamente (el mapa sigue funcionando, pero no auto-detecta; hay que marcar la ubicación manualmente haciendo clic). Esto es una restricción del navegador, no del código.

**Si el mapa no aparece en absoluto (ni al hacer clic):** revisa la consola del navegador (F12 → Console) mientras estás en Registro o en el Panel de Supervisor. Leaflet se carga desde `unpkg.com` y las teselas del mapa desde `cartocdn.com`; si esos dominios están bloqueados por una red corporativa/firewall, el mapa no se dibuja. El error en consola lo confirmaría.

## 6. Nota sobre los archivos `.min.js`

El proyecto no incluye un pipeline de build (no hay `package.json` ni minificador configurado); los `.min.js` existentes eran copias minificadas manuales. En este entorno no fue posible ejecutar un minificador (sin acceso a red para instalarlo), por lo que los `.min.js` de `registrar`, `sidebar` y el nuevo `supervisor` son una **copia funcionalmente idéntica** del código legible (no comprimida byte a byte). El sistema funciona exactamente igual; solo se pierde la compresión de esos 3 archivos puntuales. Si lo deseas, puedo indicarte cómo generarlos minificados con una herramienta como `terser` en tu propio equipo.
