-- GeoIncidencias - Exportación de Base de Datos
-- Sistema de Gestión de Incidencias Georreferenciadas
-- Generado: 2026-06-30

-- -----------------------------------------------------
-- Tabla: paises
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `paises` (
  `id_pais` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo_iso` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id_pais`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `paises` (`id_pais`, `nombre`, `codigo_iso`) VALUES
(1, 'Ecuador', 'EC');

-- -----------------------------------------------------
-- Tabla: provincias
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `provincias` (
  `id_provincia` int(11) NOT NULL AUTO_INCREMENT,
  `id_pais` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id_provincia`),
  KEY `fk_provincias_pais` (`id_pais`),
  CONSTRAINT `fk_provincias_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `provincias` (`id_provincia`, `id_pais`, `nombre`) VALUES
(1, 1, 'Guayas'),
(2, 1, 'Pichincha'),
(3, 1, 'Santa Elena');

-- -----------------------------------------------------
-- Tabla: ciudades
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ciudades` (
  `id_ciudad` int(11) NOT NULL AUTO_INCREMENT,
  `id_provincia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `latitud_ref` decimal(10,6) DEFAULT NULL,
  `longitud_ref` decimal(10,6) DEFAULT NULL,
  PRIMARY KEY (`id_ciudad`),
  KEY `fk_ciudades_provincia` (`id_provincia`),
  CONSTRAINT `fk_ciudades_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ciudades` (`id_ciudad`, `id_provincia`, `nombre`, `latitud_ref`, `longitud_ref`) VALUES
(1, 1, 'Guayaquil', -2.170998, -79.922359),
(2, 2, 'Quito', -0.180653, -78.467838),
(3, 3, 'La Libertad', -2.232450, -80.905610);

-- -----------------------------------------------------
-- Tabla: zonas
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `zonas` (
  `id_zona` int(11) NOT NULL AUTO_INCREMENT,
  `id_ciudad` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `latitud_ref` decimal(10,6) DEFAULT NULL,
  `longitud_ref` decimal(10,6) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_zona`),
  KEY `fk_zonas_ciudad` (`id_ciudad`),
  CONSTRAINT `fk_zonas_ciudad` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudades` (`id_ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `zonas` (`id_zona`, `id_ciudad`, `nombre`, `descripcion`, `latitud_ref`, `longitud_ref`, `activo`) VALUES
(1, 1, 'Planta Baja', 'Area de recepcion y acceso principal', -2.900100, -79.005900, 1),
(2, 1, 'Piso 1', 'Oficinas administrativas', -2.900200, -79.005800, 1),
(3, 1, 'Piso 2', 'Area tecnica y sistemas', -2.900300, -79.005700, 1),
(4, 1, 'Piso 3', 'Gerencia y salas de reuniones', -2.900400, -79.005600, 1),
(5, 1, 'Bodega', 'Almacen y logistica', -2.900500, -79.005500, 1),
(6, 1, 'Parqueadero', 'Zona de parqueo vehicular', -2.900600, -79.005400, 1),
(7, 1, 'Sala de Servidores', 'Centro de datos principal', -2.900700, -79.005300, 1),
(8, 1, 'Cafeteria', 'Area de descanso y comedor', -2.900800, -79.005200, 1);

-- -----------------------------------------------------
-- Tabla: tipos_incidencia
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tipos_incidencia` (
  `id_tipo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `icono` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tipos_incidencia` (`id_tipo`, `nombre`, `descripcion`, `icono`, `color`, `activo`) VALUES
(1, 'Infraestructura', 'Danos en instalaciones fisicas', 'bi-building', '#f97316', 1),
(2, 'Equipos TI', 'Fallas en hardware o software', 'bi-pc-display', '#6366f1', 1),
(3, 'Red y Conectividad', 'Problemas de red, internet o telefonia', 'bi-wifi-off', '#3b82f6', 1),
(4, 'Seguridad', 'Incidentes de seguridad fisica o digital', 'bi-shield-exclamation', '#ef4444', 1),
(5, 'Suministros', 'Falta o dano de materiales', 'bi-box-seam', '#eab308', 1),
(6, 'Servicios Basicos', 'Agua, luz, clima, aseo', 'bi-lightning-charge', '#10b981', 1),
(7, 'Accidentes', 'Accidentes laborales o de transito', 'bi-bandaid', '#f43f5e', 1);

-- -----------------------------------------------------
-- Tabla: subtipos_incidencia
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `subtipos_incidencia` (
  `id_subtipo` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_subtipo`),
  KEY `fk_subtipos_tipo` (`id_tipo`),
  CONSTRAINT `fk_subtipos_tipo` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_incidencia` (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subtipos_incidencia` (`id_subtipo`, `id_tipo`, `nombre`, `activo`) VALUES
(1, 1, 'Alumbrado', 1),
(2, 1, 'Goteras y Filtraciones', 1),
(3, 1, 'Puertas y accesos', 1),
(4, 1, 'Mobiliario danado', 1),
(5, 2, 'Computador no enciende', 1),
(6, 2, 'Error de software', 1),
(7, 2, 'Impresora', 1),
(8, 2, 'Perdida de datos', 1),
(9, 3, 'Internet lento', 1),
(10, 3, 'Sin conexion', 1),
(11, 3, 'Telefonia IP', 1),
(12, 4, 'Robo', 1),
(13, 4, 'Acceso no autorizado', 1),
(14, 4, 'Camara danada', 1),
(15, 4, 'Alarma activada', 1),
(16, 5, 'Falta de insumos de oficina', 1),
(17, 5, 'Falta de equipo de proteccion', 1),
(18, 6, 'Corte de energia', 1),
(19, 6, 'Falla de climatizacion', 1),
(20, 6, 'Falta de agua', 1),
(21, 7, 'Accidente laboral', 1),
(22, 7, 'Accidente vehicular', 1);

-- -----------------------------------------------------
-- Tabla: estados
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `estados` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  `color` varchar(7) DEFAULT NULL,
  `orden` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `estados` (`id_estado`, `nombre`, `descripcion`, `color`, `orden`, `activo`) VALUES
(1, 'Pendiente', 'Incidencia reportada, aun no atendida', '#ef4444', 1, 1),
(2, 'En proceso', 'Incidencia siendo atendida por el responsable', '#f59e0b', 2, 1),
(3, 'Resuelto', 'Incidencia solucionada', '#22c55e', 3, 1),
(4, 'Cerrado', 'Incidencia verificada y cerrada oficialmente', '#64748b', 4, 1);

-- -----------------------------------------------------
-- Tabla: incentivos_prioridad
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `incentivos_prioridad` (
  `id_incentivo` int(11) NOT NULL AUTO_INCREMENT,
  `prioridad` varchar(20) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_incentivo`),
  UNIQUE KEY `uk_prioridad` (`prioridad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incentivos_prioridad` (`id_incentivo`, `prioridad`, `monto`) VALUES
(1, 'Baja', 5.00),
(2, 'Media', 10.00),
(3, 'Alta', 20.00);

-- -----------------------------------------------------
-- Tabla: usuarios
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `telefono` varchar(20) DEFAULT NULL,
  `saldo_incentivos` decimal(10,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `correo`, `password`, `rol`, `telefono`, `saldo_incentivos`, `activo`, `created_at`) VALUES
(1, 'Admin', 'Sistema', 'admin@geoincidencias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0990000000', 0.00, 1, NOW()),
(2, 'Carlos', 'Mendoza', 'cmendoza@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0991234567', 20.00, 1, NOW()),
(3, 'Maria', 'Gonzalez', 'mgonzalez@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0992345678', 0.00, 1, NOW()),
(4, 'Pedro', 'Ramirez', 'pramirez@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0993456789', 5.00, 1, NOW()),
(5, 'Lucia', 'Torres', 'ltorres@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0994567890', 0.00, 1, NOW());

-- -----------------------------------------------------
-- Tabla: incidencias
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencias` (
  `id_incidencia` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text,
  `prioridad` enum('Baja','Media','Alta') NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `id_subtipo` int(11) DEFAULT NULL,
  `id_estado_actual` int(11) NOT NULL,
  `estado_aprobacion` enum('pendiente_revision','aprobada','rechazada') DEFAULT 'pendiente_revision',
  `id_admin_revisor` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `motivo_rechazo` text,
  `id_zona` int(11) NOT NULL,
  `latitud` decimal(10,6) DEFAULT NULL,
  `longitud` decimal(10,6) DEFAULT NULL,
  `direccion_texto` varchar(255) DEFAULT NULL,
  `fecha_ocurrencia` date NOT NULL,
  `hora_ocurrencia` time DEFAULT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `tiempo_resolucion_horas` decimal(10,2) DEFAULT NULL,
  `reportante_nombre` varchar(100) DEFAULT NULL,
  `reportante_contacto` varchar(20) DEFAULT NULL,
  `id_usuario_creador` int(11) NOT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_incidencia`),
  KEY `fk_incidencias_tipo` (`id_tipo`),
  KEY `fk_incidencias_subtipo` (`id_subtipo`),
  KEY `fk_incidencias_estado` (`id_estado_actual`),
  KEY `fk_incidencias_zona` (`id_zona`),
  KEY `fk_incidencias_creador` (`id_usuario_creador`),
  KEY `fk_incidencias_admin` (`id_admin_revisor`),
  CONSTRAINT `fk_incidencias_tipo` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_incidencia` (`id_tipo`),
  CONSTRAINT `fk_incidencias_subtipo` FOREIGN KEY (`id_subtipo`) REFERENCES `subtipos_incidencia` (`id_subtipo`),
  CONSTRAINT `fk_incidencias_estado` FOREIGN KEY (`id_estado_actual`) REFERENCES `estados` (`id_estado`),
  CONSTRAINT `fk_incidencias_zona` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id_zona`),
  CONSTRAINT `fk_incidencias_creador` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_incidencias_admin` FOREIGN KEY (`id_admin_revisor`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incidencias` (`id_incidencia`, `titulo`, `descripcion`, `prioridad`, `id_tipo`, `id_subtipo`, `id_estado_actual`, `estado_aprobacion`, `id_admin_revisor`, `fecha_revision`, `motivo_rechazo`, `id_zona`, `latitud`, `longitud`, `direccion_texto`, `fecha_ocurrencia`, `hora_ocurrencia`, `fecha_resolucion`, `tiempo_resolucion_horas`, `reportante_nombre`, `reportante_contacto`, `id_usuario_creador`, `fecha_registro`) VALUES
(1, 'Falla en servidor principal', 'El servidor de base de datos no responde desde las 08:00.', 'Alta', 2, 5, 2, 'aprobada', 1, NOW(), NULL, 7, -2.900700, -79.005300, NULL, '2026-06-15', '08:15', NULL, NULL, 'Ana Suarez', '0997001122', 2, NOW()),
(2, 'Corte de energia en piso 2', 'Se fue la luz en el ala norte del piso 2.', 'Alta', 6, 18, 2, 'aprobada', 1, NOW(), NULL, 3, -2.900200, -79.005800, NULL, '2026-06-15', '09:30', NULL, NULL, 'Luis Paredes', '0997002233', 3, NOW()),
(3, 'Filtracion de agua en techo de bodega', 'Se detecto humedad y goteo en el techo de la bodega sector B.', 'Alta', 1, 2, 1, 'aprobada', 1, NOW(), NULL, 5, -2.900500, -79.005500, NULL, '2026-06-14', '14:00', NULL, NULL, 'Roberto Mora', '0997003344', 4, NOW()),
(4, 'Impresora de recepcion fuera de servicio', 'La impresora no imprime y muestra error de papel atascado.', 'Media', 2, 7, 3, 'aprobada', 1, NOW(), NULL, 1, -2.900100, -79.005900, NULL, '2026-06-16', '10:00', NOW(), 2.5, 'Sofia Chavez', '0997004455', 2, NOW()),
(5, 'Internet muy lento en piso 3', 'La velocidad de internet es inferior a 1 Mbps en el piso 3.', 'Media', 3, 9, 2, 'aprobada', 1, NOW(), NULL, 4, -2.900400, -79.005600, NULL, '2026-06-16', '11:30', NULL, NULL, 'Jorge Ruiz', '0997005566', 3, NOW()),
(6, 'Falta de papel en impresoras', 'No hay papel en ninguna de las impresoras del piso 1.', 'Baja', 5, 16, 3, 'aprobada', 1, NOW(), NULL, 2, -2.900200, -79.005800, NULL, '2026-06-17', '09:00', NOW(), 1.0, 'Elena Vega', '0997006677', 4, NOW()),
(7, 'Alarma de seguridad activada', 'La alarma de seguridad se activo sin motivo aparente.', 'Alta', 4, 15, 3, 'aprobada', 1, NOW(), NULL, 1, -2.900100, -79.005900, NULL, '2026-06-17', '15:45', NOW(), 0.5, 'Guardia Nocturno', '0997007788', 2, NOW()),
(8, 'Falla de aire acondicionado en sala servidores', 'La temperatura en la sala de servidores esta subiendo a 28°C.', 'Alta', 6, 19, 2, 'aprobada', 1, NOW(), NULL, 7, -2.900700, -79.005300, NULL, '2026-06-18', '07:00', NULL, NULL, 'Tecnico Datacenter', '0997008899', 3, NOW());

-- -----------------------------------------------------
-- Tabla: incidencia_asignaciones
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencia_asignaciones` (
  `id_asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `rol_asignacion` enum('responsable','apoyo') DEFAULT 'responsable',
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_asignacion`),
  UNIQUE KEY `uk_incidencia_usuario` (`id_incidencia`,`id_usuario`),
  KEY `fk_asignaciones_incidencia` (`id_incidencia`),
  KEY `fk_asignaciones_usuario` (`id_usuario`),
  CONSTRAINT `fk_asignaciones_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incidencia_asignaciones` (`id_asignacion`, `id_incidencia`, `id_usuario`, `rol_asignacion`, `fecha_asignacion`) VALUES
(1, 1, 2, 'responsable', NOW()),
(2, 2, 3, 'responsable', NOW()),
(3, 3, 4, 'responsable', NOW()),
(4, 5, 3, 'responsable', NOW()),
(5, 8, 3, 'responsable', NOW());

-- -----------------------------------------------------
-- Tabla: incidencia_apoyos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencia_apoyos` (
  `id_apoyo` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `monto_incentivo` decimal(10,2) NOT NULL,
  `estado_pago` enum('pendiente_aprobacion','aprobado','rechazado','pagado') DEFAULT 'pendiente_aprobacion',
  `comentario_usuario` varchar(255) DEFAULT NULL,
  `id_admin_revisor` int(11) DEFAULT NULL,
  `comentario_admin` varchar(255) DEFAULT NULL,
  `fecha_apoyo` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_revision` datetime DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  PRIMARY KEY (`id_apoyo`),
  UNIQUE KEY `uk_incidencia_usuario_apoyo` (`id_incidencia`,`id_usuario`),
  KEY `fk_apoyos_incidencia` (`id_incidencia`),
  KEY `fk_apoyos_usuario` (`id_usuario`),
  KEY `fk_apoyos_admin` (`id_admin_revisor`),
  CONSTRAINT `fk_apoyos_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_apoyos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_apoyos_admin` FOREIGN KEY (`id_admin_revisor`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incidencia_apoyos` (`id_apoyo`, `id_incidencia`, `id_usuario`, `monto_incentivo`, `estado_pago`, `comentario_usuario`, `id_admin_revisor`, `comentario_admin`, `fecha_apoyo`, `fecha_revision`, `fecha_pago`) VALUES
(1, 1, 2, 20.00, 'aprobado', 'Puedo ayudar con la configuracion', 1, 'Aprobado por prioridad alta', NOW(), NOW(), NULL),
(2, 2, 4, 20.00, 'pendiente_aprobacion', 'Disponible para revisar el problema electrico', NULL, NULL, NOW(), NULL, NULL),
(3, 3, 2, 20.00, 'aprobado', 'Tengo experiencia con reparaciones de filtraciones', 1, 'Aprobado', NOW(), NOW(), NULL);

-- -----------------------------------------------------
-- Tabla: incidencia_estados_historial
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencia_estados_historial` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `id_estado_anterior` int(11) DEFAULT NULL,
  `id_estado_nuevo` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `fk_historial_incidencia` (`id_incidencia`),
  KEY `fk_historial_estado_anterior` (`id_estado_anterior`),
  KEY `fk_historial_estado_nuevo` (`id_estado_nuevo`),
  KEY `fk_historial_usuario` (`id_usuario`),
  CONSTRAINT `fk_historial_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_historial_estado_anterior` FOREIGN KEY (`id_estado_anterior`) REFERENCES `estados` (`id_estado`),
  CONSTRAINT `fk_historial_estado_nuevo` FOREIGN KEY (`id_estado_nuevo`) REFERENCES `estados` (`id_estado`),
  CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incidencia_estados_historial` (`id_historial`, `id_incidencia`, `id_estado_anterior`, `id_estado_nuevo`, `id_usuario`, `comentario`, `fecha_cambio`) VALUES
(1, 1, NULL, 1, 2, 'Incidencia registrada, pendiente de revision', NOW()),
(2, 1, 1, 2, 1, 'Incidencia aprobada y asignada a Carlos Mendoza', NOW()),
(3, 2, NULL, 1, 3, 'Incidencia registrada, pendiente de revision', NOW()),
(4, 2, 1, 2, 1, 'Incidencia aprobada y asignada a Maria Gonzalez', NOW()),
(5, 4, NULL, 1, 2, 'Incidencia registrada, pendiente de revision', NOW()),
(6, 4, 1, 2, 1, 'Incidencia aprobada', NOW()),
(7, 4, 2, 3, 2, 'Impresora reparada, problema resuelto', NOW());

-- -----------------------------------------------------
-- Tabla: incidencia_comentarios
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencia_comentarios` (
  `id_comentario` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `comentario` text NOT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_comentario`),
  KEY `fk_comentarios_incidencia` (`id_incidencia`),
  KEY `fk_comentarios_usuario` (`id_usuario`),
  CONSTRAINT `fk_comentarios_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_comentarios_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `incidencia_comentarios` (`id_comentario`, `id_incidencia`, `id_usuario`, `comentario`, `fecha`) VALUES
(1, 1, 2, 'Reiniciando el servidor para verificar el problema', NOW()),
(2, 1, 1, 'Por favor mantenganme informado del progreso', NOW()),
(3, 2, 3, 'Contactando al departamento de mantenimiento electrico', NOW()),
(4, 4, 2, 'Se reemplazo el rodillo de la impresora', NOW()),
(5, 5, 3, 'Verificando la configuracion del router', NOW());

-- -----------------------------------------------------
-- Tabla: notificaciones
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_incidencia` int(11) DEFAULT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notificacion`),
  KEY `fk_notificaciones_usuario` (`id_usuario`),
  KEY `fk_notificaciones_incidencia` (`id_incidencia`),
  KEY `idx_usuario_leida` (`id_usuario`,`leida`),
  CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_notificaciones_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `id_incidencia`, `titulo`, `mensaje`, `leida`, `fecha`) VALUES
(1, 1, 1, 'Nueva incidencia por revisar', '"Falla en servidor principal" necesita tu aprobacion.', 0, NOW()),
(2, 1, 2, 'Nueva incidencia por revisar', '"Corte de energia en piso 2" necesita tu aprobacion.', 0, NOW()),
(3, 2, 1, 'Incidencia asignada', 'Has sido asignado como responsable de la incidencia #1', 1, NOW()),
(4, 3, 2, 'Incidencia asignada', 'Has sido asignado como responsable de la incidencia #2', 1, NOW()),
(5, 2, 4, 'Actualizacion de incidencia', 'La incidencia #4 ha sido resuelta', 0, NOW());

-- -----------------------------------------------------
-- Tabla: historial_actividad
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_actividad` (
  `id_actividad` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `id_incidencia` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_origen` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_actividad`),
  KEY `fk_historial_actividad_usuario` (`id_usuario`),
  KEY `fk_historial_actividad_incidencia` (`id_incidencia`),
  KEY `idx_fecha_hora` (`fecha_hora`),
  KEY `idx_id_usuario` (`id_usuario`),
  KEY `idx_accion` (`accion`),
  CONSTRAINT `fk_historial_actividad_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_historial_actividad_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `historial_actividad` (`id_actividad`, `id_usuario`, `id_incidencia`, `accion`, `detalle`, `fecha_hora`, `ip_origen`) VALUES
(1, 2, 1, 'creo_incidencia', 'Carlos Mendoza registro la incidencia "Falla en servidor principal"', NOW(), '192.168.1.100'),
(2, 3, 2, 'creo_incidencia', 'Maria Gonzalez registro la incidencia "Corte de energia en piso 2"', NOW(), '192.168.1.101'),
(3, 1, 1, 'aprobo_incidencia', 'Admin Sistema aprobo la incidencia #1', NOW(), '192.168.1.1'),
(4, 1, 2, 'aprobo_incidencia', 'Admin Sistema aprobo la incidencia #2', NOW(), '192.168.1.1'),
(5, 2, 1, 'agrego_comentario', 'Carlos Mendoza agrego un comentario a la incidencia #1', NOW(), '192.168.1.100'),
(6, 2, 4, 'cambio_estado', 'Carlos Mendoza cambio el estado de la incidencia #4 a Resuelto', NOW(), '192.168.1.100');

-- -----------------------------------------------------
-- Tabla: personal_access_tokens (Laravel Sanctum)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
