# Roles institucionales implementados

Roles: Administrador, Ciudadano, Técnico general, Policía, Bomberos, Salud/Emergencias, Empresa Eléctrica, Agua Potable, Obras Públicas, Medio Ambiente y Supervisor.

## Funcionamiento
- La migración crea la tabla `roles`, agrega `usuarios.id_rol` y vincula cada tipo de incidencia con un rol responsable mediante `tipos_incidencia.id_rol_responsable`.
- Al registrar una incidencia se notifica al administrador y a los usuarios activos de la institución responsable.
- Se crea una asignación automática para los responsables institucionales.
- Los usuarios institucionales solo ven incidencias de su área en el listado y en `institucion.html`.
- Solo responsables asignados, supervisores o administradores pueden cambiar estados.
- El administrador puede crear y editar usuarios con cualquiera de los roles desde `usuarios.html`.

## Ajustar la institución responsable de un tipo
```sql
UPDATE tipos_incidencia
SET id_rol_responsable=(SELECT id_rol FROM roles WHERE slug='policia')
WHERE id_tipo=1;
```

## Ejecución
Ejecuta `LEVANTAR_TODO_GEOINCIDENCIAS.bat`; el lanzador ejecutará `php artisan migrate --force`.
