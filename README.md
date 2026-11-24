# Secretaria Digital — FATEC Araraquara

Central de atendimento e requerimentos da secretaria. O aluno abre solicitações, acompanha o andamento, envia os documentos do estágio, tira declarações com QR Code e consulta a FAQ. A secretaria atende pelo kanban, a coordenação dá o parecer e aprova os documentos do estágio, a equipe conversa pelo chat interno e a administração cadastra tudo e acompanha os relatórios.

Projeto de Curricularização da Extensão · 4º semestre de ADS · Identidade visual institucional em vermelho e cinza · Programação Web (PHP) e Banco de Dados (MySQL) · Prof. Me. Leonardo José de Lima Ferrucci.

**Autoras:** Ana Laura, Silvia Helena e Maria Júlia.

## Requisitos para rodar

- XAMPP (ou outro servidor com Apache + PHP 8 + MySQL/MariaDB)
- Extensões PHP: `mysqli` para o banco e `fileinfo` para validar os arquivos enviados; confirme que estão habilitadas no `php.ini`.
- `curl` é necessária apenas para a integração opcional com a Evolution API/WhatsApp.
- Nenhuma internet é necessária: Bootstrap, fontes e o gerador de QR Code estão dentro do projeto.

## Instalação

1. Copie a pasta `secretaria-digital` para `C:\xampp\htdocs\`.
2. No painel do XAMPP, inicie o **Apache** e o **MySQL**.
3. Abra `http://localhost/phpmyadmin`, vá na aba **SQL**, cole o conteúdo de `banco.sql` e clique em **Executar**.
   O script cria o banco `secretaria_digital`, as 19 tabelas e os dados demonstrativos, mas não cria uma conta administrativa.
4. Se o seu MySQL tiver senha no usuário `root`, ajuste a variável `$senha` em `conecta.php`.
5. Acesse `http://localhost/secretaria-digital/`.
6. Na primeira visita, o sistema abrirá a configuração do administrador. Informe nome, login no padrão `primeiro.sobrenome`, telefone e uma senha. O e-mail será criado como `login@secretariadigital.local`.

> O script apaga e recria o banco `secretaria_digital`. Rode só na instalação.

## Primeiro acesso e senhas

Não há credencial padrão no banco. A tela de configuração só permite criar o primeiro administrador e fica indisponível assim que essa conta existe. As senhas definidas pelo sistema são armazenadas com `password_hash()` e verificadas com `password_verify()`. Hashes SHA-256 antigos são aceitos durante a migração e atualizados automaticamente após um login válido.

Para atualizar uma instalação existente, faça backup e execute `migrar_senhas.sql` e `migrar_campos_usuario.sql` no banco `secretaria_digital` antes de substituir os arquivos do sistema. A migração de campos preenche telefones ausentes com um placeholder inválido (e desativa o WhatsApp nesses registros), substitui os RAs fictícios dos usuários demonstrativos, gera um RA numérico para alunos sem RA e define semestre 1 para alunos sem semestre válido. Substitua os valores provisórios pelos dados corretos; não habilite avisos por WhatsApp para telefones de placeholder. O hash da senha é atualizado automaticamente após o próximo login.

## Primeiros passos depois de instalar

Todo dado entra no sistema pelas telas de cadastro. Logado com a conta de administrador criada no primeiro acesso:

1. **Cadastros → Cursos:** cadastre os cursos (ex.: ADS, Gestão Empresarial).
2. **Cadastros → Tipos de requerimento:** cadastre os tipos, o prazo de cada um, se precisa de parecer da coordenação e se exige anexo.
3. **Cadastros → Usuários:** cadastre a secretaria, a coordenação de cada curso e os alunos.
4. A secretaria cadastra a FAQ, as respostas prontas, os avisos do mural e as **etapas do estágio** (menu Mais → Etapas do estágio), com o modelo de cada documento.

## Níveis de acesso

| Nível | O que faz |
| --- | --- |
| Aluno | Abre solicitações com anexo, conversa no chamado, cadastra o estágio e envia os documentos, emite declarações, consulta a FAQ, avalia o atendimento. Só vê os próprios dados. |
| Secretaria | Atende pelo kanban (assume, muda status, pede documento, reenvia aviso), responde com respostas prontas e notas internas, cadastra alunos, FAQ e avisos do mural, emite declarações, cadastra as etapas do estágio, aprova documentos de estágio, usa o chat da equipe e vê relatórios. |
| Coordenação | Vê só os chamados e estágios do próprio curso, dá parecer (deferir, indeferir ou devolver), aprova ou pede correção dos documentos de estágio, usa o chat da equipe e vê os relatórios do curso. |
| Administração | Cadastra usuários, cursos, tipos, FAQ, respostas e avisos; vê kanban (sem mexer), relatórios e o log de auditoria. |

Todas as contas cadastradas (aluno, secretaria, coordenação e administração) entram pela mesma tela de login; alunos e equipe são cadastrados pela secretaria ou administração. Cada página protegida confirma no banco que a conta continua ativa e confere o nível do usuário (`verifica_nivel()` em `funcoes.php`); alunos veem apenas os próprios chamados e estágios. Digitar o endereço de uma página não permitida volta para o painel com "Acesso negado".

O telefone é obrigatório para todas as contas; o RA e o semestre são obrigatórios para alunos. Secretaria, coordenação e administração não precisam de RA.

## Como o projeto atende aos requisitos

| Requisito | Onde está |
| --- | --- |
| PHP procedural, sem orientação a objetos | Todo o código usa funções; banco com `mysqli_*` procedural |
| MySQL/MariaDB | `banco.sql` |
| Framework CSS | Bootstrap 5 (`css/bootstrap.min.css`) + `css/estilo.css` |
| Página inicial `index.php` | Login, mural de avisos e FAQ |
| Cabeçalho e rodapé idênticos | `cabecalho.php` e `rodape.php` incluídos em todas as páginas |
| Cadastros reais | Telas `*_add.php` / `*_alt.php`; o banco inclui dados demonstrativos inativos e o primeiro admin é criado na configuração inicial |
| Relatórios ordenáveis | `relatorios.php` (6 relatórios, incluindo estágios) e todas as listas: clique no título da coluna |
| Gerenciamento de imagens (CRUD, upload, remoção) | Mural de avisos: `aviso_*.php`, pasta `uploads/avisos/` e modelos de documentos do estágio |
| Senhas protegidas | `password_hash()`/`password_verify()` em `funcoes.php`, com migração automática de hashes SHA-256 antigos |
| Prepared statements | Todas as consultas com dados do usuário usam `mysqli_stmt_*` |
| Prevenção de XSS e sanitização | `seguro()` (htmlspecialchars) em toda saída; `limpa()` e `(int)` na entrada |
| Pelo menos 3 níveis | 4 níveis: aluno, secretaria, coordenação, administração |

## Organização dos arquivos

O padrão de nomes segue o das aulas: `entidade_acao.php`.

| Arquivo | Função |
| --- | --- |
| `conecta.php` | Conexão com o banco |
| `funcoes.php` | Funções do sistema (segurança, sessão, histórico, upload, ordenação) |
| `setup_admin.php` | Configuração protegida da primeira conta administrativa |
| `migrar_senhas.sql` | Atualização do tamanho da coluna de senha em bancos existentes |
| `migrar_campos_usuario.sql` | Preenchimento de telefones e dados acadêmicos ausentes em bancos existentes |
| `cabecalho.php`, `rodape.php` | Cabeçalho e rodapé de todas as páginas |
| `index.php`, `login.php`, `logout.php`, `painel.php` | Entrada no sistema |
| `*_lista.php` | Listagem com ordenação |
| `*_add.php` → `*_add2.php` | Formulário de cadastro → gravação |
| `*_alt.php` → `*_alt2.php` | Formulário de edição → gravação |
| `*_del.php` | Exclusão (quem tem histórico é desativado em vez de apagado) |
| `chamado_*.php`, `kanban.php`, `anexo_ver.php` | Chamados |
| `declaracao_*.php`, `validar.php` | Declarações com QR Code e validação pública |
| `etapa_*.php`, `estagio_*.php` | Estágio: etapas com modelos, guia, cadastro do estágio, envio e aprovação dos documentos |
| `chat.php`, `chat_envia2.php`, `chat_novas.php` | Chat interno da equipe |
| `relatorios.php`, `auditoria_lista.php` | Relatórios e auditoria |
| `whatsapp_config.php`, `whatsapp_webhook.php`, `perfil_alt.php` | Integração com o WhatsApp e "Meus dados" |
| `uploads/avisos/` | Imagens do mural |
| `uploads/anexos/` | Anexos dos chamados (bloqueados por `.htaccess`, entregues só por `anexo_ver.php`) |
| `uploads/modelos/` | Modelos dos documentos do estágio (públicos, para baixar) |
| `uploads/estagio/` | Documentos de estágio enviados pelos alunos (bloqueados, entregues só por `estagio_arquivo.php`) |

## Regras de negócio principais

- O prazo do chamado é calculado na abertura com o prazo do tipo de requerimento.
- Quando o chamado fica **aguardando o aluno**, o prazo pausa. Depois de 3 dias sem resposta o aluno recebe um lembrete automático e a secretaria vê o alerta no card do kanban. **O chamado não é fechado sozinho.**
- Quando o aluno responde, o chamado volta para **em análise** e o prazo anda os dias em que ficou pausado.
- Só a coordenação defere ou indefere, e só chamados do próprio curso.
- Notas internas não aparecem para o aluno.
- Toda mudança fica no histórico do chamado e as ações importantes ficam no log de auditoria.

### Estágio
- A secretaria cadastra as **etapas** (documentos exigidos), com ordem, prazo em dias a partir do início do estágio e um modelo para baixar. Elas aparecem no **Guia do estágio**, que é público.
- O aluno cadastra o estágio (empresa, supervisor, carga horária, período) e envia cada documento em PDF, PNG ou JPG. Só pode ter um estágio em andamento por vez.
- A coordenação do curso, ou a secretaria, **aprova** ou **pede correção** com uma observação. O aluno é avisado e reenvia; os envios anteriores ficam no histórico.
- A barra de progresso mostra quantos documentos obrigatórios já foram aprovados.

### Chat da equipe
- Só secretaria, coordenação e administração usam; os alunos não têm acesso.
- Há um canal **Geral da equipe** e conversas diretas entre duas pessoas.
- As mensagens novas aparecem sozinhas (a página consulta `chat_novas.php` a cada 4 segundos) e o menu mostra quantas não foram lidas.
- Escrever `#15` cria um link para o chamado 15.

## Integração com o WhatsApp (opcional) — Evolution API

O sistema funciona completo sem ela: os avisos aparecem no painel e no sininho. Com a integração ligada, cada aviso também vai por WhatsApp para quem ativou a opção.

1. Tenha uma instância da **Evolution API** conectada (QR Code lido) ao WhatsApp da secretaria.
2. No sistema, como admin: **Cadastros → Integração WhatsApp**. Preencha o **endereço da Evolution**, o **nome da instância** e a **API key**, ligue a integração e salve. O quadro **Situação** mostra se a instância está conectada.
3. Use **Enviar teste** para conferir.
4. Cada usuário ativa os avisos em **Meus dados** (menu com o nome), informando o celular com DDD.

O envio usa `POST /message/sendText/{instância}` com o cabeçalho `apikey`, pelas funções `curl_*` do PHP (procedurais), sem bibliotecas externas.

**Consulta pelo WhatsApp:** o aluno manda `status 15` para o WhatsApp da secretaria e recebe o andamento do chamado 15. Para isso, na instância da Evolution, ative o **Webhook** com o endereço mostrado na tela de integração (ele já inclui uma chave secreta) e marque o evento **MESSAGES_UPSERT**. A Evolution precisa conseguir acessar esse endereço: se ela estiver na internet e o sistema no seu computador, use um túnel como o **ngrok**. Por segurança, o sistema só responde se o celular que perguntou é o do aluno dono do chamado. Outras conversas não recebem resposta automática.
