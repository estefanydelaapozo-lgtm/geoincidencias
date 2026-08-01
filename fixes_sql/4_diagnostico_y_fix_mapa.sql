-- ============================================================
-- MAPA VACÍO — diagnóstico y fix
-- El mapa (endpoint /incidencias/mapa) solo muestra incidencias
-- que tengan latitud/longitud. Si se registraron sin marcar un
-- punto en el mapa, quedan invisibles ahí (aunque sí aparezcan
-- en las listas normales).
-- ============================================================

-- 1) DIAGNÓSTICO: cuántas incidencias aprobadas no tienen coordenadas
--    (estas son justo las que faltan en el mapa).
SELECT COUNT(*) AS sin_coordenadas
FROM incidencias
WHERE estado_aprobacion = 'aprobada'
  AND (latitud IS NULL OR longitud IS NULL);

-- 2) Detalle: cuáles son y si su zona tiene una coordenada de
--    referencia para poder rellenarlas.
SELECT i.id_incidencia, i.titulo, z.nombre AS zona,
       z.latitud_ref, z.longitud_ref
FROM incidencias i
JOIN zonas z ON i.id_zona = z.id_zona
WHERE i.estado_aprobacion = 'aprobada'
  AND (i.latitud IS NULL OR i.longitud IS NULL);

-- 3) FIX: rellena la incidencia con la coordenada de referencia
--    de su zona, solo cuando la incidencia no tenga una propia
--    y la zona sí tenga una definida. No pisa coordenadas ya
--    guardadas.
UPDATE incidencias i
JOIN zonas z ON i.id_zona = z.id_zona
SET i.latitud = z.latitud_ref,
    i.longitud = z.longitud_ref
WHERE (i.latitud IS NULL OR i.longitud IS NULL)
  AND z.latitud_ref IS NOT NULL
  AND z.longitud_ref IS NOT NULL;

-- 4) Verifica que ya no queden incidencias aprobadas sin coordenadas
--    (si sigue saliendo > 0, esa zona tampoco tiene latitud_ref/
--    longitud_ref definida — habría que ponérsela a la zona primero).
SELECT COUNT(*) AS sin_coordenadas_restantes
FROM incidencias
WHERE estado_aprobacion = 'aprobada'
  AND (latitud IS NULL OR longitud IS NULL);
