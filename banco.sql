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
-- Cursos da FATEC (ADS, Gestão Empresarial, Biocombustíveis...)
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
-- senha: SHA-256 (64 caracteres hexadecimais) com salt baseado no login
--        senha = SHA2(CONCAT(login, senha_digitada), 256)
-- id_curso: obrigatório para aluno e coordenação (validado no PHP)
-- ---------------------------------------------------------------------
CREATE TABLE tb_usuarios (
  id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(120) NOT NULL,
  login           VARCHAR(50)  NOT NULL,
  senha           CHAR(64)     NOT NULL,
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

-- =====================================================================
-- Credencial para primeiro acesso
-- Todo o resto dos dados entra pelo sistema (telas de cadastro).
--   Login: admin
--   Senha: Fatec@2026  (troque no primeiro acesso)
-- O hash é calculado igual ao PHP: hash('sha256', $login . $senha)
-- =====================================================================
INSERT INTO tb_usuarios (nome, login, senha, email, nivel)
VALUES ('Administrador', 'admin',
        SHA2(CONCAT('admin', 'Fatec@2026'), 256),
        'admin@secretariadigital.local', 'admin');
