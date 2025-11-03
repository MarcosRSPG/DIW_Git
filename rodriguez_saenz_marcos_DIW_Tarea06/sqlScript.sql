CREATE DATABASE IF NOT EXISTS mi_app
   CHARACTER SET utf8mb4
   COLLATE utf8mb4_unicode_ci;

USE mi_app;
Database changed

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
   theme                     VARCHAR(20)  NOT NULL DEFAULT 'light',
   language                  VARCHAR(10)  NOT NULL DEFAULT 'es',
   items_per_page            TINYINT UNSIGNED NOT NULL DEFAULT 10,
   email_notifications       TINYINT(1)   NOT NULL DEFAULT 1,
   created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
   CONSTRAINT fk_user_settings_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE
 ) ENGINE=InnoDB;

 CREATE TABLE IF NOT EXISTS routes (
   id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   user_id            INT UNSIGNED NOT NULL,
   title              VARCHAR(150) NOT NULL,
   description        TEXT         NOT NULL,
   date               DATE         DEFAULT NULL,
   start_location     VARCHAR(150) DEFAULT NULL,
   end_location       VARCHAR(150) DEFAULT NULL,
   time_hours         TINYINT UNSIGNED DEFAULT NULL,
   time_minutes       TINYINT UNSIGNED DEFAULT NULL,
   difficulty         VARCHAR(50)  DEFAULT NULL,
   created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
   CONSTRAINT fk_routes_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE
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
     ON DELETE CASCADE
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
     ON DELETE CASCADE
 ) ENGINE=InnoDB;