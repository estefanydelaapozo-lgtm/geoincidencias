-- GeoIncidencias - Exportación de Base de Datos
-- Sistema de Gestión de Incidencias Georreferenciadas
-- Configuración para Aiven Cloud
-- Generado: 2026-07-01

-- ── Script SQL para crear las tablas en Aiven ──

-- 1. Eliminar tablas si existen
DROP TABLE IF EXISTS historial_actividad;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS incidencia_comentarios;
DROP TABLE IF EXISTS incidencia_estados_historial;
DROP TABLE IF EXISTS incidencia_apoyos;
DROP TABLE IF EXISTS incidencia_asignaciones;
DROP TABLE IF EXISTS incidencias;
DROP TABLE IF EXISTS personal_access_tokens;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS incentivos_prioridad;
DROP TABLE IF EXISTS estados;
DROP TABLE IF EXISTS subtipos_incidencia;
DROP TABLE IF EXISTS tipos_incidencia;
DROP TABLE IF EXISTS zonas;
DROP TABLE IF EXISTS ciudades;
DROP TABLE IF EXISTS provincias;
DROP TABLE IF EXISTS paises;

-- 2. Crear tabla paises
CREATE TABLE paises (
  id_pais INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  codigo_iso VARCHAR(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Crear tabla provincias
CREATE TABLE provincias (
  id_provincia INT AUTO_INCREMENT PRIMARY KEY,
  id_pais INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_pais) REFERENCES paises(id_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Crear tabla ciudades
CREATE TABLE ciudades (
  id_ciudad INT AUTO_INCREMENT PRIMARY KEY,
  id_provincia INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  latitud_ref DECIMAL(10,6) DEFAULT NULL,
  longitud_ref DECIMAL(10,6) DEFAULT NULL,
  FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Crear tabla zonas (corregido: todas apuntan a Guayaquil)
CREATE TABLE zonas (
  id_zona INT AUTO_INCREMENT PRIMARY KEY,
  id_ciudad INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  latitud_ref DECIMAL(10,6) DEFAULT NULL,
  longitud_ref DECIMAL(10,6) DEFAULT NULL,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (id_ciudad) REFERENCES ciudades(id_ciudad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Crear tabla tipos_incidencia
CREATE TABLE tipos_incidencia (
  id_tipo INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  icono VARCHAR(50) DEFAULT NULL,
  color VARCHAR(7) DEFAULT NULL,
  activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Crear tabla subtipos_incidencia
CREATE TABLE subtipos_incidencia (
  id_subtipo INT AUTO_INCREMENT PRIMARY KEY,
  id_tipo INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (id_tipo) REFERENCES tipos_incidencia(id_tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Crear tabla estados
CREATE TABLE estados (
  id_estado INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  descripcion TEXT,
  color VARCHAR(7) DEFAULT NULL,
  orden INT DEFAULT NULL,
  activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Crear tabla incentivos_prioridad
CREATE TABLE incentivos_prioridad (
  id_incentivo INT AUTO_INCREMENT PRIMARY KEY,
  prioridad VARCHAR(20) NOT NULL UNIQUE,
  monto DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Crear tabla usuarios
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) DEFAULT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  telefono VARCHAR(20) DEFAULT NULL,
  saldo_incentivos DECIMAL(10,2) DEFAULT 0.00,
  activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Crear tabla incidencias
CREATE TABLE incidencias (
  id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT,
  prioridad ENUM('Baja','Media','Alta') NOT NULL,
  id_tipo INT NOT NULL,
  id_subtipo INT DEFAULT NULL,
  id_estado_actual INT NOT NULL,
  estado_aprobacion ENUM('pendiente_revision','aprobada','rechazada') DEFAULT 'pendiente_revision',
  id_admin_revisor INT DEFAULT NULL,
  fecha_revision DATETIME DEFAULT NULL,
  motivo_rechazo TEXT,
  id_zona INT NOT NULL,
  latitud DECIMAL(10,6) DEFAULT NULL,
  longitud DECIMAL(10,6) DEFAULT NULL,
  direccion_texto VARCHAR(255) DEFAULT NULL,
  fecha_ocurrencia DATE NOT NULL,
  hora_ocurrencia TIME DEFAULT NULL,
  fecha_resolucion DATETIME DEFAULT NULL,
  tiempo_resolucion_horas DECIMAL(10,2) DEFAULT NULL,
  reportante_nombre VARCHAR(100) DEFAULT NULL,
  reportante_contacto VARCHAR(20) DEFAULT NULL,
  id_usuario_creador INT NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_tipo) REFERENCES tipos_incidencia(id_tipo),
  FOREIGN KEY (id_subtipo) REFERENCES subtipos_incidencia(id_subtipo),
  FOREIGN KEY (id_estado_actual) REFERENCES estados(id_estado),
  FOREIGN KEY (id_zona) REFERENCES zonas(id_zona),
  FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_admin_revisor) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Crear tabla personal_access_tokens (Laravel Sanctum)
CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Crear tabla incidencia_asignaciones
CREATE TABLE incidencia_asignaciones (
  id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
  id_incidencia INT NOT NULL,
  id_usuario INT NOT NULL,
  rol_asignacion ENUM('responsable','apoyo') DEFAULT 'responsable',
  fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (id_incidencia, id_usuario),
  FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Crear tabla incidencia_apoyos
CREATE TABLE incidencia_apoyos (
  id_apoyo INT AUTO_INCREMENT PRIMARY KEY,
  id_incidencia INT NOT NULL,
  id_usuario INT NOT NULL,
  monto_incentivo DECIMAL(10,2) NOT NULL,
  estado_pago ENUM('pendiente_aprobacion','aprobado','rechazado','pagado') DEFAULT 'pendiente_aprobacion',
  comentario_usuario VARCHAR(255) DEFAULT NULL,
  id_admin_revisor INT DEFAULT NULL,
  comentario_admin VARCHAR(255) DEFAULT NULL,
  fecha_apoyo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_revision DATETIME DEFAULT NULL,
  fecha_pago DATETIME DEFAULT NULL,
  UNIQUE KEY (id_incidencia, id_usuario),
  FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_admin_revisor) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Crear tabla incidencia_estados_historial
CREATE TABLE incidencia_estados_historial (
  id_historial INT AUTO_INCREMENT PRIMARY KEY,
  id_incidencia INT NOT NULL,
  id_estado_anterior INT DEFAULT NULL,
  id_estado_nuevo INT NOT NULL,
  id_usuario INT DEFAULT NULL,
  comentario VARCHAR(255) DEFAULT NULL,
  fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
  FOREIGN KEY (id_estado_anterior) REFERENCES estados(id_estado),
  FOREIGN KEY (id_estado_nuevo) REFERENCES estados(id_estado),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Crear tabla incidencia_comentarios
CREATE TABLE incidencia_comentarios (
  id_comentario INT AUTO_INCREMENT PRIMARY KEY,
  id_incidencia INT NOT NULL,
  id_usuario INT DEFAULT NULL,
  comentario TEXT NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Crear tabla notificaciones
CREATE TABLE notificaciones (
  id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_incidencia INT DEFAULT NULL,
  titulo VARCHAR(150) NOT NULL,
  mensaje VARCHAR(255) DEFAULT NULL,
  leida TINYINT(1) DEFAULT 0,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Crear tabla historial_actividad
CREATE TABLE historial_actividad (
  id_actividad INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT DEFAULT NULL,
  id_incidencia INT DEFAULT NULL,
  accion VARCHAR(100) NOT NULL,
  detalle VARCHAR(255) DEFAULT NULL,
  fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ip_origen VARCHAR(45) DEFAULT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Insertar datos iniciales
INSERT INTO paises (id_pais, nombre, codigo_iso) VALUES (1, 'Ecuador', 'EC');

INSERT INTO provincias (id_provincia, id_pais, nombre) VALUES
(1, 1, 'Guayas'),
(2, 1, 'Pichincha'),
(3, 1, 'Santa Elena');

INSERT INTO ciudades (id_ciudad, id_provincia, nombre, latitud_ref, longitud_ref) VALUES
(1, 1, 'Guayaquil', -2.170998, -79.922359),
(2, 2, 'Quito', -0.180653, -78.467838),
(3, 3, 'La Libertad', -2.232450, -80.905610);

-- CORREGIDO: Todas las zonas ahora apuntan a Guayaquil (id_ciudad = 1)
INSERT INTO zonas (id_ciudad, nombre, descripcion, latitud_ref, longitud_ref, activo) VALUES
(1, 'Planta Baja', 'Area de recepcion y acceso principal', -2.900100, -79.005900, 1),
(1, 'Piso 1', 'Oficinas administrativas', -2.900200, -79.005800, 1),
(1, 'Piso 2', 'Area tecnica y sistemas', -2.900300, -79.005700, 1),
(1, 'Piso 3', 'Gerencia y salas de reuniones', -2.900400, -79.005600, 1),
(1, 'Bodega', 'Almacen y logistica', -2.900500, -79.005500, 1),
(1, 'Parqueadero', 'Zona de parqueo vehicular', -2.900600, -79.005400, 1),
(1, 'Sala de Servidores', 'Centro de datos principal', -2.900700, -79.005300, 1),
(1, 'Cafeteria', 'Area de descanso y comedor', -2.900800, -79.005200, 1);

INSERT INTO tipos_incidencia (id_tipo, nombre, descripcion, icono, color, activo) VALUES
(1, 'Infraestructura', 'Danos en instalaciones fisicas', 'bi-building', '#f97316', 1),
(2, 'Equipos TI', 'Fallas en hardware o software', 'bi-pc-display', '#6366f1', 1),
(3, 'Red y Conectividad', 'Problemas de red, internet o telefonia', 'bi-wifi-off', '#3b82f6', 1),
(4, 'Seguridad', 'Incidentes de seguridad fisica o digital', 'bi-shield-exclamation', '#ef4444', 1),
(5, 'Suministros', 'Falta o dano de materiales', 'bi-box-seam', '#eab308', 1),
(6, 'Servicios Basicos', 'Agua, luz, clima, aseo', 'bi-lightning-charge', '#10b981', 1),
(7, 'Accidentes', 'Accidentes laborales o de transito', 'bi-bandaid', '#f43f5e', 1);

INSERT INTO subtipos_incidencia (id_tipo, nombre, activo) VALUES
(1, 'Alumbrado', 1),
(1, 'Goteras y Filtraciones', 1),
(1, 'Puertas y accesos', 1),
(1, 'Mobiliario danado', 1),
(2, 'Computador no enciende', 1),
(2, 'Error de software', 1),
(2, 'Impresora', 1),
(2, 'Perdida de datos', 1),
(3, 'Internet lento', 1),
(3, 'Sin conexion', 1),
(3, 'Telefonia IP', 1),
(4, 'Robo', 1),
(4, 'Acceso no autorizado', 1),
(4, 'Camara danada', 1),
(4, 'Alarma activada', 1),
(5, 'Falta de insumos de oficina', 1),
(5, 'Falta de equipo de proteccion', 1),
(6, 'Corte de energia', 1),
(6, 'Falla de climatizacion', 1),
(6, 'Falta de agua', 1),
(7, 'Accidente laboral', 1),
(7, 'Accidente vehicular', 1);

INSERT INTO estados (id_estado, nombre, descripcion, color, orden, activo) VALUES
(1, 'Pendiente', 'Incidencia reportada, aun no atendida', '#ef4444', 1, 1),
(2, 'En proceso', 'Incidencia siendo atendida por el responsable', '#f59e0b', 2, 1),
(3, 'Resuelto', 'Incidencia solucionada', '#22c55e', 3, 1),
(4, 'Cerrado', 'Incidencia verificada y cerrada oficialmente', '#64748b', 4, 1);

INSERT INTO incentivos_prioridad (prioridad, monto) VALUES
('Baja', 5.00),
('Media', 10.00),
('Alta', 20.00);

-- Usuarios con contraseña: 123456 (hash compatible con Laravel)
INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password, rol, telefono, saldo_incentivos, activo, created_at) VALUES
(1, 'Admin', 'Sistema', 'admin@geoincidencias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0990000000', 0.00, 1, NOW()),
(2, 'Carlos', 'Mendoza', 'cmendoza@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0991234567', 20.00, 1, NOW()),
(3, 'Maria', 'Gonzalez', 'mgonzalez@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0992345678', 0.00, 1, NOW());

-- Verificar que se crearon las tablas
SHOW TABLES;
