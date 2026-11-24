-- =====================================================================
-- Secretaria Digital - FATEC Araraquara
-- Script de geração do banco de dados (MySQL / MariaDB)
-- Projeto de Curricularização da Extensão - 4º sem. ADS
-- =====================================================================
-- Como usar: abra no phpMyAdmin (aba SQL) ou rode no terminal:
--   mysql -u root -p < banco.sql
-- O script apaga e recria o banco "secretaria_digital".
-- =====================================================================

DROP DATABASE IF EXISTS secretaria_digital;
CREATE DATABASE secretaria_digital
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE secretaria_digital;

-- ---------------------------------------------------------------------
-- Cursos da FATEC (ADS, Gestão Empresarial, Gestão Comercial...)
-- ---------------------------------------------------------------------
CREATE TABLE tb_cursos (
  id_curso     INT AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(100) NOT NULL,
  sigla        VARCHAR(10)  NOT NULL,
  ativo        TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cursos_sigla (sigla)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Usuários e níveis de acesso
-- nivel: aluno | secretaria | coordenacao | admin
-- senhas novas usam password_hash(); hashes SHA-256 de demonstração são
-- atualizados para password_hash() no primeiro login válido
-- id_curso: obrigatório para aluno e coordenação (validado no PHP)
-- ---------------------------------------------------------------------
CREATE TABLE tb_usuarios (
  id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(120) NOT NULL,
  login           VARCHAR(50)  NOT NULL,
  senha           VARCHAR(255) NOT NULL,
  email           VARCHAR(120) NOT NULL,
  nivel           ENUM('aluno','secretaria','coordenacao','admin') NOT NULL,
  id_curso        INT          NULL,
  ra              VARCHAR(20)  NULL,
  semestre        TINYINT      NULL,
  telefone        VARCHAR(20)  NULL,
  aviso_whatsapp  TINYINT(1)   NOT NULL DEFAULT 0,
  aviso_email     TINYINT(1)   NOT NULL DEFAULT 1,
  ativo           TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_usuarios_login (login),
  UNIQUE KEY uk_usuarios_email (email),
  UNIQUE KEY uk_usuarios_ra (ra),
  CONSTRAINT fk_usuarios_curso FOREIGN KEY (id_curso)
    REFERENCES tb_cursos (id_curso)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tipos de requerimento (cadastrados pelo admin)
-- prazo_dias: prazo de atendimento (SLA) em dias
-- precisa_coordenacao: 1 = passa pelo parecer do coordenador
-- exige_anexo: 1 = aluno só consegue enviar com arquivo anexado
-- ---------------------------------------------------------------------
CREATE TABLE tb_tipos (
  id_tipo              INT AUTO_INCREMENT PRIMARY KEY,
  nome                 VARCHAR(100) NOT NULL,
  descricao            TEXT         NULL,
  documentos_exigidos  VARCHAR(255) NULL,
  prazo_dias           SMALLINT     NOT NULL DEFAULT 5,
  precisa_coordenacao  TINYINT(1)   NOT NULL DEFAULT 0,
  exige_anexo          TINYINT(1)   NOT NULL DEFAULT 0,
  ativo                TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_tipos_nome (nome)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Chamados (as solicitações dos alunos)
-- prazo: data limite calculada na abertura (hoje + prazo_dias do tipo)
-- aguardando_desde / ultimo_lembrete: controle do lembrete automático
--   quando o chamado fica parado em "aguardando_aluno"
-- ---------------------------------------------------------------------
CREATE TABLE tb_chamados (
  id_chamado        INT AUTO_INCREMENT PRIMARY KEY,
  id_aluno          INT          NOT NULL,
  id_tipo           INT          NOT NULL,
  id_responsavel    INT          NULL,
  assunto           VARCHAR(150) NOT NULL,
  descricao         TEXT         NOT NULL,
  status            ENUM('aberto','em_analise','aguardando_aluno',
                         'aguardando_coordenacao','deferido',
                         'indeferido','concluido')
                    NOT NULL DEFAULT 'aberto',
  prazo             DATE         NOT NULL,
  parecer           TEXT         NULL,
  id_parecerista    INT          NULL,
  parecer_em        DATETIME     NULL,
  aguardando_desde  DATETIME     NULL,
  ultimo_lembrete   DATETIME     NULL,
  avaliacao         TINYINT      NULL,
  criado_em         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_chamados_status (status),
  KEY idx_chamados_prazo (prazo),
  CONSTRAINT fk_chamados_aluno FOREIGN KEY (id_aluno)
    REFERENCES tb_usuarios (id_usuario),
  CONSTRAINT fk_chamados_tipo FOREIGN KEY (id_tipo)
    REFERENCES tb_tipos (id_tipo),
  CONSTRAINT fk_chamados_responsavel FOREIGN KEY (id_responsavel)
    REFERENCES tb_usuarios (id_usuario),
  CONSTRAINT fk_chamados_parecerista FOREIGN KEY (id_parecerista)
    REFERENCES tb_usuarios (id_usuario),
  CONSTRAINT ck_chamados_avaliacao CHECK (avaliacao IS NULL OR avaliacao BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Conversa dentro do chamado
-- interna = 1: nota interna, só a equipe (secretaria/coordenação/admin) vê
-- ---------------------------------------------------------------------
CREATE TABLE tb_mensagens (
  id_mensagem  INT AUTO_INCREMENT PRIMARY KEY,
  id_chamado   INT        NOT NULL,
  id_usuario   INT        NOT NULL,
  mensagem     TEXT       NOT NULL,
  interna      TINYINT(1) NOT NULL DEFAULT 0,
  criado_em    DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mensagens_chamado FOREIGN KEY (id_chamado)
    REFERENCES tb_chamados (id_chamado) ON DELETE CASCADE,
  CONSTRAINT fk_mensagens_usuario FOREIGN KEY (id_usuario)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Arquivos anexados aos chamados (PDF ou imagem)
-- arquivo: nome gerado pelo sistema na pasta uploads/anexos/
-- ---------------------------------------------------------------------
CREATE TABLE tb_anexos (
  id_anexo       INT AUTO_INCREMENT PRIMARY KEY,
  id_chamado     INT          NOT NULL,
  id_usuario     INT          NOT NULL,
  nome_original  VARCHAR(255) NOT NULL,
  arquivo        VARCHAR(255) NOT NULL,
  tamanho        INT          NOT NULL,
  criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_anexos_chamado FOREIGN KEY (id_chamado)
    REFERENCES tb_chamados (id_chamado) ON DELETE CASCADE,
  CONSTRAINT fk_anexos_usuario FOREIGN KEY (id_usuario)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Histórico de cada chamado: quem fez o quê e quando
-- (mudança de status, assumiu, encaminhou, lembrete enviado...)
-- ---------------------------------------------------------------------
CREATE TABLE tb_historico (
  id_historico     INT AUTO_INCREMENT PRIMARY KEY,
  id_chamado       INT          NOT NULL,
  id_usuario       INT          NULL,
  acao             VARCHAR(100) NOT NULL,
  status_anterior  VARCHAR(30)  NULL,
  status_novo      VARCHAR(30)  NULL,
  observacao       VARCHAR(255) NULL,
  criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historico_chamado FOREIGN KEY (id_chamado)
    REFERENCES tb_chamados (id_chamado) ON DELETE CASCADE,
  CONSTRAINT fk_historico_usuario FOREIGN KEY (id_usuario)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Declarações emitidas (matrícula, frequência)
-- codigo: código de validação impresso no documento e no QR Code
-- ---------------------------------------------------------------------
CREATE TABLE tb_documentos (
  id_documento  INT AUTO_INCREMENT PRIMARY KEY,
  codigo        CHAR(16)   NOT NULL,
  id_aluno      INT        NOT NULL,
  tipo          ENUM('matricula','frequencia') NOT NULL,
  emitido_em    DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  valido_ate    DATE       NOT NULL,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_documentos_codigo (codigo),
  CONSTRAINT fk_documentos_aluno FOREIGN KEY (id_aluno)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Perguntas frequentes (FAQ)
-- ---------------------------------------------------------------------
CREATE TABLE tb_faq (
  id_faq          INT AUTO_INCREMENT PRIMARY KEY,
  pergunta        VARCHAR(255) NOT NULL,
  resposta        TEXT         NOT NULL,
  categoria       VARCHAR(60)  NULL,
  palavras_chave  VARCHAR(255) NULL,
  ativo           TINYINT(1)   NOT NULL DEFAULT 1,
  id_autor        INT          NOT NULL,
  criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_faq_autor FOREIGN KEY (id_autor)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Respostas prontas usadas pela secretaria
-- ---------------------------------------------------------------------
CREATE TABLE tb_respostas (
  id_resposta  INT AUTO_INCREMENT PRIMARY KEY,
  titulo       VARCHAR(100) NOT NULL,
  texto        TEXT         NOT NULL,
  ativo        TINYINT(1)   NOT NULL DEFAULT 1,
  id_autor     INT          NOT NULL,
  criado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_respostas_autor FOREIGN KEY (id_autor)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Mural de avisos (gerenciamento de imagens: cadastro, upload, troca e remoção)
-- imagem: nome do arquivo na pasta uploads/avisos/ (NULL = sem imagem)
-- ---------------------------------------------------------------------
CREATE TABLE tb_avisos (
  id_aviso       INT AUTO_INCREMENT PRIMARY KEY,
  titulo         VARCHAR(150) NOT NULL,
  texto          TEXT         NOT NULL,
  imagem         VARCHAR(255) NULL,
  publicado      TINYINT(1)   NOT NULL DEFAULT 1,
  exibir_ate     DATE         NULL,
  id_autor       INT          NOT NULL,
  criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_avisos_autor FOREIGN KEY (id_autor)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Notificações enviadas aos usuários (sistema, e-mail, WhatsApp)
-- ---------------------------------------------------------------------
CREATE TABLE tb_notificacoes (
  id_notificacao  INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario      INT          NOT NULL,
  id_chamado      INT          NULL,
  canal           ENUM('sistema','email','whatsapp') NOT NULL DEFAULT 'sistema',
  mensagem        VARCHAR(255) NOT NULL,
  lida            TINYINT(1)   NOT NULL DEFAULT 0,
  enviada         TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notificacoes_usuario FOREIGN KEY (id_usuario)
    REFERENCES tb_usuarios (id_usuario),
  CONSTRAINT fk_notificacoes_chamado FOREIGN KEY (id_chamado)
    REFERENCES tb_chamados (id_chamado) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Log de auditoria geral (login, cadastros, exclusões...)
-- ---------------------------------------------------------------------
CREATE TABLE tb_auditoria (
  id_auditoria  INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario    INT          NULL,
  acao          VARCHAR(60)  NOT NULL,
  tabela        VARCHAR(60)  NULL,
  id_registro   INT          NULL,
  detalhes      VARCHAR(255) NULL,
  ip            VARCHAR(45)  NULL,
  criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auditoria_data (criado_em),
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Estágio: etapas/documentos exigidos (cadastrados pela secretaria)
-- modelo: arquivo para o aluno baixar (pasta uploads/modelos/), pode ser NULL
-- prazo_dias: dias, contados do início do estágio, para entregar o documento
-- ---------------------------------------------------------------------
CREATE TABLE tb_etapas (
  id_etapa      INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(120) NOT NULL,
  descricao     TEXT         NULL,
  ordem         SMALLINT     NOT NULL DEFAULT 1,
  prazo_dias    SMALLINT     NULL,
  obrigatoria   TINYINT(1)   NOT NULL DEFAULT 1,
  modelo        VARCHAR(255) NULL,
  modelo_nome   VARCHAR(255) NULL,
  ativo         TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Estágio de cada aluno (dados da empresa e do período)
-- situacao: em_andamento | concluido | cancelado
-- ---------------------------------------------------------------------
CREATE TABLE tb_estagios (
  id_estagio     INT AUTO_INCREMENT PRIMARY KEY,
  id_aluno       INT          NOT NULL,
  empresa        VARCHAR(150) NOT NULL,
  cnpj           VARCHAR(20)  NULL,
  supervisor     VARCHAR(120) NOT NULL,
  email_supervisor VARCHAR(120) NULL,
  area           VARCHAR(120) NULL,
  horas_semana   TINYINT      NOT NULL,
  data_inicio    DATE         NOT NULL,
  data_fim       DATE         NOT NULL,
  situacao       ENUM('em_andamento','concluido','cancelado') NOT NULL DEFAULT 'em_andamento',
  criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_estagios_aluno FOREIGN KEY (id_aluno)
    REFERENCES tb_usuarios (id_usuario),
  CONSTRAINT ck_estagios_datas CHECK (data_fim >= data_inicio)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Documentos de estágio enviados pelo aluno e a avaliação de cada um
-- status: enviado (aguardando análise) | aprovado | correcao
-- arquivo: nome gerado na pasta uploads/estagio/ (bloqueada, entregue por estagio_arquivo.php)
-- ---------------------------------------------------------------------
CREATE TABLE tb_entregas (
  id_entrega     INT AUTO_INCREMENT PRIMARY KEY,
  id_estagio     INT          NOT NULL,
  id_etapa       INT          NOT NULL,
  arquivo        VARCHAR(255) NOT NULL,
  nome_original  VARCHAR(255) NOT NULL,
  tamanho        INT          NOT NULL,
  status         ENUM('enviado','aprovado','correcao') NOT NULL DEFAULT 'enviado',
  observacao     VARCHAR(500) NULL,
  id_avaliador   INT          NULL,
  avaliado_em    DATETIME     NULL,
  criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entregas_status (status),
  CONSTRAINT fk_entregas_estagio FOREIGN KEY (id_estagio)
    REFERENCES tb_estagios (id_estagio) ON DELETE CASCADE,
  CONSTRAINT fk_entregas_etapa FOREIGN KEY (id_etapa)
    REFERENCES tb_etapas (id_etapa),
  CONSTRAINT fk_entregas_avaliador FOREIGN KEY (id_avaliador)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Chat interno da equipe (secretaria, coordenação, administração)
-- id_destinatario NULL = mensagem no canal geral da equipe
-- lida: usada nas conversas diretas
-- ---------------------------------------------------------------------
CREATE TABLE tb_chat (
  id_chat          INT AUTO_INCREMENT PRIMARY KEY,
  id_remetente     INT          NOT NULL,
  id_destinatario  INT          NULL,
  mensagem         VARCHAR(1000) NOT NULL,
  lida             TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_chat_conversa (id_destinatario, id_remetente),
  CONSTRAINT fk_chat_remetente FOREIGN KEY (id_remetente)
    REFERENCES tb_usuarios (id_usuario),
  CONSTRAINT fk_chat_destinatario FOREIGN KEY (id_destinatario)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- Até qual mensagem do canal geral cada pessoa já leu
CREATE TABLE tb_chat_geral_lido (
  id_usuario   INT PRIMARY KEY,
  ultimo_id    INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_chatlido_usuario FOREIGN KEY (id_usuario)
    REFERENCES tb_usuarios (id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Configurações do sistema (ex.: integração com o WhatsApp)
-- preenchidas pelo admin na tela "Integração WhatsApp"
-- ---------------------------------------------------------------------
CREATE TABLE tb_configuracoes (
  chave          VARCHAR(60)  PRIMARY KEY,
  valor          TEXT         NULL,
  atualizado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insere os cursos da FATEC Araraquara (para uso no cadastro de alunos e coordenação)

INSERT INTO tb_cursos (nome, sigla) VALUES
  ('Análise e Desenvolvimento de Sistemas', 'ADS'),
  ('Administração', 'ADM'),
  ('Gestão Empresarial', 'GE'),
  ('Gestão Comercial', 'GC'),
  ('Gestão da Produção Industrial', 'GPI'),
  ('Segurança da Informação', 'SI');

-- Cadastro fictício de aluno ADS; telefone propositalmente inválido.
INSERT INTO tb_usuarios
  (nome, login, senha, email, nivel, id_curso, ra, semestre, telefone,
   aviso_whatsapp, ativo)
SELECT
  'Mário Ambrósio',
  'mario.ambrosio',
  '24a1d4d64b9b7f18e177ce6dce143697a23c52213f40041cea2d298f18bc8828',
  'mario.ambrosio@secretariadigital.local',
  'aluno',
  id_curso,
  'DEMO-ADS-001',
  3,
  '(00) 00000-0000',
  0,
  1
FROM tb_cursos
WHERE sigla = 'ADS';

-- Usuários demonstrativos

INSERT INTO tb_usuarios (nome, login, senha, email, nivel, ativo)
VALUES
  ('Sofia Martins', 'sofia.martins',
   SHA2(CONCAT('sofia.martins', UUID()), 256),
   'sofia.martins@secretariadigital.local', 'secretaria', 0);

INSERT INTO tb_usuarios
  (nome, login, senha, email, nivel, id_curso, ativo)
SELECT
  dados.nome,
  dados.login,
  SHA2(CONCAT(dados.login, UUID()), 256),
  CONCAT(dados.login, '@secretariadigital.local'),
  'coordenacao', dados.id_curso, 0
FROM (
  SELECT
    c.id_curso,
    CASE c.sigla
      WHEN 'ADS' THEN 'Renata Almeida'
      WHEN 'ADM' THEN 'Marcos Ribeiro'
      WHEN 'GE' THEN 'Helena Duarte'
      WHEN 'GC' THEN 'Caio Nogueira'
      WHEN 'GPI' THEN 'Lívia Monteiro'
      WHEN 'SI' THEN 'Bruno Vasconcelos'
      ELSE CONCAT('Coordenação Fictícia ', c.sigla)
    END AS nome,
    CASE c.sigla
      WHEN 'ADS' THEN 'renata.almeida'
      WHEN 'ADM' THEN 'marcos.ribeiro'
      WHEN 'GE' THEN 'helena.duarte'
      WHEN 'GC' THEN 'caio.nogueira'
      WHEN 'GPI' THEN 'livia.monteiro'
      WHEN 'SI' THEN 'bruno.vasconcelos'
      ELSE CONCAT('coordenacao.', LOWER(c.sigla))
    END AS login
  FROM tb_cursos c
) AS dados;

INSERT INTO tb_usuarios
  (nome, login, senha, email, nivel, id_curso, ra, semestre, telefone,
   aviso_whatsapp, ativo)
SELECT
  dados.nome,
  dados.login,
  SHA2(CONCAT(dados.login, UUID()), 256),
  CONCAT(dados.login, '@secretariadigital.local'),
  'aluno', dados.id_curso, CONCAT('DEMO-', dados.sigla), 1,
  '(00) 00000-0000', 0, 0
FROM (
  SELECT
    c.id_curso,
    c.sigla,
    CASE c.sigla
      WHEN 'ADS' THEN 'Lara Mendonça'
      WHEN 'ADM' THEN 'Pedro Azevedo'
      WHEN 'GE' THEN 'Camila Tavares'
      WHEN 'GC' THEN 'Rafael Campos'
      WHEN 'GPI' THEN 'Isabela Freire'
      WHEN 'SI' THEN 'Diego Barros'
      ELSE CONCAT('Aluno Fictício ', c.sigla)
    END AS nome,
    CASE c.sigla
      WHEN 'ADS' THEN 'lara.mendonca'
      WHEN 'ADM' THEN 'pedro.azevedo'
      WHEN 'GE' THEN 'camila.tavares'
      WHEN 'GC' THEN 'rafael.campos'
      WHEN 'GPI' THEN 'isabela.freire'
      WHEN 'SI' THEN 'diego.barros'
      ELSE CONCAT('aluno.', LOWER(c.sigla))
    END AS login
  FROM tb_cursos c
) AS dados;

-- Tipos de requerimento — revisar prazos e documentos com a secretaria

INSERT INTO tb_tipos
  (nome, descricao, documentos_exigidos, prazo_dias,
   precisa_coordenacao, exige_anexo)
VALUES
  ('Declaração de matrícula', 'Solicitação de declaração de matrícula.',
   NULL, 5, 0, 0),
  ('Declaração de frequência', 'Solicitação de declaração de frequência.',
   NULL, 5, 0, 0),
  ('Revisão de nota', 'Solicitação de revisão de nota de avaliação.',
   'Comprovante ou avaliação relacionada, se disponível', 10, 1, 1),
  ('Aproveitamento de estudos', 'Solicitação de análise de estudos anteriores.',
   'Histórico escolar e ementas das disciplinas', 15, 1, 1),
  ('Trancamento de matrícula', 'Solicitação de trancamento de matrícula.',
   'Documentação comprobatória, se aplicável', 10, 1, 1);

-- Chamados sintéticos cobrindo os status disponíveis.

INSERT INTO tb_chamados
  (id_aluno, id_tipo, id_responsavel, assunto, descricao, status, prazo,
   parecer, id_parecerista, parecer_em, aguardando_desde)
SELECT
  aluno.id_usuario,
  tipo.id_tipo,
  secretaria.id_usuario,
  demo.assunto,
  demo.descricao,
  demo.status,
  DATE_ADD(CURDATE(), INTERVAL demo.prazo_dias DAY),
  CASE WHEN demo.status IN ('deferido', 'indeferido')
       THEN demo.parecer ELSE NULL END,
  CASE WHEN demo.status IN ('deferido', 'indeferido')
       THEN coordenador.id_usuario ELSE NULL END,
  CASE WHEN demo.status IN ('deferido', 'indeferido')
       THEN NOW() ELSE NULL END,
  CASE WHEN demo.status = 'aguardando_aluno' THEN NOW() ELSE NULL END
FROM (
  SELECT 'mario.ambrosio' AS login_aluno,
         'Declaração de matrícula' AS tipo,
         'DEMO | Solicitação aberta (Mário)' AS assunto,
         'Registro sintético para demonstração do status aberto.' AS descricao,
         'aberto' AS status, 5 AS prazo_dias, NULL AS parecer
  UNION ALL
  SELECT 'mario.ambrosio', 'Declaração de frequência',
         'DEMO | Em análise (Mário)',
         'Registro sintético para demonstração do status em análise.',
         'em_analise', 5, NULL
  UNION ALL
  SELECT 'mario.ambrosio', 'Declaração de matrícula',
         'DEMO | Aguardando aluno (Mário)',
         'Registro sintético para demonstração do status aguardando aluno.',
         'aguardando_aluno', 5, NULL
  UNION ALL
  SELECT 'mario.ambrosio', 'Declaração de frequência',
         'DEMO | Aguardando coordenação (Mário)',
         'Registro sintético para demonstração do status aguardando coordenação.',
         'aguardando_coordenacao', 10, NULL
  UNION ALL
  SELECT 'mario.ambrosio', 'Declaração de matrícula',
         'DEMO | Deferido (Mário)',
         'Registro sintético para demonstração do status deferido.',
         'deferido', 15, 'Parecer fictício de demonstração.'
  UNION ALL
  SELECT 'mario.ambrosio', 'Declaração de frequência',
         'DEMO | Indeferido (Mário)',
         'Registro sintético para demonstração do status indeferido.',
         'indeferido', 10, 'Parecer fictício de demonstração.'
  UNION ALL
  SELECT 'mario.ambrosio', 'Declaração de frequência',
         'DEMO | Concluído (Mário)',
         'Registro sintético para demonstração do status concluído.',
         'concluido', 5, NULL
) AS demo
JOIN tb_usuarios aluno
  ON aluno.login = demo.login_aluno
JOIN tb_tipos tipo
  ON tipo.nome = demo.tipo
JOIN tb_usuarios secretaria
  ON secretaria.login = 'sofia.martins'
LEFT JOIN tb_usuarios coordenador
  ON coordenador.nivel = 'coordenacao'
 AND coordenador.id_curso = aluno.id_curso;

-- Mensagens associadas aos chamados demonstrativos
INSERT INTO tb_mensagens (id_chamado, id_usuario, mensagem, interna)
SELECT chamado.id_chamado, aluno.id_usuario, demo.mensagem, 0
FROM (
  SELECT 'DEMO | Solicitação aberta (Mário)' AS assunto,
         'mario.ambrosio' AS login,
         'Mensagem sintética: solicito a declaração de matrícula.'
           AS mensagem
  UNION ALL
  SELECT 'DEMO | Em análise (Mário)', 'mario.ambrosio',
         'Mensagem sintética: solicito a declaração de frequência.'
  UNION ALL
  SELECT 'DEMO | Aguardando aluno (Mário)', 'mario.ambrosio',
         'Mensagem sintética: solicito orientação sobre a declaração.'
  UNION ALL
  SELECT 'DEMO | Aguardando coordenação (Mário)', 'mario.ambrosio',
         'Mensagem sintética: solicito análise da declaração de frequência.'
  UNION ALL
  SELECT 'DEMO | Deferido (Mário)', 'mario.ambrosio',
         'Mensagem sintética: solicito a declaração de matrícula.'
  UNION ALL
  SELECT 'DEMO | Indeferido (Mário)', 'mario.ambrosio',
         'Mensagem sintética: solicito a declaração de frequência.'
  UNION ALL
  SELECT 'DEMO | Concluído (Mário)', 'mario.ambrosio',
         'Mensagem sintética: solicito a declaração de frequência.'
) AS demo
JOIN tb_chamados chamado ON chamado.assunto = demo.assunto
JOIN tb_usuarios aluno ON aluno.login = demo.login;

INSERT INTO tb_mensagens (id_chamado, id_usuario, mensagem, interna)
SELECT chamado.id_chamado, secretaria.id_usuario,
       'Mensagem sintética: aguardamos o complemento das informações.',
       0
FROM tb_chamados chamado
JOIN tb_usuarios secretaria ON secretaria.login = 'sofia.martins'
WHERE chamado.assunto = 'DEMO | Aguardando aluno (Mário)';

INSERT INTO tb_mensagens (id_chamado, id_usuario, mensagem, interna)
SELECT chamado.id_chamado, secretaria.id_usuario,
       'Nota interna sintética para a coordenação.',
       1
FROM tb_chamados chamado
JOIN tb_usuarios secretaria ON secretaria.login = 'sofia.martins'
WHERE chamado.assunto = 'DEMO | Aguardando coordenação (Mário)';

-- Anexos demonstrativos com arquivos correspondentes em uploads/anexos/
INSERT INTO tb_anexos
  (id_chamado, id_usuario, nome_original, arquivo, tamanho)
SELECT chamado.id_chamado, aluno.id_usuario,
       'Comprovante demonstrativo.pdf', 'demo-comprovante.pdf', 614
FROM tb_chamados chamado
JOIN tb_usuarios aluno ON aluno.login = 'mario.ambrosio'
WHERE chamado.assunto = 'DEMO | Solicitação aberta (Mário)';

INSERT INTO tb_anexos
  (id_chamado, id_usuario, nome_original, arquivo, tamanho)
SELECT chamado.id_chamado, aluno.id_usuario,
       'Formulário demonstrativo.pdf', 'demo-formulario.pdf', 617
FROM tb_chamados chamado
JOIN tb_usuarios aluno ON aluno.login = 'mario.ambrosio'
WHERE chamado.assunto = 'DEMO | Aguardando aluno (Mário)';

-- Histórico inicial dos chamados demonstrativos; não simula auditoria real
INSERT INTO tb_historico
  (id_chamado, id_usuario, acao, status_anterior, status_novo, observacao)
SELECT
  chamado.id_chamado, NULL, 'Carga de demonstração', NULL, chamado.status,
  'Registro sintético para revisão; não representa uma ação real.'
FROM tb_chamados chamado
WHERE chamado.assunto LIKE 'DEMO | %';

-- Documentos demonstrativos inativos, sem validade de emissão real
INSERT INTO tb_documentos (codigo, id_aluno, tipo, valido_ate, ativo)
SELECT
  UPPER(SUBSTRING(SHA2(CONCAT('documento-demo-', aluno.login), 256), 1, 16)),
  aluno.id_usuario, 'matricula', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0
FROM tb_usuarios aluno
WHERE aluno.ra LIKE 'DEMO-%';

-- FAQ

INSERT INTO tb_faq
  (pergunta, resposta, categoria, palavras_chave, id_autor)
SELECT
  'Como solicito uma declaração?',
  'Acesse a área de requerimentos, selecione a declaração desejada e acompanhe o andamento pelo sistema.',
  'Requerimentos', 'declaração, matrícula, frequência', id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

INSERT INTO tb_faq
  (pergunta, resposta, categoria, palavras_chave, id_autor)
SELECT
  'Como acompanho uma solicitação?',
  'Acesse seus chamados no sistema para consultar o status e as mensagens da secretaria.',
  'Requerimentos', 'chamado, solicitação, status, acompanhamento', id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

INSERT INTO tb_faq
  (pergunta, resposta, categoria, palavras_chave, id_autor)
SELECT
  'Como solicito aproveitamento de estudos?',
  'Abra um requerimento de aproveitamento e anexe os documentos solicitados. A análise depende da documentação e dos procedimentos acadêmicos vigentes.',
  'Requerimentos', 'aproveitamento, estudos, disciplinas, histórico', id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

INSERT INTO tb_faq
  (pergunta, resposta, categoria, palavras_chave, id_autor)
SELECT
  'Onde consulto prazos e calendário acadêmico?',
  'Consulte o calendário acadêmico e os comunicados oficiais da unidade. Em caso de dúvida, entre em contato com a secretaria.',
  'Informações acadêmicas', 'prazo, calendário, secretaria', id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

-- Respostas prontas da secretaria

INSERT INTO tb_respostas (titulo, texto, id_autor)
SELECT
  'Solicitação recebida',
  'Sua solicitação foi recebida pela secretaria. Acompanhe as atualizações e eventuais pedidos de informação pelo sistema.',
  id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

INSERT INTO tb_respostas (titulo, texto, id_autor)
SELECT
  'Documento pendente',
  'Para dar continuidade à solicitação, envie os documentos indicados na mensagem do chamado.',
  id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

INSERT INTO tb_respostas (titulo, texto, id_autor)
SELECT
  'Consulte o calendário acadêmico',
  'Os prazos dependem do calendário e dos procedimentos acadêmicos vigentes. Consulte os comunicados oficiais ou procure a secretaria.',
  id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

-- Aviso geral, sem data de expiração
INSERT INTO tb_avisos (titulo, texto, publicado, exibir_ate, id_autor)
SELECT
  'Consulte os comunicados acadêmicos',
  'Acompanhe os comunicados oficiais e o calendário acadêmico para verificar prazos, atividades e orientações da unidade.',
  1, NULL, id_usuario
FROM tb_usuarios WHERE login = 'sofia.martins';

-- Notificações sintéticas para os dois chamados que aguardam ação
INSERT INTO tb_notificacoes
  (id_usuario, id_chamado, canal, mensagem, lida, enviada)
SELECT aluno.id_usuario, chamado.id_chamado, 'sistema',
       'Notificação sintética: o chamado demonstrativo aguarda sua resposta.',
       0, 0
FROM tb_chamados chamado
JOIN tb_usuarios aluno ON aluno.login = 'mario.ambrosio'
WHERE chamado.assunto = 'DEMO | Aguardando aluno (Mário)';

INSERT INTO tb_notificacoes
  (id_usuario, id_chamado, canal, mensagem, lida, enviada)
SELECT coordenador.id_usuario, chamado.id_chamado, 'sistema',
       'Notificação sintética: há um chamado demonstrativo aguardando parecer.',
       0, 0
FROM tb_chamados chamado
JOIN tb_usuarios coordenador
  ON coordenador.login = 'renata.almeida'
WHERE chamado.assunto = 'DEMO | Aguardando coordenação (Mário)';

-- Eventos de auditoria fictícios, identificados explicitamente como teste
INSERT INTO tb_auditoria
  (id_usuario, acao, tabela, id_registro, detalhes, ip)
SELECT secretaria.id_usuario, 'teste', 'tb_chamados', chamado.id_chamado,
       'TESTE: registro sintético; não representa uma ação real.',
       '127.0.0.1'
FROM tb_chamados chamado
JOIN tb_usuarios secretaria ON secretaria.login = 'sofia.martins'
WHERE chamado.assunto = 'DEMO | Solicitação aberta (Mário)';

INSERT INTO tb_auditoria
  (id_usuario, acao, tabela, id_registro, detalhes, ip)
SELECT secretaria.id_usuario, 'teste', 'tb_anexos', anexo.id_anexo,
       'TESTE: anexo sintético; não representa uma ação real.',
       '127.0.0.1'
FROM tb_anexos anexo
JOIN tb_usuarios secretaria ON secretaria.login = 'sofia.martins'
WHERE anexo.arquivo = 'demo-comprovante.pdf';
