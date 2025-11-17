-- docker run -d --name mis_rutas_db -e MYSQL_ROOT_PASSWORD=rpwd -e MYSQL_DATABASE=mis_rutas -p 4000:3306 -v mis_rutas_data:/var/lib/mysql mysql


CREATE DATABASE IF NOT EXISTS mis_rutas
   CHARACTER SET utf8mb4
   COLLATE utf8mb4_unicode_ci;

USE mis_rutas;




CREATE TABLE IF NOT EXISTS users (
   id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   name           VARCHAR(100) NOT NULL,
   email          VARCHAR(150) NOT NULL UNIQUE,
   password_hash  VARCHAR(255) NOT NULL,
   created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS user_settings (
   id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   user_id                   INT UNSIGNED NOT NULL UNIQUE,
   theme                     VARCHAR(20)  NOT NULL DEFAULT 'default',
   items_per_page            TINYINT UNSIGNED NOT NULL DEFAULT 10,
   email_notifications       BOOLEAN  NOT NULL DEFAULT TRUE,
   created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
   CONSTRAINT fk_user_settings_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE,
   INDEX idx_user_settings_user (user_id)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS routes (
   id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   user_id            INT UNSIGNED NOT NULL,
   title              VARCHAR(150) NOT NULL,
   description        TEXT         NOT NULL,
   route_date         DATE         DEFAULT NULL,
   start_location     VARCHAR(150) DEFAULT NULL,
   end_location       VARCHAR(150) DEFAULT NULL,
   time_hours         TINYINT UNSIGNED DEFAULT NULL,
   time_minutes       TINYINT UNSIGNED DEFAULT NULL,
   difficulty         ENUM('facil','media','dificil','experto') DEFAULT NULL,
   photo_path         VARCHAR(255) DEFAULT NULL,
   created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
   CONSTRAINT fk_routes_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE,
   INDEX idx_routes_user (user_id),
   INDEX idx_routes_date (route_date),
   INDEX idx_routes_difficulty (difficulty)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS tags (
   id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   name       VARCHAR(100) NOT NULL UNIQUE,
   slug       VARCHAR(120) DEFAULT NULL,
   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS route_tags (
   route_id INT UNSIGNED NOT NULL,
   tag_id   INT UNSIGNED NOT NULL,
   PRIMARY KEY (route_id, tag_id),
   CONSTRAINT fk_route_tags_route
     FOREIGN KEY (route_id) REFERENCES routes(id)
     ON DELETE CASCADE,
   CONSTRAINT fk_route_tags_tag
     FOREIGN KEY (tag_id) REFERENCES tags(id)
     ON DELETE CASCADE,
   INDEX idx_route_tags_route (route_id),
   INDEX idx_route_tags_tag (tag_id)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS comments (
   id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   route_id   INT UNSIGNED NOT NULL,
   user_id    INT UNSIGNED NOT NULL,
   content    TEXT NOT NULL,
   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   CONSTRAINT fk_comments_route
     FOREIGN KEY (route_id) REFERENCES routes(id)
     ON DELETE CASCADE,
   CONSTRAINT fk_comments_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE,
   INDEX idx_comments_route (route_id),
   INDEX idx_comments_user (user_id)
) ENGINE=InnoDB;




CREATE TABLE IF NOT EXISTS li_dis_route (
   id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   user_id            INT UNSIGNED NOT NULL,
   route_id           INT UNSIGNED NOT NULL,
   dis_li             BOOLEAN NOT NULL, 
   created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   CONSTRAINT fk_li_dis_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE,
   CONSTRAINT fk_li_dis_route
     FOREIGN KEY (route_id) REFERENCES routes(id)
     ON DELETE CASCADE,
   UNIQUE KEY uq_li_dis_route (user_id, route_id),
   INDEX idx_li_dis_user (user_id),
   INDEX idx_li_dis_route (route_id)
) ENGINE=InnoDB;


SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE mis_rutas;




INSERT INTO users (id, name, email, password_hash, created_at, updated_at) VALUES
(1, 'Marcos', 'marcos@example.com', '$2b$12$bgZLwzusFKcuyJzH7WMuOexBaSwdmtoKu5ogmZoZG1521SzCby95K', NOW(), NOW()), 
(2, 'Laura',  'laura@example.com',  '$2b$12$HZ.rt6HKThRWFKkti4qGGuoFamynMbo2HBCxIfPh.gV0TY18upYJu', NOW(), NOW()), 
(3, 'Carlos', 'carlos@example.com', '$2b$12$Z7jUtzPGVmeO18mndeC.i.XRD5XuwqJXkPfHZVMwvpQiawsr0.KUC', NOW(), NOW()), 
(4, 'Ana',    'ana@example.com',    '$2b$12$YJoK/qDu632CQC/7g4.yXuTnIrKPCE0HY4uOG268fXpmhS9Qi7EKi', NOW(), NOW()), 
(5, 'Admin',  'admin@example.com',  '$2b$12$AoXNzGKCDEoS0bPnTv43uu4vq3PXcCZsgHLBjUHZUbDcmfcYZxMkO', NOW(), NOW()); 




INSERT INTO user_settings (user_id, theme, items_per_page, email_notifications, created_at, updated_at) VALUES
(1, 'dark',   5,  1, NOW(), NOW()),
(2, 'light', 10, 1, NOW(), NOW()),
(3, 'system',10, 1, NOW(), NOW()),
(4, 'dark',  20, 1, NOW(), NOW()),
(5, 'light', 50, 0, NOW(), NOW());




INSERT INTO tags (id, name, slug, created_at) VALUES
(1,  'Bosque',           'bosque',           NOW()),
(2,  'Montaña',          'montana',          NOW()),
(3,  'Río',              'rio',              NOW()),
(4,  'Costa',            'costa',            NOW()),
(5,  'Circular',         'circular',         NOW()),
(6,  'Cascada',          'cascada',          NOW()),
(7,  'Familiar',         'familiar',         NOW()),
(8,  'Nocturna',         'nocturna',         NOW()),
(9,  'Nieve',            'nieve',            NOW()),
(10, 'Mirador',          'mirador',          NOW()),
(11, 'Sendero local',    'sendero-local',    NOW()),
(12, 'Alta montaña',     'alta-montana',     NOW());




INSERT INTO routes
(id, user_id, title, description, route_date, start_location, end_location,
 time_hours, time_minutes, difficulty, photo_path, created_at, updated_at)
VALUES
(1,  1, 'Circular al Pico Norte',
 'Ruta circular con buenas vistas al valle y subida suave al Pico Norte.',
 '2024-05-01', 'Pueblo Verde', 'Pico Norte', 4, 30, 'media', './img/ruta-1.jpg', NOW(), NOW()),
(2,  2, 'Bosque de las Hayedas',
 'Senda por un bosque de hayas muy sombrío y fresco, ideal en verano.',
 '2024-05-04', 'Pueblo Rojo', 'Collado del Haya', 3, 15, 'facil', './img/ruta-2.jpg', NOW(), NOW()),
(3,  3, 'Ruta de los Miradores',
 'Recorrido por varios miradores con vistas espectaculares del valle.',
 '2024-05-07', 'Mirador Bajo', 'Mirador Alto', 5, 0, 'media', './img/ruta-3.jpg', NOW(), NOW()),
(4,  4, 'Ascensión al Alto del Viento',
 'Ascensión continua con tramos pedregosos y fuerte desnivel.',
 '2024-05-10', 'Refugio La Vega', 'Alto del Viento', 6, 0, 'dificil', './img/ruta-4.jpg', NOW(), NOW()),
(5,  5, 'Senda del Río Claro',
 'Ruta sencilla siguiendo el cauce del río, con varias zonas de baño.',
 '2024-05-13', 'Área Recreativa Río Claro', 'Puente Viejo', 3, 0, 'facil', './img/ruta-1.jpg', NOW(), NOW()),
(6,  1, 'Travesía de las Viniegras',
 'Travesía de media montaña cruzando varias aldeas tradicionales.',
 '2024-05-16', 'Viniegra Baja', 'Viniegra Alta', 7, 0, 'media', './img/vini-intro.jpg', NOW(), NOW()),
(7,  2, 'Crestas de los Cameros',
 'Itinerario exigente por crestas con tramos aéreos y grandes panorámicas.',
 '2024-05-19', 'Collado del Lobo', 'Pico del Águila', 7, 30, 'experto', './img/montanas-intro.jpg', NOW(), NOW()),
(8,  3, 'Vuelta al Pantano de Piarrejas',
 'Circular alrededor del pantano, con varios miradores y sendas fáciles.',
 '2024-05-22', 'Aparcamiento Piarrejas', 'Aparcamiento Piarrejas', 4, 0, 'facil', './img/piarrejas-intro.jpg', NOW(), NOW()),
(9,  4, 'Senda del Robledal Encantado',
 'Ruta entre robles centenarios, ideal para otoño por los colores.',
 '2024-05-25', 'Pueblo del Robledal', 'Collado del Roble', 3, 45, 'media', './img/ruta-2.jpg', NOW(), NOW()),
(10, 5, 'Ruta Nocturna a Trevijano',
 'Ruta guiada nocturna para ver estrellas y el pueblo de Trevijano.',
 '2024-05-28', 'Aparcamiento Trevijano', 'Trevijano', 2, 30, 'facil', './img/trevi-intro.jpg', NOW(), NOW()),
(11, 1, 'Collado de las Nubes',
 'Subida progresiva hasta un collado con vistas a varias sierras.',
 '2024-06-01', 'Pueblo Azul', 'Collado de las Nubes', 5, 15, 'media', './img/ruta-3.jpg', NOW(), NOW()),
(12, 2, 'Desfiladero del Águila',
 'Tramo estrecho junto al río, con paredes verticales y pasarelas.',
 '2024-06-04', 'Área del Río', 'Puente del Águila', 4, 0, 'dificil', './img/ruta-4.jpg', NOW(), NOW()),
(13, 3, 'Senda de los Pastores',
 'Ruta histórica siguiendo antiguos caminos de pastores.',
 '2024-06-07', 'Majada Vieja', 'Refugio Pastoril', 4, 45, 'media', './img/ruta-1.jpg', NOW(), NOW()),
(14, 4, 'Cascadas del Arroyo Frío',
 'Ruta corta con varias cascadas y pozas de agua cristalina.',
 '2024-06-10', 'Área Arroyo Frío', 'Cascada Mayor', 2, 30, 'facil', './img/ruta-2.jpg', NOW(), NOW()),
(15, 5, 'Circular de las Tres Cimas',
 'Exigente circular coronando tres cimas en una sola jornada.',
 '2024-06-13', 'Parking Montaña', 'Parking Montaña', 8, 0, 'experto', './img/montanas-intro.jpg', NOW(), NOW()),
(16, 1, 'Laguna Oculta',
 'Ruta de media montaña que termina en una pequeña laguna escondida.',
 '2024-06-16', 'Bosque Alto', 'Laguna Oculta', 4, 30, 'media', './img/ruta-3.jpg', NOW(), NOW()),
(17, 2, 'Pico del Guardián',
 'Ascensión con fuerte pendiente final y vistas a todo el valle.',
 '2024-06-19', 'Pueblo del Valle', 'Pico del Guardián', 6, 15, 'dificil', './img/ruta-4.jpg', NOW(), NOW()),
(18, 3, 'Barranco de las Flores',
 'Senda primaveral con mucha vegetación y flores silvestres.',
 '2024-06-22', 'Área Barranco', 'Collado Flores', 3, 30, 'facil', './img/ruta-1.jpg', NOW(), NOW()),
(19, 4, 'Bosque de Niebla',
 'Ruta con ambiente húmedo y nieblas frecuentes, muy atmosférica.',
 '2024-06-25', 'Pueblo Niebla', 'Mirador Nublado', 4, 15, 'media', './img/ruta-2.jpg', NOW(), NOW()),
(20, 5, 'Ruta Familiar de Valverde',
 'Recorrido fácil y corto para hacer en familia cerca de Valverde.',
 '2024-06-28', 'Valverde', 'Área de Picnic', 2, 0, 'facil', './img/ruta-1.jpg', NOW(), NOW()),
(21, 1, 'Etapa de Alta Montaña',
 'Larga etapa de alta montaña con pasos técnicos y fuerte desnivel.',
 '2024-07-01', 'Refugio Base', 'Refugio Alto', 9, 0, 'experto', './img/montanas-intro.jpg', NOW(), NOW()),
(22, 2, 'Ascensión Invernal',
 'Ascensión en condiciones invernales, recomendable material técnico.',
 '2024-01-20', 'Parking Invierno', 'Cima Invernal', 6, 45, 'dificil', './img/ruta-4.jpg', NOW(), NOW()),
(23, 3, 'Camino de las Ermitas',
 'Ruta cultural uniendo varias ermitas y miradores cercanos.',
 '2024-04-10', 'Pueblo Histórico', 'Ermita Mayor', 4, 0, 'media', './img/ruta-3.jpg', NOW(), NOW()),
(24, 4, 'Vuelta al Valle Escondido',
 'Circular por un valle poco transitado y muy tranquilo.',
 '2024-07-05', 'Aparcamiento Valle', 'Aparcamiento Valle', 5, 30, 'media', './img/ruta-2.jpg', NOW(), NOW()),
(25, 5, 'Ruta de las Buitreras',
 'Itinerario junto a un farallón donde anidan buitres leonados.',
 '2024-07-08', 'Mirador del Buitre', 'Collado Alto', 4, 30, 'media', './img/ruta-1.jpg', NOW(), NOW()),
(26, 1, 'Circular de los Viñedos',
 'Paseo sencillo por caminos entre viñedos y pequeñas bodegas.',
 '2024-05-02', 'Bodega Vieja', 'Bodega Vieja', 2, 30, 'facil', './img/ruta-2.jpg', NOW(), NOW()),
(27, 2, 'Senda del Castañar',
 'Ruta otoñal por un gran castañar con alfombra de hojas.',
 '2024-10-15', 'Área Castaños', 'Collado Castañas', 3, 0, 'facil', './img/ruta-3.jpg', NOW(), NOW()),
(28, 3, 'Ruta Costera de los Acantilados',
 'Recorrido por la costa con acantilados y vistas al mar.',
 '2024-08-12', 'Faro Norte', 'Playa del Acantilado', 4, 45, 'media', './img/ruta-4.jpg', NOW(), NOW()),
(29, 4, 'Paseo Fluvial del Río Largo',
 'Ruta llana siguiendo el cauce del río con pasarelas y áreas de descanso.',
 '2024-09-05', 'Puente Nuevo', 'Área Recreativa Río Largo', 3, 15, 'facil', './img/ruta-1.jpg', NOW(), NOW()),
(30, 5, 'Cumbre del Amanecer',
 'Ascensión madrugadora para ver amanecer desde la cumbre.',
 '2024-07-20', 'Parking Amanecer', 'Cumbre del Amanecer', 5, 30, 'media', './img/montanas-intro.jpg', NOW(), NOW());




INSERT INTO route_tags (route_id, tag_id) VALUES
(1,  2), (1, 5), (1,10),
(2,  1), (2, 7),
(3, 10), (3, 2),
(4,  2), (4,12),
(5,  3), (5, 5),
(6,  2), (6,11),
(7,  2), (7,12),
(8,  3), (8, 5), (8,10),
(9,  1), (9,11),
(10, 8), (10,10),
(11, 2), (11,10),
(12, 2), (12, 3),
(13,11), (13, 7),
(14, 3), (14, 6),
(15, 2), (15,12),
(16, 1), (16, 2),
(17, 2), (17,12),
(18, 1), (18, 6),
(19, 1), (19, 9),
(20, 7), (20,11),
(21,12), (21, 2),
(22,12), (22, 9),
(23,11), (23,10),
(24, 2), (24, 5),
(25,10), (25, 2),
(26, 5), (26,11),
(27, 1), (27, 6),
(28, 4), (28,10),
(29, 3), (29,11),
(30, 2), (30,10), (30, 8);




INSERT INTO comments (route_id, user_id, content, created_at) VALUES
(1, 1, 'Ruta muy completa, las vistas desde la parte alta son increíbles.',        NOW() - INTERVAL 10 DAY),
(1, 2, 'Subida suave, perfecta para un día tranquilo.',                           NOW() - INTERVAL 9 DAY),
(2, 3, 'El bosque es muy sombrío, ideal en verano. Llevad calzado que agarre.',   NOW() - INTERVAL 8 DAY),
(3, 4, 'Los miradores son espectaculares, pero el tramo final se hace largo.',    NOW() - INTERVAL 7 DAY),
(4, 2, 'Ruta dura, pero merece mucho la pena. Ojo si hay niebla.',                NOW() - INTERVAL 6 DAY),
(5, 1, 'Perfecta para ir con niños, con muchas zonas para parar y bañarse.',      NOW() - INTERVAL 5 DAY),
(6, 5, 'Pueblos muy bonitos y poco tráfico, muy recomendable.',                   NOW() - INTERVAL 4 DAY),
(7, 3, 'Tramo algo aéreo, no apto para gente con mucho vértigo.',                 NOW() - INTERVAL 3 DAY),
(8, 4, 'La vuelta al pantano se hace muy amena, buen firme.',                     NOW() - INTERVAL 2 DAY),
(9, 2, 'En otoño tiene que ser espectacular con el color de las hojas.',          NOW() - INTERVAL 1 DAY),
(10,1, 'La nocturna a Trevijano ha sido una experiencia brutal.',                  NOW()),
(11,2, 'Buena opción para entrenar algo de desnivel sin ser extremo.',             NOW()),
(12,3, 'Las pasarelas del desfiladero impresionan bastante.',                      NOW()),
(13,4, 'Se nota que es una ruta “clásica” de pastores, muy auténtica.',            NOW()),
(14,5, 'Las cascadas son pequeñas pero muy bonitas.',                              NOW()),
(15,1, 'Ruta muy exigente, las tres cimas en un día se notan en las piernas.',     NOW());




INSERT INTO li_dis_route (user_id, route_id, dis_li, created_at) VALUES
(1, 1, 1, NOW()),
(1, 4, 0, NOW()),
(1, 8, 1, NOW()),
(2, 1, 1, NOW()),
(2, 3, 1, NOW()),
(2, 7, 0, NOW()),
(3, 5, 1, NOW()),
(3, 8, 1, NOW()),
(3, 12,0, NOW()),
(4, 4, 1, NOW()),
(4, 15,1, NOW()),
(5, 6, 1, NOW()),
(5, 10,1, NOW()),
(5, 21,1, NOW());
