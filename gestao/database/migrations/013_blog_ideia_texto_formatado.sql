-- Blog: o texto da ideia passa a ser formatado (negrito, listas, links...). Textos antigos continuam como texto simples até serem salvos de novo.
ALTER TABLE blog_ideas ADD COLUMN notes_rich TINYINT(1) NOT NULL DEFAULT 0
