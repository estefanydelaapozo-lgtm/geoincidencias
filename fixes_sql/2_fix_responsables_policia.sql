-- ══════════════════════════════════════════════════════════════════
-- FIX — GeoIncidencias
-- Corrige DATOS, no código. Es seguro ejecutarlo aunque ya esté bien
-- (solo toca filas que estén mal / vacías, con WHERE que lo limita).
-- Ejecuta primero 1_diagnostico_responsables.sql para confirmar.
-- ══════════════════════════════════════════════════════════════════

-- PASO 1: Reparar usuarios cuyo id_rol no coincide con su rol de texto.
-- (Pasa sobre todo a usuarios creados con los scripts crear_usuarios*.sql,
--  que insertan directo en la tabla sin pasar por el formulario de la app).
UPDATE usuarios u
JOIN roles r ON r.slug = u.rol
SET u.id_rol = r.id_rol
WHERE u.id_rol IS NULL OR u.id_rol <> r.id_rol;

-- PASO 2: Asignar responsable institucional a los tipos de incidencia
-- que quedaron sin institución (misma lógica con la que se creó el
-- sistema de roles, solo que reaplicada a tipos que faltaron).
UPDATE tipos_incidencia t JOIN roles r ON r.slug='policia'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'seguridad|robo|violencia|vandal|accidente';

UPDATE tipos_incidencia t JOIN roles r ON r.slug='bomberos'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'incendio|rescate|gas|fuego';

UPDATE tipos_incidencia t JOIN roles r ON r.slug='salud'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'salud|m[eé]dic|emergencia|sanitari';

UPDATE tipos_incidencia t JOIN roles r ON r.slug='electrica'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'el[eé]ctric|energ[ií]a|alumbrado|poste';

UPDATE tipos_incidencia t JOIN roles r ON r.slug='agua'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'agua|fuga|alcantarill';

UPDATE tipos_incidencia t JOIN roles r ON r.slug='obras_publicas'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'infraestructura|bache|calle|vial|se[ñn]al';

UPDATE tipos_incidencia t JOIN roles r ON r.slug='medio_ambiente'
  SET t.id_rol_responsable=r.id_rol
  WHERE t.id_rol_responsable IS NULL
    AND LOWER(t.nombre) REGEXP 'ambiente|basura|contamin|[aá]rbol';

-- PASO 3 (opcional, revisa antes): si después de lo anterior sigue
-- habiendo tipos con id_rol_responsable NULL (nombres que no calzaron
-- con ninguna palabra clave), asígnalos a mano así, cambiando el
-- id_tipo y el slug según tu caso real (usa el resultado del
-- diagnóstico, consulta #2, para saber cuáles faltan):
--
-- UPDATE tipos_incidencia
-- SET id_rol_responsable = (SELECT id_rol FROM roles WHERE slug='policia')
-- WHERE id_tipo = 1;

-- PASO 4: Vuelve a correr el diagnóstico (consultas 1, 2 y 4) para
-- confirmar que ya no queden usuarios ni tipos sin resolver.
-- Nota: las incidencias que ya existían ANTES de este fix no se
-- reasignan solas (la asignación automática solo corre al REGISTRAR
-- una incidencia nueva). Si necesitas que las incidencias antiguas
-- también aparezcan en "Rendimiento por responsable", dímelo y te
-- preparo un PASO 5 que las asigna retroactivamente.
