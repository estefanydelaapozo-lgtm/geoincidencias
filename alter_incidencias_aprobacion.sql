-- ============================================================
-- GeoIncidencias — Script incremental
-- Agrega el flujo completo de aprobación avanzada:
--   - Ubicación ya existía (latitud, longitud, direccion_texto).
--   - Motivo de aprobación, fechas de aprobación/rechazo,
--     aprobación automática y plazo de 24 horas.
--   - Tabla de historial de aprobaciones/rechazos.
--
-- NOTA IMPORTANTE:
-- Este cambio también se aplica automáticamente al iniciar el
-- backend (Laravel ejecuta "php artisan migrate --force" en cada
-- arranque del contenedor, ver Dockerfile de backend-laravel),
-- gracias a la nueva migración:
--   backend-laravel/database/migrations/2026_07_29_000600_add_aprobacion_avanzada_incidencias.php
--
-- Este script SQL se entrega como alternativa por si se necesita
-- aplicar el cambio manualmente (phpMyAdmin, consola de Aiven, etc.)
-- SIN pasar por el proceso de migraciones de Laravel.
--
-- Es seguro ejecutarlo más de una vez: sólo agrega columnas/tablas
-- que todavía no existan. No elimina ni modifica ninguna tabla ni
-- columna existente.
-- ============================================================

-- ── Columnas nuevas en "incidencias" ──
SET @col_motivo_aprobacion := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencias' AND COLUMN_NAME = 'motivo_aprobacion'
);
SET @sql := IF(@col_motivo_aprobacion = 0,
  'ALTER TABLE incidencias ADD COLUMN motivo_aprobacion TEXT NULL AFTER motivo_rechazo;',
  'SELECT "La columna motivo_aprobacion ya existe." AS mensaje;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_fecha_aprobacion := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencias' AND COLUMN_NAME = 'fecha_aprobacion'
);
SET @sql := IF(@col_fecha_aprobacion = 0,
  'ALTER TABLE incidencias ADD COLUMN fecha_aprobacion TIMESTAMP NULL AFTER fecha_revision;',
  'SELECT "La columna fecha_aprobacion ya existe." AS mensaje;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_fecha_rechazo := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencias' AND COLUMN_NAME = 'fecha_rechazo'
);
SET @sql := IF(@col_fecha_rechazo = 0,
  'ALTER TABLE incidencias ADD COLUMN fecha_rechazo TIMESTAMP NULL AFTER fecha_aprobacion;',
  'SELECT "La columna fecha_rechazo ya existe." AS mensaje;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_aprobacion_automatica := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencias' AND COLUMN_NAME = 'aprobacion_automatica'
);
SET @sql := IF(@col_aprobacion_automatica = 0,
  'ALTER TABLE incidencias ADD COLUMN aprobacion_automatica TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_rechazo;',
  'SELECT "La columna aprobacion_automatica ya existe." AS mensaje;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_fecha_limite := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencias' AND COLUMN_NAME = 'fecha_limite_accion'
);
SET @sql := IF(@col_fecha_limite = 0,
  'ALTER TABLE incidencias ADD COLUMN fecha_limite_accion TIMESTAMP NULL AFTER aprobacion_automatica;',
  'SELECT "La columna fecha_limite_accion ya existe." AS mensaje;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Completar el plazo de 24 horas para incidencias que ya existían sin él.
UPDATE incidencias
SET fecha_limite_accion = DATE_ADD(fecha_registro, INTERVAL 24 HOUR)
WHERE fecha_limite_accion IS NULL;

-- ── Tabla de historial de aprobación / rechazo (manual o automático) ──
CREATE TABLE IF NOT EXISTS incidencia_aprobaciones_historial (
  id_historial_aprobacion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_incidencia BIGINT UNSIGNED NOT NULL,
  id_usuario BIGINT UNSIGNED NULL,
  accion ENUM('aprobada', 'rechazada', 'aprobada_automatica') NOT NULL,
  motivo TEXT NULL,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aprobaciones_incidencia_fecha (id_incidencia, fecha),
  CONSTRAINT fk_aprobaciones_incidencia FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
  CONSTRAINT fk_aprobaciones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Nota sobre ubicación (latitud, longitud, direccion_texto):
-- La tabla "incidencias" YA contenía estas tres columnas desde la
-- migración original 2026_07_01_000010_create_incidencias_table.php,
-- por lo que no se requiere ningún cambio adicional en la base de
-- datos para la ubicación exacta de la incidencia. Lo que faltaba
-- era exponer "direccion_texto" en el frontend (registro, edición,
-- panel de supervisor y panel de administración) y agregar el
-- enlace de "Cómo llegar" (Google Maps), lo cual se resuelve en el
-- backend (IncidenciasController::transformar) sin cambios de BD.
--
-- Nota sobre aprobación automática por 24 horas:
-- El respaldo periódico corre vía "php artisan incidencias:auto-aprobar"
-- (programado cada 5 minutos en bootstrap/app.php) y, además, de
-- forma perezosa en cada petición al listado/detalle de incidencias
-- (con límite de una ejecución real cada 30s), por lo que funciona
-- incluso si el contenedor no tiene cron configurado.
-- ============================================================
