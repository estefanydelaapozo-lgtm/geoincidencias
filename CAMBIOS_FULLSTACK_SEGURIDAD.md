# GeoIncidencias — Correcciones y seguridad

## Cambios aplicados

1. **Apoyos:** la API devuelve ahora nombres compatibles con el frontend (`titulo`, `monto`, `created_at`) sin eliminar los nombres anteriores. Se validan `id_incidencia` y comentarios.
2. **Reportes:** filtros validados (`desde`, `hasta`, `tipo`, `zona`), nombres de campos compatibles con Chart.js, prioridad predominante, promedio de resolución y filtros también en responsables.
3. **Fuerza bruta:** bloqueo por IP + correo después de 5 intentos durante 15 minutos.
4. **Entradas y auditoría:** middleware de detección XSS/SQLi, sanitización de texto (excluye contraseñas/tokens), archivo `storage/logs/security.log` y tabla `security_audit_logs`.
5. **Headers:** CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy y HSTS cuando se usa HTTPS.
6. **Administrador:** estilo visual exclusivo, banda de seguridad y carga de foto de perfil (JPG/PNG/WebP, 2 MB).
7. **Contraseñas:** botones mostrar/ocultar en los campos escritos. La contraseña guardada nunca puede revelarse porque Laravel almacena únicamente un hash irreversible.
8. **Notificaciones:** se notifican nuevas incidencias a administradores y técnicos activos. El frontend consulta nuevas notificaciones cada 30 segundos y muestra alerta visual.

## Aplicación de migraciones

```powershell
docker compose exec backend php artisan migrate --force
```

## Reinicio recomendado

```powershell
docker compose down
docker compose build --no-cache backend frontend
docker compose up -d
docker compose exec backend php artisan migrate --force
```

Luego abrir `http://localhost:8080` y realizar una recarga completa con `Ctrl + F5`.

## Verificación

```powershell
docker compose exec backend php artisan route:list
docker compose exec backend php artisan test
```

Revisar eventos sospechosos:

```powershell
docker compose exec backend tail -n 100 storage/logs/security.log
```
