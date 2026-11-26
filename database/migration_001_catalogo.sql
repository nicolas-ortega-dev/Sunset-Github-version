-- =====================================================================
-- SUNSET - Migracion 001: soporte para el catalogo con Open Library
-- Ejecutar UNA sola vez sobre la base "sunset" ya existente
-- (phpMyAdmin > base sunset > pestana Importar).
-- =====================================================================
USE sunset;

ALTER TABLE books
  ADD COLUMN details_fetched TINYINT(1) NOT NULL DEFAULT 0 AFTER edition_count;

CREATE TABLE IF NOT EXISTS search_cache (
  query_hash   CHAR(40)     NOT NULL PRIMARY KEY,
  query_text   VARCHAR(200) NOT NULL,
  result_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
