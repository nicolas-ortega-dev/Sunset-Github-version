-- =====================================================================
-- SUNSET - Esquema de base de datos (MySQL 8 / XAMPP)
-- Importar desde phpMyAdmin o con: mysql -u root < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sunset
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sunset;

-- ---------------------------------------------------------------------
-- USUARIOS Y SEGUIDORES
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,            -- password_hash() de PHP (bcrypt/argon2)
  bio           VARCHAR(300) NULL,
  location      VARCHAR(100) NULL,
  website       VARCHAR(200) NULL,
  avatar_path   VARCHAR(255) NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE follows (
  follower_id INT UNSIGNED NOT NULL,              -- quien sigue
  followed_id INT UNSIGNED NOT NULL,              -- a quien siguen
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, followed_id),
  INDEX idx_followed (followed_id),
  FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
  -- Regla (en PHP): un usuario no puede seguirse a si mismo.
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATALOGO DE OBRAS
-- ---------------------------------------------------------------------
CREATE TABLE books (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ol_work_key         VARCHAR(30)  NOT NULL UNIQUE,   -- ej: OL45883W
  title               VARCHAR(300) NOT NULL,
  authors             VARCHAR(300) NOT NULL DEFAULT 'Autor desconocido',
  description         TEXT NULL,
  language            CHAR(3)      NOT NULL DEFAULT 'spa',  -- spa, eng, jpn...
  category            ENUM('libro','manga','comic') NOT NULL DEFAULT 'libro',
  subjects            VARCHAR(500) NULL,                    -- separados por | , para filtros
  isbn                VARCHAR(20)  NULL,                    -- para enlaces a Buscalibre
  cover_id            INT UNSIGNED NULL,                    -- id de portada en Open Library
  first_publish_year  SMALLINT NULL,
  edition_count       INT UNSIGNED NOT NULL DEFAULT 0,      -- sirve de desempate/popularidad
  details_fetched     TINYINT(1)   NOT NULL DEFAULT 0,      -- 1 = ya se pidio la sinopsis a Open Library
  -- Calificaciones internas de Sunset (se actualizan al reseñar):
  rating_sum          INT UNSIGNED NOT NULL DEFAULT 0,
  rating_count        INT UNSIGNED NOT NULL DEFAULT 0,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FULLTEXT KEY ft_title_authors (title, authors),
  INDEX idx_category (category),
  INDEX idx_language (language),
  INDEX idx_rating (rating_count, rating_sum)
) ENGINE=InnoDB;

-- Busquedas ya consultadas a Open Library (evita repetir la misma consulta)
CREATE TABLE search_cache (
  query_hash   CHAR(40)     NOT NULL PRIMARY KEY,   -- sha1 de la busqueda normalizada
  query_text   VARCHAR(200) NOT NULL,
  result_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Listas "Leidos" y "Deseados"
CREATE TABLE user_books (
  user_id    INT UNSIGNED NOT NULL,
  book_id    INT UNSIGNED NOT NULL,
  status     ENUM('read','wishlist') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, book_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PUBLICACIONES Y RESEÑAS
-- Una resena es una publicacion con book_id y rating.
-- Un usuario solo puede dejar UNA resena por libro: al guardar una resena,
-- PHP copia book_id en review_book_id (en posts normales queda NULL).
-- ---------------------------------------------------------------------
CREATE TABLE posts (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  book_id        INT UNSIGNED NULL,                 -- "Vincular obra"
  rating         TINYINT UNSIGNED NULL,             -- 1 a 5, solo si es resena
  body           TEXT NOT NULL,
  image_path     VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  review_book_id INT UNSIGNED NULL,                 -- = book_id solo si es resena

  UNIQUE KEY uq_one_review (user_id, review_book_id),
  INDEX idx_user_date (user_id, created_at),
  INDEX idx_book_date (book_id, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL,
  CHECK (rating IS NULL OR (rating BETWEEN 1 AND 5))
) ENGINE=InnoDB;

CREATE TABLE post_likes (
  user_id    INT UNSIGNED NOT NULL,
  post_id    INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, post_id),
  INDEX idx_post (post_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  body       VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_post_date (post_id, created_at),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FOROS Y CHAT (el chat usa polling: se consulta por id > ultimo_id)
-- ---------------------------------------------------------------------
CREATE TABLE forums (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(300) NULL,
  book_id     INT UNSIGNED NULL,                   -- NULL = foro libre
  creator_id  INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_name (name),
  INDEX idx_book (book_id),
  FOREIGN KEY (book_id)    REFERENCES books(id) ON DELETE SET NULL,
  FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE forum_members (
  forum_id  INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED NOT NULL,
  role      ENUM('admin','member') NOT NULL DEFAULT 'member',
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (forum_id, user_id),
  INDEX idx_user (user_id),
  FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE forum_messages (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  forum_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  body       VARCHAR(2000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_forum_id (forum_id, id),
  FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTIFICACIONES (solo: nuevo seguidor, me gusta, comentario)
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,                -- quien la recibe
  actor_id   INT UNSIGNED NOT NULL,                -- quien la genera
  type       ENUM('follow','like','comment') NOT NULL,
  post_id    INT UNSIGNED NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_read (user_id, is_read, created_at),
  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id)  REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DIRECTORIO DE LIBRERIAS
-- search_url: plantilla con {q}, que se reemplaza por titulo o ISBN.
-- Ej: https://www.buscalibre.cl/libros/search?q={q}
-- ---------------------------------------------------------------------
CREATE TABLE bookstores (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  type        ENUM('fisica','virtual') NOT NULL,
  city        VARCHAR(80)  NULL,
  address     VARCHAR(200) NULL,
  website     VARCHAR(200) NULL,
  search_url  VARCHAR(300) NULL,
  is_demo     TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = tienda ficticia de demostracion
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTA PONDERADA (referencia para el catalogo)
-- score = (v / (v + m)) * R + (m / (v + m)) * C
--   v = rating_count del libro      R = rating_sum / rating_count
--   m = minimo de resenas (ej. 5)   C = promedio global de Sunset (ej. 3.5)
-- Orden: score DESC, language='spa' DESC, edition_count DESC
-- Se calcula en la consulta PHP del modelo Book, no se guarda.
-- ---------------------------------------------------------------------
