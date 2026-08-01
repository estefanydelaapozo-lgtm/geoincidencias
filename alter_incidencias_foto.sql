-- ============================================================
-- GeoIncidencias — Script incremental
-- Agrega el campo necesario para la foto de la incidencia.
--
-- NOTA IMPORTANTE:
-- Este cambio también se aplica automáticamente al iniciar el
-- backend (Laravel ejecuta "php artisan migrate --force" en cada
-- arranque del contenedor, ver Dockerfile de backend-laravel),
-- gracias a la nueva migración:
--   backend-laravel/database/migrations/2026_07_27_000500_add_foto_incidencia.php
--
-- Este script SQL se entrega como alternativa por si se necesita
-- aplicar el cambio manualmente (phpMyAdmin, consola de Aiven, etc.)
-- SIN pasar por el proceso de migraciones de Laravel.
--
-- Es seguro ejecutarlo más de una vez: sólo agrega la columna si
-- todavía no existe. No elimina ni modifica ninguna tabla ni
-- columna existente.
-- ============================================================

SET @columna_existe := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'incidencias'
    AND COLUMN_NAME  = 'foto'
);

SET @sql := IF(
  @columna_existe = 0,
  'ALTER TABLE incidencias ADD COLUMN foto VARCHAR(255) NULL AFTER direccion_texto;',
  'SELECT "La columna foto ya existe en incidencias, no se realizaron cambios." AS mensaje;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Nota sobre latitud, longitud y estado:
-- La tabla "incidencias" YA contenía las columnas "latitud" y
-- "longitud" (decimal 10,6) desde la migración original
-- 2026_07_01_000010_create_incidencias_table.php, así como el
-- flujo de estados dinámico (tabla "estados" + "id_estado_actual"),
-- por lo que NO se requiere ningún cambio adicional en la base de
-- datos para esos campos.
--
-- Nota sobre "observacion_supervisor":
-- El sistema ya contaba con una tabla dedicada a comentarios de
-- seguimiento por incidencia: "incidencia_comentarios" (con autor,
-- fecha y texto), mucho más completa que una sola columna de texto
-- plano. El Panel de Supervisor reutiliza esa tabla existente para
-- registrar las observaciones del supervisor, evitando duplicar
-- funcionalidad y estructuras de datos. Ver explicación detallada
-- en el documento de entrega.
-- ============================================================
