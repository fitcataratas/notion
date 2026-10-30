-- Etapa 4: Blog (Banco de Ideias em quadro + Pautas), com as automações do Notion feitas pelo próprio sistema.
CREATE TABLE blog_pautas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  theme TEXT NULL,
  audience VARCHAR(160) NULL,
  researcher_id INT UNSIGNED NULL,
  publish_date DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Ideia recebida',
  requester_id INT UNSIGNED NULL,
  article_url VARCHAR(500) NULL,
  notes MEDIUMTEXT NULL,
  idea_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pauta_idea (idea_id),
  KEY ix_pauta_pub (publish_date),
  CONSTRAINT fk_pauta_researcher FOREIGN KEY (researcher_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_pauta_requester FOREIGN KEY (requester_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_ideas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  notes MEDIUMTEXT NULL,
  evaluation VARCHAR(30) NOT NULL DEFAULT 'Sem Avaliação',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_blog_eval (evaluation),
  CONSTRAINT fk_bi_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uma ideia só pode ter uma pauta; uma ideia com pauta não pode ser apagada (move-se para Cancelado).
ALTER TABLE blog_pautas ADD CONSTRAINT fk_pauta_idea FOREIGN KEY (idea_id) REFERENCES blog_ideas (id) ON DELETE RESTRICT;
