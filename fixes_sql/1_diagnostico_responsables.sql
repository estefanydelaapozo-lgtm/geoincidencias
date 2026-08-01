-- ══════════════════════════════════════════════════════════════════
-- DIAGNÓSTICO — GeoIncidencias
-- Solo consultas SELECT. No modifica absolutamente nada.
-- Ejecuta esto primero para confirmar el problema antes de aplicar
-- el archivo 2_fix_responsables_policia.sql
-- ══════════════════════════════════════════════════════════════════

-- 1) Usuarios cuyo id_rol no coincide con su rol (o está vacío).
--    Si el policía aparece aquí, por eso no le sale nada en su panel.
SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.rol AS rol_texto,
       u.id_rol AS id_rol_actual, r.id_rol AS id_rol_correcto
FROM usuarios u
LEFT JOIN roles r ON r.slug = u.rol
WHERE u.id_rol IS NULL OR u.id_rol <> r.id_rol;

-- 2) Tipos de incidencia SIN institución responsable asignada.
--    Estos son los que nunca se asignan a nadie ni salen en "Rendimiento
--    por responsable", sin importar qué usuario tengas.
SELECT id_tipo, nombre, id_rol_responsable
FROM tipos_incidencia
WHERE id_rol_responsable IS NULL
ORDER BY nombre;

-- 3) Lista de roles disponibles, para saber qué id_rol usar en el fix
--    (por ejemplo para asignarle manualmente un tipo a "policia").
SELECT id_rol, slug, nombre, es_institucional
FROM roles
ORDER BY id_rol;

-- 4) Cuántas asignaciones "responsable" existen hoy (si sale 0,
--    confirma por qué el reporte de rendimiento está vacío).
SELECT rol_asignacion, COUNT(*) AS total
FROM incidencia_asignaciones
GROUP BY rol_asignacion;
