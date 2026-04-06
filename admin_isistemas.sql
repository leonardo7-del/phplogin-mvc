USE patron_mvc;

INSERT INTO usuarios (nombre, email, password, rol, creado_en)
VALUES (
    'Carlos',
    'isistemas2022@gmail.com',
    '$2y$10$1bMSQpOVo.EtwWahox31QuhUf/9Hmy6fwCZfx8G9TRFDVrUpHTmba',
    'admin',
    NOW()
)
ON DUPLICATE KEY UPDATE
    rol = 'admin',
    email = VALUES(email);

-- Password temporal en texto plano: Admin123*
-- Cambiala luego iniciando sesion o actualizando el hash.
