-- ============================================================
-- Diagnóstico y reseteo del usuario Supervisor
-- ============================================================

-- 1) Verifica si el usuario existe, si está activo y qué rol tiene
--    (reemplaza el correo por el que estés usando)
SELECT id_usuario, nombre, correo, rol, id_rol, activo
FROM usuarios
WHERE correo = 'supervisor@geoincidencias.com';   -- <-- pon aquí el correo real

-- Si la fila NO aparece: el usuario no existe, créalo desde
-- Usuarios (ya corregido) o usa el INSERT de la entrega anterior.
--
-- Si aparece con activo = 0: ese es el motivo de "Credenciales
-- incorrectas" (el login exige activo = 1). Corrígelo con:
UPDATE usuarios SET activo = 1
WHERE correo = 'supervisor@geoincidencias.com';

-- 2) Si no estás seguro de la contraseña, resetéala a "123456"
--    (mismo hash de prueba que ya trae el proyecto en crear_usuarios.sql)
UPDATE usuarios
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE correo = 'supervisor@geoincidencias.com';

-- Después de esto, inicia sesión con:
--   correo:      supervisor@geoincidencias.com  (el que hayas puesto)
--   contraseña:  123456
