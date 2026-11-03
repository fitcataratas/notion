-- Dashboard Editorial: quadro de produção de conteúdo (Ideia → Publicado), separado do Cronograma de Postagens.
CREATE TABLE editorial_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  color CHAR(7) NOT NULL DEFAULT '#2f5bea',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE editorial_posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content MEDIUMTEXT NULL,
  profile_id INT UNSIGNED NOT NULL,
  network VARCHAR(40) NOT NULL,
  format VARCHAR(40) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Ideia',
  pub_date DATE NOT NULL,
  owner_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_ed_data (pub_date),
  KEY ix_ed_perfil (profile_id),
  CONSTRAINT fk_ed_perfil FOREIGN KEY (profile_id) REFERENCES editorial_profiles(id) ON DELETE CASCADE,
  CONSTRAINT fk_ed_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ed_criador FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO editorial_profiles (name, color, sort_order) VALUES
 ('FITCataratas', '#17bebb', 1), ('Hackatour', '#e4572e', 2), ('De Angeli', '#2e86de', 3), ('Paulo Angeli', '#7b5ea7', 4),
 ('Idestur', '#76b041', 5), ('Village', '#d94f9a', 6), ('FitGeek', '#c98a00', 7), ('Fórum', '#6b7a8f', 8);
