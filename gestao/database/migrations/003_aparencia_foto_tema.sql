-- Versão 1.1: foto de perfil, tema (claro/escuro) por pessoa.
-- As configurações de aparência do sistema ficam na tabela "settings" (chaves ap_*).
ALTER TABLE users
  ADD COLUMN photo CHAR(24) NULL AFTER role,
  ADD COLUMN theme ENUM('auto','claro','escuro') NOT NULL DEFAULT 'auto' AFTER photo;
