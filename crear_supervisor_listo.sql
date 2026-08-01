-- ============================================================
-- Crea (o actualiza si ya existe) una cuenta Supervisor lista
-- para usar, con credenciales conocidas.
--
-- Correo:      supervisor@geoincidencias.com
-- Contraseña:  123456
-- ============================================================

INSERT INTO usuarios (nombre, apellido, correo, password, rol, id_rol, activo, created_at, updated_at)
VALUES (
  'Supervisor', 'Demo',
  'supervisor@geoincidencias.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- hash de "123456"
  'supervisor',
  (SELECT id_rol FROM roles WHERE slug = 'supervisor'),
  1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  rol = 'supervisor',
  id_rol = (SELECT id_rol FROM roles WHERE slug = 'supervisor'),
  activo = 1;

-- Verifica que quedó bien:
SELECT id_usuario, nombre, correo, rol, id_rol, activo FROM usuarios
WHERE correo = 'supervisor@geoincidencias.com';
