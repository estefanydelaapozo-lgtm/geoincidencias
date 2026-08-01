-- GeoIncidencias: objetos SQL avanzados para MySQL/Aiven
-- Estos objetos también se crean automáticamente mediante migración Laravel.

DROP VIEW IF EXISTS vw_incidencias_resumen;
CREATE VIEW vw_incidencias_resumen AS
SELECT i.id_incidencia, i.titulo, i.prioridad, e.nombre AS estado,
       t.nombre AS tipo, st.nombre AS subtipo, z.nombre AS zona,
       c.nombre AS ciudad, i.estado_aprobacion, i.fecha_registro,
       i.fecha_resolucion, i.tiempo_resolucion_horas
FROM incidencias i
JOIN estados e ON e.id_estado = i.id_estado_actual
JOIN tipos_incidencia t ON t.id_tipo = i.id_tipo
LEFT JOIN subtipos_incidencia st ON st.id_subtipo = i.id_subtipo
JOIN zonas z ON z.id_zona = i.id_zona
JOIN ciudades c ON c.id_ciudad = z.id_ciudad;

DROP VIEW IF EXISTS vw_metricas_incidencias;
CREATE VIEW vw_metricas_incidencias AS
SELECT e.nombre AS estado, COUNT(i.id_incidencia) AS total_incidencias,
       ROUND(AVG(i.tiempo_resolucion_horas),2) AS promedio_resolucion_horas,
       SUM(CASE WHEN i.prioridad='Crítica' THEN 1 ELSE 0 END) AS criticas,
       SUM(CASE WHEN i.prioridad='Alta' THEN 1 ELSE 0 END) AS altas
FROM estados e
LEFT JOIN incidencias i ON i.id_estado_actual=e.id_estado
GROUP BY e.id_estado,e.nombre;

DROP FUNCTION IF EXISTS fn_total_incidencias_estado;
CREATE FUNCTION fn_total_incidencias_estado(p_estado VARCHAR(60))
RETURNS INT DETERMINISTIC READS SQL DATA
RETURN (SELECT COUNT(*) FROM incidencias i JOIN estados e
        ON e.id_estado=i.id_estado_actual WHERE e.nombre=p_estado);

DROP PROCEDURE IF EXISTS sp_reporte_incidencias_periodo;
DELIMITER $$
CREATE PROCEDURE sp_reporte_incidencias_periodo(IN p_desde DATE, IN p_hasta DATE)
BEGIN
  SELECT i.id_incidencia,i.titulo,i.prioridad,e.nombre AS estado,
         t.nombre AS tipo,z.nombre AS zona,i.fecha_ocurrencia,
         i.tiempo_resolucion_horas
  FROM incidencias i
  JOIN estados e ON e.id_estado=i.id_estado_actual
  JOIN tipos_incidencia t ON t.id_tipo=i.id_tipo
  JOIN zonas z ON z.id_zona=i.id_zona
  WHERE i.fecha_ocurrencia BETWEEN p_desde AND p_hasta
  ORDER BY i.fecha_ocurrencia DESC;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_comentario_auditoria;
DELIMITER $$
CREATE TRIGGER trg_comentario_auditoria
AFTER INSERT ON incidencia_comentarios
FOR EACH ROW
BEGIN
  INSERT INTO historial_actividad
    (id_usuario,id_incidencia,accion,detalle,fecha_hora,ip_origen)
  VALUES
    (NEW.id_usuario,NEW.id_incidencia,'comentario_sql',
     CONCAT('Comentario registrado automáticamente. ID: ',NEW.id_comentario),
     CURRENT_TIMESTAMP,'trigger-db');
END$$
DELIMITER ;
