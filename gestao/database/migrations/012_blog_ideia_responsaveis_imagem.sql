-- Blog: responsáveis da ideia (como numa tarefa) e a imagem que vai junto com o texto.
ALTER TABLE blog_ideas
  ADD COLUMN image_code CHAR(24) NULL,
  ADD COLUMN image_ext VARCHAR(4) NULL,
  ADD COLUMN image_name VARCHAR(120) NULL;

CREATE TABLE blog_idea_people (
  idea_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (idea_id, user_id),
  CONSTRAINT fk_bip_idea FOREIGN KEY (idea_id) REFERENCES blog_ideas (id) ON DELETE CASCADE,
  CONSTRAINT fk_bip_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
