-- Etapa 3: Cronograma de Postagens (tabela, calendário, filtro por formato) e Ideias de Conteúdo.
-- As listas (projeto, tipo, formato, plataforma, situação) e suas cores são editáveis pelo administrador.
CREATE TABLE post_options (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kind ENUM('projeto','tipo','formato','plataforma','situacao') NOT NULL,
  name VARCHAR(60) NOT NULL,
  color CHAR(7) NOT NULL DEFAULT '#64748b',
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_post_options (kind, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  post_date DATE NULL,
  project_id INT UNSIGNED NULL,
  type_id INT UNSIGNED NULL,
  format_id INT UNSIGNED NULL,
  status_id INT UNSIGNED NULL,
  objective VARCHAR(255) NULL,
  content MEDIUMTEXT NULL,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_posts_date (post_date),
  CONSTRAINT fk_posts_project FOREIGN KEY (project_id) REFERENCES post_options (id) ON DELETE RESTRICT,
  CONSTRAINT fk_posts_type FOREIGN KEY (type_id) REFERENCES post_options (id) ON DELETE RESTRICT,
  CONSTRAINT fk_posts_format FOREIGN KEY (format_id) REFERENCES post_options (id) ON DELETE RESTRICT,
  CONSTRAINT fk_posts_status FOREIGN KEY (status_id) REFERENCES post_options (id) ON DELETE RESTRICT,
  CONSTRAINT fk_posts_created FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_updated FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_people (
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, user_id),
  CONSTRAINT fk_pp_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_pp_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_platforms (
  post_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, option_id),
  CONSTRAINT fk_ppl_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_ppl_option FOREIGN KEY (option_id) REFERENCES post_options (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ideas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  notes TEXT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  post_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_ideas_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE SET NULL,
  CONSTRAINT fk_ideas_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO post_options (kind, name, color, sort_order) VALUES
  ('projeto','FITCATARATAS','#b7791f',1), ('projeto','FÓRUM','#7c5cbf',2), ('projeto','HACKTOUR','#c0392b',3),
  ('projeto','IDESTUR','#2b6cb0',4), ('projeto','Paulo Angeli','#718096',5), ('projeto','De Angeli','#2f855a',6), ('projeto','Revista','#b83280',7),
  ('tipo','Institucional/Comercial','#2f855a',1), ('tipo','Tutorial','#7c5cbf',2), ('tipo','Educativo','#b7791f',3),
  ('tipo','Comercial','#2b6cb0',4), ('tipo','Institucional','#c05621',5),
  ('formato','Email MKT','#8a5a3c',1), ('formato','Vídeo horizontal','#b83280',2), ('formato','Estático','#7c5cbf',3),
  ('formato','Reels com áudio em alta','#2b6cb0',4), ('formato','Carrossel de fotos','#b7791f',5), ('formato','Carrossel','#c0392b',6), ('formato','Reels','#2f855a',7),
  ('plataforma','Email MKT','#8a5a3c',1), ('plataforma','Site','#c0392b',2), ('plataforma','TikTok','#b7791f',3),
  ('plataforma','LinkedIn','#b83280',4), ('plataforma','Facebook','#2f855a',5), ('plataforma','Instagram','#2b6cb0',6), ('plataforma','YouTube','#e53e3e',7),
  ('situacao','A produzir','#d69e2e',1), ('situacao','Em produção','#dd6b20',2), ('situacao','Aguardando aprovação','#2b6cb0',3),
  ('situacao','Aprovado','#d53f8c',4), ('situacao','Não aprovado','#e53e3e',5), ('situacao','Postado','#38a169',6);
