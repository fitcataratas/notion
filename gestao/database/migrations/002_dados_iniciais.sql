-- Setores e projetos que existiam no Notion (memorial, seções 6.3 e 6.4)
-- Os administradores podem renomear, trocar a cor e incluir novos pela tela de administração.

INSERT IGNORE INTO sectors (name, color, sort_order) VALUES
  ('Arquitetura', '#7c5cbf', 1),
  ('Caravanas', '#0e7c86', 2),
  ('Comercial', '#c2410c', 3),
  ('Coordenação', '#475569', 4),
  ('Design', '#be185d', 5),
  ('Marketing', '#6d28d9', 6),
  ('Secretaria', '#b45309', 7),
  ('TI', '#1d4ed8', 8);

INSERT IGNORE INTO projects (name, color, sort_order) VALUES
  ('DE ANGELI', '#15803d', 1),
  ('FITCATARATAS', '#c2410c', 2),
  ('FORUM', '#6d28d9', 3),
  ('HACKATOUR', '#be123c', 4),
  ('IDESTUR', '#0369a1', 5),
  ('PAULO ANGELI', '#57534e', 6),
  ('REVISTA', '#a16207', 7);

INSERT IGNORE INTO settings (k, v) VALUES
  ('datas_importantes', 'FIT 2027: 02, 03 e 04 de junho de 2027.');
