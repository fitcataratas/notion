-- Avisos e textos editáveis em qualquer página (feitos pelo administrador, sem mexer em arquivos).
CREATE TABLE page_notices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page VARCHAR(60) NOT NULL,
  body TEXT NOT NULL,
  color CHAR(7) NOT NULL DEFAULT '#c98a00',
  style VARCHAR(10) NOT NULL DEFAULT 'faixa',
  pos VARCHAR(10) NOT NULL DEFAULT 'topo',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY ix_notice_page (page)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
