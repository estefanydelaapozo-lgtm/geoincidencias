-- Script para crear usuarios en Aiven
-- Ejecuta esto en DataGrip o en la interfaz SQL de Aiven

INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password, rol, telefono, saldo_incentivos, activo, created_at) VALUES
(1, 'Admin', 'Sistema', 'admin@geoincidencias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0990000000', 0.00, 1, NOW())
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido), password=VALUES(password), rol=VALUES(rol);

INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password, rol, telefono, saldo_incentivos, activo, created_at) VALUES
(2, 'Carlos', 'Mendoza', 'cmendoza@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0991234567', 20.00, 1, NOW())
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido), password=VALUES(password), rol=VALUES(rol);

INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password, rol, telefono, saldo_incentivos, activo, created_at) VALUES
(3, 'Maria', 'Gonzalez', 'mgonzalez@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', '0992345678', 0.00, 1, NOW())
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellido=VALUES(apellido), password=VALUES(password), rol=VALUES(rol);

-- Verificar usuarios creados
SELECT id_usuario, nombre, correo, rol, activo FROM usuarios;
