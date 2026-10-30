-- Etapa 2: página de cada setor (responsáveis, descrição, páginas de texto, links) e solicitações de Marketing/Design.
ALTER TABLE sectors
  ADD COLUMN description TEXT NULL AFTER color,
  ADD COLUMN parent_id INT UNSIGNED NULL AFTER description,
  ADD CONSTRAINT fk_sectors_parent FOREIGN KEY (parent_id) REFERENCES sectors (id) ON DELETE SET NULL;

CREATE TABLE sector_leads (
  sector_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role_label VARCHAR(60) NOT NULL DEFAULT 'Responsável',
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (sector_id, user_id),
  CONSTRAINT fk_sl_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE CASCADE,
  CONSTRAINT fk_sl_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sector_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  body MEDIUMTEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pages_sector (sector_id, sort_order),
  CONSTRAINT fk_pages_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE CASCADE,
  CONSTRAINT fk_pages_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_pages_updater FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sector_links (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sector_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  url VARCHAR(2000) NOT NULL,
  grp VARCHAR(40) NOT NULL DEFAULT 'Documentos',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sl_sector (sector_id),
  CONSTRAINT fk_links_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quais setores recebem os pedidos de Marketing/Design (o administrador pode trocar em Administração → Setores)
INSERT INTO settings (k, v) SELECT 'sol_setor_recebe', CAST(id AS CHAR) FROM sectors WHERE name = 'Marketing' LIMIT 1;
INSERT INTO settings (k, v) SELECT 'sol_setor_design', CAST(id AS CHAR) FROM sectors WHERE name = 'Design' LIMIT 1;
-- Design fica dentro de Marketing, como no Notion
UPDATE sectors d JOIN (SELECT id FROM sectors WHERE name = 'Marketing') m SET d.parent_id = m.id WHERE d.name = 'Design';
