# GeoIncidencias — Rediseño UI/UX y módulos profesionales

## Implementado
- Perfil clicable desde el sidebar, edición de nombre, apellidos, correo, teléfono y fotografía.
- Cambio de contraseña con validación visual en tiempo real.
- Login y registro empresarial con glassmorphism, opción Recuérdame y Google OAuth2.
- Reportes con filtros por fecha, tipo, zona y prioridad; gráficos Chart.js; exportación XLSX y PDF con membrete.
- Vista `plan-negocio.html` con fuentes de ingreso, incentivos, costo-beneficio, clientes e indicadores.
- Migración para `google_id`, proveedor de autenticación, verificación de correo y remember token.

## Google OAuth
Defina `GOOGLE_CLIENT_ID` y agregue `http://localhost:8080` como origen autorizado. Consulte `CONFIGURAR_GOOGLE_OAUTH.txt`.

## Inicio
Ejecute `LEVANTAR_TODO_GEOINCIDENCIAS.bat` o:
```powershell
docker compose down
docker compose up -d --build
```
Las migraciones se ejecutan al iniciar el backend.
