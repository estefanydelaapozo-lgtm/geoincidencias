-- ============================================================
-- Crea (o actualiza si ya existen) UN usuario de prueba por
-- cada rol institucional, con credenciales conocidas.
--
-- Sigue el mismo patrón seguro que ya usa crear_supervisor_listo.sql:
-- el id_rol SIEMPRE se llena con una subconsulta a roles.slug,
-- así nunca queda en NULL (esa fue la causa de que Policía no
-- viera nada en su panel).
--
-- Contraseña para todos: 123456
-- ============================================================

INSERT INTO usuarios (nombre, apellido, correo, password, rol, id_rol, activo, created_at, updated_at) VALUES
('Policía', 'Demo', 'policia@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'policia',
 (SELECT id_rol FROM roles WHERE slug = 'policia'), 1, NOW(), NOW()),

('Bomberos', 'Demo', 'bomberos@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bomberos',
 (SELECT id_rol FROM roles WHERE slug = 'bomberos'), 1, NOW(), NOW()),

('Salud', 'Demo', 'salud@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'salud',
 (SELECT id_rol FROM roles WHERE slug = 'salud'), 1, NOW(), NOW()),

('Eléctrica', 'Demo', 'electrica@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'electrica',
 (SELECT id_rol FROM roles WHERE slug = 'electrica'), 1, NOW(), NOW()),

('Agua Potable', 'Demo', 'agua@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agua',
 (SELECT id_rol FROM roles WHERE slug = 'agua'), 1, NOW(), NOW()),

('Obras Públicas', 'Demo', 'obras@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'obras_publicas',
 (SELECT id_rol FROM roles WHERE slug = 'obras_publicas'), 1, NOW(), NOW()),

('Medio Ambiente', 'Demo', 'ambiente@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medio_ambiente',
 (SELECT id_rol FROM roles WHERE slug = 'medio_ambiente'), 1, NOW(), NOW()),

('Técnico', 'Demo', 'tecnico@geoincidencias.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico',
 (SELECT id_rol FROM roles WHERE slug = 'tecnico'), 1, NOW(), NOW())

ON DUPLICATE KEY UPDATE
  password   = VALUES(password),
  rol        = VALUES(rol),
  id_rol     = VALUES(id_rol),
  activo     = 1;

-- Verifica que todos quedaron con id_rol correcto (ninguna fila
-- debería salir con id_rol en NULL):
SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.id_rol, r.slug AS rol_de_la_tabla_roles
FROM usuarios u
LEFT JOIN roles r ON r.id_rol = u.id_rol
WHERE u.correo IN (
  'policia@geoincidencias.com','bomberos@geoincidencias.com','salud@geoincidencias.com',
  'electrica@geoincidencias.com','agua@geoincidencias.com','obras@geoincidencias.com',
  'ambiente@geoincidencias.com','tecnico@geoincidencias.com'
);
