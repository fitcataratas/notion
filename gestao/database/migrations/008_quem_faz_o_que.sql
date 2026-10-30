-- Quem faz o quê: conteúdo inicial (o administrador edita depois em Administração → Quem faz o quê).
INSERT INTO who_does (user_id, person_name, role_label, duties, contact_for, sort_order) VALUES
  ((SELECT id FROM users WHERE name = 'Mayara Angeli' LIMIT 1), 'Mayara Angeli', 'Coordenação e Marketing', 'Direcionamento geral
Definição de prioridades
Integração entre setores
Aprovações finais', 'Decisões estratégicas
Conflitos de prioridade
Autorizações institucionais
Situações fora do planejamento', 1),
  ((SELECT id FROM users WHERE name = 'Jessica Domingues' LIMIT 1), 'Jessica Domingues', 'Co-coordenação', 'Organização de agenda
Acompanhamento de prazos
Alinhamento entre setores
Suporte à operação dos eventos', 'Andamento das atividades
Ajustes de cronograma
Demandas entre setores
Apoio na ausência da coordenação', 2),
  ((SELECT id FROM users WHERE name = 'Bruna Holanda' LIMIT 1), 'Bruna Holanda', 'Comercial', 'Prospecção de clientes
Elaboração de propostas
Negociação de contratos
Relacionamento com expositores e patrocinadores', 'Valores e espaços
Propostas e contratos
Patrocínios e parcerias
Atendimento comercial
Ajuda aos expositores', 3),
  ((SELECT id FROM users WHERE name = 'Raísa Moreira' LIMIT 1), 'Raísa Moreira', 'Marketing (redes sociais)', 'Planejamento de conteúdo
Gestão das redes sociais
Criação de textos e campanhas
Divulgação dos eventos', 'Publicações nas redes
Textos e legendas
Campanhas de divulgação
Ideias de conteúdo', 4),
  ((SELECT id FROM users WHERE name = 'Kristen Grignet' LIMIT 1), 'Kristen Grignet', 'Marketing (videomaker)', 'Planejamento de vídeos
Captação de imagens
Edição audiovisual
Cobertura dos eventos', 'Gravações
Reels e vídeos
Depoimentos e entrevistas
Ajustes em materiais audiovisuais', 5),
  ((SELECT id FROM users WHERE name = 'Gabo Gaona' LIMIT 1), 'Gabo Gaona', 'Design e Marketing', 'Criação de peças gráficas
Adaptação de formatos
Aplicação da identidade visual
Organização dos arquivos finais', 'Artes digitais e impressas
Apresentações
Ajustes de materiais
Arquivos, logotipos e formatos', 6),
  ((SELECT id FROM users WHERE name = 'Joaquin' LIMIT 1), 'Joaquin', 'Secretaria', 'Atendimento ao público
Controle de inscrições
Organização de documentos
Emissão de listas e certificados
Redirecionamento de demandas', 'Inscrições e credenciamento
Certificados e documentos
Listas e cadastros
Informações gerais', 7),
  ((SELECT id FROM users WHERE name = 'João Pedro' LIMIT 1), 'João Pedro', 'TI', 'Gestão de acessos
Suporte aos sistemas
Apoio técnico nos eventos', 'Senhas e permissões
Problemas em plataformas
Internet e equipamentos
Suporte técnico durante eventos', 8),
  ((SELECT id FROM users WHERE name = 'Vacy Alvaro' LIMIT 1), 'Vacy Alvaro', 'Assessor de Imprensa', 'Produção de matérias e reportagens estratégicas para público externo
Relacionamento com a imprensa (solicitações de entrevistas ou materiais dos eventos)', 'Assuntos estratégicos dos eventos
Novidades do evento
Produção de matérias e reportagens estratégicas que podem servir como material extra para as redes sociais', 9);
