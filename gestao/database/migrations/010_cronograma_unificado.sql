-- v1.8: o Dashboard Editorial passa a ser uma visão do Cronograma de Postagens (mesmos dados e mesmos nomes),
-- a postagem pode ter mais de um Formato, e entra o projeto FITGEEK.
CREATE TABLE post_formats (
  post_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, option_id),
  CONSTRAINT fk_pf_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_pf_option FOREIGN KEY (option_id) REFERENCES post_options (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO post_formats (post_id, option_id) SELECT id, format_id FROM posts WHERE format_id IS NOT NULL;

ALTER TABLE posts DROP FOREIGN KEY fk_posts_format;

ALTER TABLE posts DROP COLUMN format_id;

INSERT IGNORE INTO post_options (kind, name, color, sort_order) VALUES ('projeto', 'FITGEEK', '#c98a00', 8);

INSERT INTO projects (name, color, sort_order)
  SELECT 'FITGEEK', '#c98a00', COALESCE(MAX(sort_order), 0) + 1 FROM projects
   WHERE NOT EXISTS (SELECT 1 FROM projects WHERE name IN ('FITGEEK', 'FIT GEEK', 'FITGeek'));

-- Traz o que já tiver sido digitado no Dashboard Editorial da v1.7 para o Cronograma (e depois remove as tabelas antigas).
ALTER TABLE posts ADD COLUMN ed_origem INT UNSIGNED NULL;

INSERT INTO posts (title, post_date, project_id, status_id, content, created_by, ed_origem)
  SELECT e.title, e.pub_date,
    (SELECT o.id FROM post_options o WHERE o.kind = 'projeto' AND o.name = CASE WHEN pr.name = 'Hackatour' THEN 'HACKTOUR' ELSE pr.name END LIMIT 1),
    (SELECT o.id FROM post_options o WHERE o.kind = 'situacao' AND o.name = CASE e.status
        WHEN 'Ideia' THEN 'A produzir' WHEN 'Roteiro' THEN 'A produzir' WHEN 'Produção' THEN 'Em produção'
        WHEN 'Aprovação' THEN 'Aguardando aprovação' WHEN 'Agendado' THEN 'Aprovado' ELSE 'Postado' END LIMIT 1),
    CONCAT(COALESCE(e.content, ''), CASE WHEN pr.name IN ('Village') THEN CONCAT('\n\n(Perfil no Dashboard Editorial: ', pr.name, ')') ELSE '' END),
    e.created_by, e.id
  FROM editorial_posts e JOIN editorial_profiles pr ON pr.id = e.profile_id;

INSERT IGNORE INTO post_people (post_id, user_id) SELECT p.id, e.owner_id FROM posts p JOIN editorial_posts e ON e.id = p.ed_origem WHERE e.owner_id IS NOT NULL;

INSERT IGNORE INTO post_platforms (post_id, option_id)
  SELECT p.id, o.id FROM posts p JOIN editorial_posts e ON e.id = p.ed_origem
    JOIN post_options o ON o.kind = 'plataforma' AND o.name = CASE e.network
        WHEN 'Site / Blog' THEN 'Site' WHEN 'Newsletter' THEN 'Email MKT' WHEN 'RD Marketing' THEN 'Email MKT' ELSE e.network END;

INSERT IGNORE INTO post_formats (post_id, option_id)
  SELECT p.id, o.id FROM posts p JOIN editorial_posts e ON e.id = p.ed_origem
    JOIN post_options o ON o.kind = 'formato' AND o.name = CASE e.format
        WHEN 'Reels / vídeo curto' THEN 'Reels' WHEN 'Post estático' THEN 'Estático' WHEN 'Vídeo longo' THEN 'Vídeo horizontal'
        WHEN 'Newsletter' THEN 'Email MKT' ELSE e.format END;

ALTER TABLE posts DROP COLUMN ed_origem;

DROP TABLE editorial_posts;

DROP TABLE editorial_profiles;
