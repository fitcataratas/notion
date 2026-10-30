-- Etapa 5: notificações, reuniões, "quem faz o quê", missão/visão/valores, projetos e espaço do externo.
CREATE TABLE notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  actor_id INT UNSIGNED NULL,
  kind VARCHAR(30) NOT NULL,
  body VARCHAR(400) NOT NULL,
  url VARCHAR(300) NOT NULL,
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_notif_user (user_id, read_at, id),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE who_does (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  person_name VARCHAR(120) NOT NULL,
  role_label VARCHAR(160) NOT NULL,
  duties TEXT NULL,
  contact_for TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT fk_who_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE meetings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  meeting_date DATE NULL,
  kind VARCHAR(30) NOT NULL DEFAULT 'Geral',
  project_id INT UNSIGNED NULL,
  minutes_by INT UNSIGNED NULL,
  minutes_url VARCHAR(600) NULL,
  notes MEDIUMTEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_meet_date (meeting_date),
  CONSTRAINT fk_meet_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL,
  CONSTRAINT fk_meet_minutes FOREIGN KEY (minutes_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_meet_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE meeting_people (
  meeting_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (meeting_id, user_id),
  CONSTRAINT fk_mp_meeting FOREIGN KEY (meeting_id) REFERENCES meetings (id) ON DELETE CASCADE,
  CONSTRAINT fk_mp_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE meeting_sectors (
  meeting_id INT UNSIGNED NOT NULL,
  sector_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (meeting_id, sector_id),
  CONSTRAINT fk_ms_meeting FOREIGN KEY (meeting_id) REFERENCES meetings (id) ON DELETE CASCADE,
  CONSTRAINT fk_ms_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projetos: os eventos principais ganham destaque e descrição na página Projetos.
ALTER TABLE projects ADD COLUMN description TEXT NULL AFTER color, ADD COLUMN is_main TINYINT(1) NOT NULL DEFAULT 0 AFTER description;
UPDATE projects SET is_main = 1 WHERE name IN ('FITCATARATAS', 'FORUM', 'FÓRUM', 'HACKATOUR', 'HACKTOUR');

INSERT INTO settings (k, v) VALUES
  ('missao', 'Criar, organizar e coordenar eventos que proporcionem o compartilhamento de conhecimento e geração de negócios, buscando resultados efetivos.'),
  ('visao', 'Ser referência na criação, organização e realização de eventos nacionais e internacionais até 2030.'),
  ('valores', 'Comprometimento, Respeito, Inovação e Criatividade.'),
  ('quem_faz_aviso', 'Antes de encaminhar uma solicitação, informe o que precisa, o prazo, os materiais disponíveis e quem fará a aprovação.'),
  ('externo_intro', 'Espaço dedicado ao trabalho e funcionamento das suas tarefas junto com a De Angeli. Se precisar de ajuda com alguma coisa, pode mandar mensagem para o Joaquin.');
