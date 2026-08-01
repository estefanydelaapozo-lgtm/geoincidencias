-- Script SQL para crear usuarios de emergencia
-- Ejecutar esto en phpMyAdmin: http://localhost:8081

USE geoincidencias;

-- Crear tabla usuarios si no existe
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    correo VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
    telefono VARCHAR(20),
    saldo_incentivos DECIMAL(10,2) DEFAULT 0.00,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eliminar usuarios existentes (opcional, comentar si quieres mantener los existentes)
DELETE FROM usuarios WHERE correo IN ('admin@geoincidencias.com', 'cmendoza@empresa.com', 'mgonzalez@empresa.com');

-- Insertar usuarios con contraseña: 123456
-- Hash generado con Laravel: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password, rol, telefono, saldo_incentivos, activo, created_at) VALUES
(1, 'Admin', 'Sistema', 'admin@geoincidencias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0990000000', 0.00, 1, NOW()),
(2, 'Carlos', 'Mendoza', 'cmendoza@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0991234567', 20.00, 1, NOW()),
(3, 'Maria', 'Gonzalez', 'mgonzalez@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0992345678', 0.00, 1, NOW());

-- Verificar usuarios creados
SELECT id_usuario, nombre, correo, rol, activo FROM usuarios;
