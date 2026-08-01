<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vw_incidencias_resumen');
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_incidencias_resumen AS
SELECT
    i.id_incidencia,
    i.titulo,
    i.prioridad,
    e.nombre AS estado,
    t.nombre AS tipo,
    st.nombre AS subtipo,
    z.nombre AS zona,
    c.nombre AS ciudad,
    i.estado_aprobacion,
    i.fecha_registro,
    i.fecha_resolucion,
    i.tiempo_resolucion_horas
FROM incidencias i
INNER JOIN estados e ON e.id_estado = i.id_estado_actual
INNER JOIN tipos_incidencia t ON t.id_tipo = i.id_tipo
LEFT JOIN subtipos_incidencia st ON st.id_subtipo = i.id_subtipo
INNER JOIN zonas z ON z.id_zona = i.id_zona
INNER JOIN ciudades c ON c.id_ciudad = z.id_ciudad
SQL);

        DB::unprepared('DROP VIEW IF EXISTS vw_metricas_incidencias');
        DB::unprepared(<<<'SQL'
CREATE VIEW vw_metricas_incidencias AS
SELECT
    e.nombre AS estado,
    COUNT(*) AS total_incidencias,
    ROUND(AVG(i.tiempo_resolucion_horas), 2) AS promedio_resolucion_horas,
    SUM(CASE WHEN i.prioridad = 'Crítica' THEN 1 ELSE 0 END) AS criticas,
    SUM(CASE WHEN i.prioridad = 'Alta' THEN 1 ELSE 0 END) AS altas
FROM estados e
LEFT JOIN incidencias i ON i.id_estado_actual = e.id_estado
GROUP BY e.id_estado, e.nombre
SQL);

        DB::unprepared('DROP FUNCTION IF EXISTS fn_total_incidencias_estado');
        DB::unprepared(<<<'SQL'
CREATE FUNCTION fn_total_incidencias_estado(p_estado VARCHAR(60))
RETURNS INT
DETERMINISTIC
READS SQL DATA
RETURN (
    SELECT COUNT(*)
    FROM incidencias i
    INNER JOIN estados e ON e.id_estado = i.id_estado_actual
    WHERE e.nombre = p_estado
)
SQL);

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_reporte_incidencias_periodo');
        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_reporte_incidencias_periodo(IN p_desde DATE, IN p_hasta DATE)
BEGIN
    SELECT
        i.id_incidencia,
        i.titulo,
        i.prioridad,
        e.nombre AS estado,
        t.nombre AS tipo,
        z.nombre AS zona,
        i.fecha_ocurrencia,
        i.tiempo_resolucion_horas
    FROM incidencias i
    INNER JOIN estados e ON e.id_estado = i.id_estado_actual
    INNER JOIN tipos_incidencia t ON t.id_tipo = i.id_tipo
    INNER JOIN zonas z ON z.id_zona = i.id_zona
    WHERE i.fecha_ocurrencia BETWEEN p_desde AND p_hasta
    ORDER BY i.fecha_ocurrencia DESC;
END
SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_comentario_auditoria');
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_comentario_auditoria
AFTER INSERT ON incidencia_comentarios
FOR EACH ROW
INSERT INTO historial_actividad
    (id_usuario, id_incidencia, accion, detalle, fecha_hora, ip_origen)
VALUES
    (NEW.id_usuario, NEW.id_incidencia, 'comentario_sql',
     CONCAT('Comentario registrado automáticamente. ID: ', NEW.id_comentario),
     CURRENT_TIMESTAMP, 'trigger-db')
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_comentario_auditoria');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_reporte_incidencias_periodo');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_total_incidencias_estado');
        DB::unprepared('DROP VIEW IF EXISTS vw_metricas_incidencias');
        DB::unprepared('DROP VIEW IF EXISTS vw_incidencias_resumen');
    }
};
