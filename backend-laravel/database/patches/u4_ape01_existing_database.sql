-- Ejecutar UNA sola vez sobre una base GeoIncidencias ya creada.
ALTER TABLE usuarios MODIFY rol ENUM('admin','tecnico','usuario') NOT NULL DEFAULT 'usuario';
UPDATE estados SET nombre='Registrada', descripcion='Incidencia registrada y pendiente de atención' WHERE nombre='Pendiente';
UPDATE estados SET nombre='Resuelta' WHERE nombre='Resuelto';
UPDATE estados SET nombre='Cerrada' WHERE nombre='Cerrado';
CREATE INDEX idx_incidencias_flujo ON incidencias (estado_aprobacion, id_estado_actual, fecha_registro);
CREATE INDEX idx_incidencias_filtros ON incidencias (id_tipo, id_zona, prioridad);
CREATE INDEX idx_incidencias_fecha ON incidencias (fecha_ocurrencia);
CREATE INDEX idx_historial_incidencia_fecha ON incidencia_estados_historial (id_incidencia, fecha_cambio);
