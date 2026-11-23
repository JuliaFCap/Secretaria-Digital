<?php
    // =========================================================
    // Biblioteca de funções do sistema (modularização)
    // Importada em todas as páginas com require_once("funcoes.php")
    // =========================================================

    // Prevenção de XSS: toda saída de dado vindo do usuário passa aqui
    function seguro($texto){
        return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
    }

    // Sanitização de entrada de texto: remove espaços e tags HTML
    function limpa($texto){
        return trim(strip_tags((string)$texto));
    }

    // Hash da senha: SHA-256 com salt baseado no login
    function gera_hash($login, $senha){
        return hash("sha256", $login . $senha);
    }

    // Exige usuário logado. Se não estiver, volta para a página inicial
    function verifica_login(){
        if(!isset($_SESSION['id_usuario'])){
            $_SESSION['erro'] = "Faça login para continuar.";
            header("location: index.php");
            exit;
        }
    }

    // Exige um dos níveis informados. Ex: verifica_nivel(["admin", "secretaria"])
    function verifica_nivel($niveis){
        verifica_login();
        if(!in_array($_SESSION['nivel'], $niveis)){
            $_SESSION['erro'] = "Acesso negado para o seu perfil.";
            header("location: painel.php");
            exit;
        }
    }

    // Mostra (e apaga) as mensagens guardadas na sessão
    function exibe_msg(){
        if(isset($_SESSION['msg'])){
            echo "<div class='alert alert-success'>" . seguro($_SESSION['msg']) . "</div>";
            unset($_SESSION['msg']);
        }
        if(isset($_SESSION['erro'])){
            echo "<div class='alert alert-danger'>" . seguro($_SESSION['erro']) . "</div>";
            unset($_SESSION['erro']);
        }
    }

    // Nome amigável de cada nível
    function nome_nivel($nivel){
        $nomes = array(
            "aluno" => "Aluno",
            "secretaria" => "Secretaria",
            "coordenacao" => "Coordenação",
            "admin" => "Administração"
        );
        return isset($nomes[$nivel]) ? $nomes[$nivel] : $nivel;
    }

    // Nome amigável de cada status de chamado
    function nome_status($status){
        $nomes = array(
            "aberto" => "Aberto",
            "em_analise" => "Em análise",
            "aguardando_aluno" => "Aguardando aluno",
            "aguardando_coordenacao" => "Aguardando coordenação",
            "deferido" => "Deferido",
            "indeferido" => "Indeferido",
            "concluido" => "Concluído"
        );
        return isset($nomes[$status]) ? $nomes[$status] : $status;
    }

    // Etiqueta colorida do status
    function etiqueta_status($status){
        return "<span class='status status-$status'>" . nome_status($status) . "</span>";
    }

    // Formata data do banco (AAAA-MM-DD ...) para DD/MM/AAAA
    function data_br($data, $com_hora = false){
        if(empty($data)){
            return "—";
        }
        $tempo = strtotime($data);
        return $com_hora ? date("d/m/Y H:i", $tempo) : date("d/m/Y", $tempo);
    }

    // Texto do prazo: "Atrasado 2 dias", "Vence hoje", "Vence em 4 dias"
    function texto_prazo($prazo, $status){
        if(in_array($status, array("deferido", "indeferido", "concluido"))){
            return "—";
        }
        if($status == "aguardando_aluno"){
            return "Pausado";
        }
        $dias = (int)floor((strtotime($prazo) - strtotime(date("Y-m-d"))) / 86400);
        if($dias < 0){
            return "<span class='text-danger fw-bold'>Atrasado " . abs($dias) . " dia(s)</span>";
        }
        if($dias == 0){
            return "<span class='text-warning-emphasis fw-bold'>Vence hoje</span>";
        }
        return "Vence em $dias dia(s)";
    }

    // Código de validação das declarações (16 caracteres)
    function gera_codigo(){
        return strtoupper(bin2hex(random_bytes(8)));
    }

    // Log de auditoria geral
    function registra_auditoria($con, $acao, $tabela, $id_registro, $detalhes){
        $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? "";
        $sql = "INSERT INTO tb_auditoria (id_usuario, acao, tabela, id_registro, detalhes, ip)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "ississ", $id_usuario, $acao, $tabela, $id_registro, $detalhes, $ip);
        mysqli_stmt_execute($stmt);
    }

    // Histórico de um chamado
    function registra_historico($con, $id_chamado, $acao, $status_anterior, $status_novo, $observacao){
        $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
        $sql = "INSERT INTO tb_historico (id_chamado, id_usuario, acao, status_anterior, status_novo, observacao)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iissss", $id_chamado, $id_usuario, $acao, $status_anterior, $status_novo, $observacao);
        mysqli_stmt_execute($stmt);
    }

    // Notificação para um usuário (aparece no painel dele e, se ele ativou, vai também por WhatsApp)
    function notifica($con, $id_usuario, $id_chamado, $mensagem){
        $sql = "INSERT INTO tb_notificacoes (id_usuario, id_chamado, canal, mensagem)
                VALUES (?, ?, 'sistema', ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $id_usuario, $id_chamado, $mensagem);
        mysqli_stmt_execute($stmt);
        envia_whatsapp_usuario($con, $id_usuario, $id_chamado, $mensagem);
    }

    // ---------------------------------------------------------
    // Configurações guardadas no banco (tb_configuracoes)
    // ---------------------------------------------------------
    function busca_config($con, $chave){
        $sql = "SELECT valor FROM tb_configuracoes WHERE chave = ? LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "s", $chave);
        mysqli_stmt_execute($stmt);
        $linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return $linha ? (string)$linha['valor'] : "";
    }

    function salva_config($con, $chave, $valor){
        $sql = "INSERT INTO tb_configuracoes (chave, valor) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $chave, $valor);
        mysqli_stmt_execute($stmt);
    }

    // ---------------------------------------------------------
    // Integração com o WhatsApp pela Evolution API (instância conectada ao número da secretaria)
    // ---------------------------------------------------------

    // Converte o celular para o formato internacional: (16) 99999-8888 -> 5516999998888
    function telefone_whatsapp($telefone){
        $digitos = preg_replace("/\D/", "", (string)$telefone);
        if(strlen($digitos) == 10 || strlen($digitos) == 11){
            $digitos = "55" . $digitos;//acrescenta o código do Brasil
        }
        if(strlen($digitos) < 12 || strlen($digitos) > 15){
            return "";
        }
        return $digitos;
    }

    // Compara dois números de WhatsApp aceitando o celular brasileiro com ou sem o 9 extra
    // (o WhatsApp às vezes identifica 5516998887777 como 551698887777)
    function mesmo_whatsapp($numero1, $numero2){
        $a = preg_replace("/\D/", "", (string)$numero1);
        $b = preg_replace("/\D/", "", (string)$numero2);
        if($a == "" || $b == ""){
            return false;
        }
        if($a == $b){
            return true;
        }
        return sem_nono_digito($a) == sem_nono_digito($b);
    }

    function sem_nono_digito($numero){
        // 55 + DDD (2) + 9 + 8 dígitos = 13 dígitos -> remove o 9
        if(strlen($numero) == 13 && substr($numero, 0, 2) == "55" && substr($numero, 4, 1) == "9"){
            return substr($numero, 0, 4) . substr($numero, 5);
        }
        return $numero;
    }

    // Chama a Evolution API. Devolve array com o código HTTP e a resposta já convertida
    function chama_evolution($con, $metodo, $caminho, $dados = null){
        $url = rtrim(busca_config($con, "evolution_url"), "/");
        $apikey = busca_config($con, "evolution_apikey");
        if($url == "" || $apikey == ""){
            return array("codigo" => 0, "resposta" => null);
        }
        $curl = curl_init($url . $caminho);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("apikey: " . $apikey, "Content-Type: application/json"));
        if($dados !== null){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($curl, CURLOPT_TIMEOUT, 8);//não deixa o sistema travar se a Evolution cair
        $resposta = curl_exec($curl);
        $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $erro_rede = curl_error($curl);
        curl_close($curl);
        return array("codigo" => $codigo, "resposta" => json_decode((string)$resposta, true), "erro_rede" => $erro_rede);
    }

    // Estado da conexão da instância: "open" = conectada ao WhatsApp
    function estado_evolution($con){
        $instancia = busca_config($con, "evolution_instancia");
        if($instancia == ""){
            return "";
        }
        $r = chama_evolution($con, "GET", "/instance/connectionState/" . rawurlencode($instancia));
        if($r['codigo'] == 0){
            return "sem resposta";
        }
        return $r['resposta']['instance']['state'] ?? ("erro HTTP " . $r['codigo']);
    }

    // Envia uma mensagem de texto pela instância da Evolution. Devolve true se a Evolution aceitou.
    function envia_whatsapp($con, $numero, $mensagem){
        if(busca_config($con, "whatsapp_ativo") != "1"){
            return false;
        }
        $instancia = busca_config($con, "evolution_instancia");
        if($instancia == "" || $numero == ""){
            return false;
        }
        $dados = array("number" => $numero, "text" => $mensagem);
        $r = chama_evolution($con, "POST", "/message/sendText/" . rawurlencode($instancia), $dados);
        if($r['codigo'] == 200 || $r['codigo'] == 201){
            return true;
        }
        // guarda o motivo do erro para o admin ver na tela da integração
        if($r['codigo'] == 0){
            $erro = "Sem resposta da Evolution" . (!empty($r['erro_rede']) ? " (" . $r['erro_rede'] . ")" : "");
        }
        else{
            $erro = "HTTP " . $r['codigo'];
            $detalhe = $r['resposta']['response']['message'] ?? ($r['resposta']['message'] ?? ($r['resposta']['error'] ?? ""));
            if(is_array($detalhe)){
                $detalhe = json_encode($detalhe, JSON_UNESCAPED_UNICODE);
            }
            if($detalhe != ""){
                $erro .= " - " . $detalhe;
            }
        }
        salva_config($con, "whatsapp_ultimo_erro", date("d/m/Y H:i") . " · " . mb_substr($erro, 0, 200));
        return false;
    }

    // Envia o aviso por WhatsApp para um usuário que ativou essa opção e registra o envio
    function envia_whatsapp_usuario($con, $id_usuario, $id_chamado, $mensagem){
        if(busca_config($con, "whatsapp_ativo") != "1"){
            return;
        }
        $sql = "SELECT telefone FROM tb_usuarios WHERE id_usuario = ? AND aviso_whatsapp = 1 AND ativo = 1 LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if($usuario == null){
            return;
        }
        $numero = telefone_whatsapp($usuario['telefone']);
        if($numero == ""){
            return;
        }
        $texto = "Secretaria Digital FATEC: " . $mensagem;
        $enviada = envia_whatsapp($con, $numero, $texto) ? 1 : 0;
        $sql = "INSERT INTO tb_notificacoes (id_usuario, id_chamado, canal, mensagem, lida, enviada)
                VALUES (?, ?, 'whatsapp', ?, 1, ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iisi", $id_usuario, $id_chamado, $mensagem, $enviada);
        mysqli_stmt_execute($stmt);
    }

    // Lembrete automático: chamado parado em "aguardando aluno" há X dias
    // não é fechado; o aluno recebe um aviso e a secretaria vê o alerta no card
    function envia_lembretes($con, $dias = 3){
        $sql = "SELECT id_chamado, id_aluno FROM tb_chamados
                WHERE status = 'aguardando_aluno'
                AND aguardando_desde <= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND (ultimo_lembrete IS NULL OR ultimo_lembrete <= DATE_SUB(NOW(), INTERVAL ? DAY))";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $dias, $dias);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
        foreach($dados as $ch){
            notifica($con, $ch['id_aluno'], $ch['id_chamado'],
                "Lembrete: o chamado #" . $ch['id_chamado'] . " está aguardando sua resposta.");
            $sql2 = "UPDATE tb_chamados SET ultimo_lembrete = NOW() WHERE id_chamado = ?";
            $stmt2 = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt2, $sql2);
            mysqli_stmt_bind_param($stmt2, "i", $ch['id_chamado']);
            mysqli_stmt_execute($stmt2);
            registra_historico($con, $ch['id_chamado'], "Lembrete automático enviado ao aluno", null, null, null);
        }
        return count($dados);
    }

    // Ordenação segura dos relatórios: só aceita colunas da lista permitida
    // $permitidas = array("chave_na_url" => "coluna_sql")
    function ordem_sql($permitidas, $padrao){
        $campo = $_GET['ordem'] ?? $padrao;
        if(!array_key_exists($campo, $permitidas)){
            $campo = $padrao;
        }
        $direcao = (isset($_GET['dir']) && $_GET['dir'] == "desc") ? "DESC" : "ASC";
        return $permitidas[$campo] . " " . $direcao;
    }

    // Cabeçalho de coluna clicável para ordenar
    function th_ordem($rotulo, $campo){
        $atual = $_GET['ordem'] ?? "";
        $dir = $_GET['dir'] ?? "asc";
        $nova_dir = ($atual == $campo && $dir == "asc") ? "desc" : "asc";
        $parametros = $_GET;
        $parametros['ordem'] = $campo;
        $parametros['dir'] = $nova_dir;
        $seta = "";
        if($atual == $campo){
            $seta = ($dir == "asc") ? " ▲" : " ▼";
        }
        return "<th><a class='link-ordem' href='?" . seguro(http_build_query($parametros)) . "'>$rotulo$seta</a></th>";
    }

    // Busca um chamado com aluno, curso, tipo e responsável
    function busca_chamado($con, $id){
        $sql = "SELECT c.*, t.nome AS tipo, t.precisa_coordenacao, t.documentos_exigidos,
                       a.nome AS aluno, a.ra, a.email AS email_aluno, a.id_curso AS curso_aluno, a.semestre,
                       cu.nome AS curso, cu.sigla,
                       r.nome AS responsavel, p.nome AS parecerista
                FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
                LEFT JOIN tb_cursos cu ON cu.id_curso = a.id_curso
                LEFT JOIN tb_usuarios r ON r.id_usuario = c.id_responsavel
                LEFT JOIN tb_usuarios p ON p.id_usuario = c.id_parecerista
                WHERE c.id_chamado = ? LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($res);
    }

    // Cada nível acessa apenas o permitido:
    // aluno -> só os próprios; coordenação -> só do seu curso; secretaria e admin -> todos
    function pode_ver_chamado($chamado){
        if($chamado == null){
            return false;
        }
        $nivel = $_SESSION['nivel'];
        if($nivel == "aluno"){
            return $chamado['id_aluno'] == $_SESSION['id_usuario'];
        }
        if($nivel == "coordenacao"){
            return $chamado['curso_aluno'] == $_SESSION['id_curso'];
        }
        return ($nivel == "secretaria" || $nivel == "admin");
    }

    // Muda o status de um chamado, grava o histórico e avisa quem precisa saber
    function muda_status($con, $chamado, $novo, $observacao){
        $id = $chamado['id_chamado'];
        $anterior = $chamado['status'];
        if($anterior == $novo){
            return;
        }
        if($novo == "aguardando_aluno"){
            // pausa o prazo e começa a contar para o lembrete automático
            $sql = "UPDATE tb_chamados SET status = ?, aguardando_desde = NOW(), ultimo_lembrete = NULL WHERE id_chamado = ?";
        }
        else if($anterior == "aguardando_aluno"){
            // retoma: o prazo anda os dias em que ficou pausado
            $sql = "UPDATE tb_chamados
                    SET status = ?, prazo = DATE_ADD(prazo, INTERVAL DATEDIFF(NOW(), aguardando_desde) DAY),
                        aguardando_desde = NULL
                    WHERE id_chamado = ?";
        }
        else{
            $sql = "UPDATE tb_chamados SET status = ? WHERE id_chamado = ?";
        }
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "si", $novo, $id);
        mysqli_stmt_execute($stmt);
        registra_historico($con, $id, "Status alterado", $anterior, $novo, $observacao);
        // avisa o aluno de toda mudança de status
        notifica($con, $chamado['id_aluno'], $id, "Seu chamado #$id agora está: " . nome_status($novo) . ".");
        // avisa a coordenação do curso quando o chamado chega para parecer
        if($novo == "aguardando_coordenacao"){
            $sql = "SELECT id_usuario FROM tb_usuarios WHERE nivel = 'coordenacao' AND ativo = 1 AND id_curso = ?";
            $stmt = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "i", $chamado['curso_aluno']);
            mysqli_stmt_execute($stmt);
            $coords = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
            foreach($coords as $coord){
                notifica($con, $coord['id_usuario'], $id, "Chamado #$id aguarda o seu parecer.");
            }
        }
    }

    // Upload de anexo do chamado (PDF, PNG ou JPG até 2 MB)
    // devolve array com nome original, nome salvo e tamanho, ou null se não houver arquivo válido
    function envia_anexo($campo, $pasta){
        if(!isset($_FILES[$campo]) || $_FILES[$campo]['size'] == 0){
            return null;
        }
        $tam = $_FILES[$campo]['size'];
        $origem = $_FILES[$campo]['tmp_name'];
        $tipo = mime_content_type($origem);
        $tipos = array("application/pdf" => ".pdf", "image/png" => ".png", "image/jpeg" => ".jpg");
        if($tam > (2*1024*1024)){
            $_SESSION['erro'] = "Erro! Arquivo muito grande (máximo 2 MB).";
            return null;
        }
        if(!isset($tipos[$tipo])){
            $_SESSION['erro'] = "Erro! O anexo precisa ser PDF, PNG ou JPG.";
            return null;
        }
        $nomenovo = date("YmdHis") . "_" . bin2hex(random_bytes(6)) . $tipos[$tipo];
        if(!move_uploaded_file($origem, $pasta . $nomenovo)){
            $_SESSION['erro'] = "Falha no upload do anexo.";
            return null;
        }
        $original = limpa(basename($_FILES[$campo]['name']));
        return array("original" => $original, "arquivo" => $nomenovo, "tamanho" => $tam);
    }

    // Grava o anexo enviado no banco, ligado ao chamado
    function grava_anexo($con, $id_chamado, $anexo){
        $sql = "INSERT INTO tb_anexos (id_chamado, id_usuario, nome_original, arquivo, tamanho) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iissi", $id_chamado, $_SESSION['id_usuario'], $anexo['original'], $anexo['arquivo'], $anexo['tamanho']);
        mysqli_stmt_execute($stmt);
    }

    // ---------------------------------------------------------
    // Estágio
    // ---------------------------------------------------------

    // Upload do modelo de documento (PDF, DOC ou DOCX até 2 MB) que o aluno vai baixar
    function envia_modelo($campo, $pasta){
        if(!isset($_FILES[$campo]) || $_FILES[$campo]['size'] == 0){
            return null;
        }
        $tam = $_FILES[$campo]['size'];
        $origem = $_FILES[$campo]['tmp_name'];
        $tipo = mime_content_type($origem);
        $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        $permitidos = array(
            "pdf" => array("application/pdf"),
            "doc" => array("application/msword", "application/CDFV2", "application/x-ole-storage"),
            "docx" => array("application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/zip", "application/octet-stream")
        );
        if($tam > (2*1024*1024)){
            $_SESSION['erro'] = "Erro! Modelo muito grande (máximo 2 MB).";
            return null;
        }
        if(!isset($permitidos[$ext]) || !in_array($tipo, $permitidos[$ext])){
            $_SESSION['erro'] = "Erro! O modelo precisa ser PDF, DOC ou DOCX.";
            return null;
        }
        $nomenovo = date("YmdHis") . "_" . bin2hex(random_bytes(6)) . "." . $ext;
        if(!move_uploaded_file($origem, $pasta . $nomenovo)){
            $_SESSION['erro'] = "Falha no upload do modelo.";
            return null;
        }
        return array("arquivo" => $nomenovo, "original" => limpa(basename($_FILES[$campo]['name'])));
    }

    // Busca um estágio com os dados do aluno e do curso
    function busca_estagio($con, $id){
        $sql = "SELECT e.*, u.nome AS aluno, u.ra, u.email AS email_aluno, u.id_curso AS curso_aluno, c.sigla
                FROM tb_estagios e
                INNER JOIN tb_usuarios u ON u.id_usuario = e.id_aluno
                LEFT JOIN tb_cursos c ON c.id_curso = u.id_curso
                WHERE e.id_estagio = ? LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }

    // aluno -> só o próprio; coordenação -> só do seu curso; secretaria e admin -> todos
    function pode_ver_estagio($estagio){
        if($estagio == null){
            return false;
        }
        $nivel = $_SESSION['nivel'];
        if($nivel == "aluno"){
            return $estagio['id_aluno'] == $_SESSION['id_usuario'];
        }
        if($nivel == "coordenacao"){
            return $estagio['curso_aluno'] == $_SESSION['id_curso'];
        }
        return ($nivel == "secretaria" || $nivel == "admin");
    }

    // quem pode aprovar ou pedir correção dos documentos
    function pode_avaliar_estagio($estagio){
        return pode_ver_estagio($estagio) && in_array($_SESSION['nivel'], array("secretaria", "coordenacao"));
    }

    // Quantas etapas obrigatórias já foram aprovadas: array("aprovadas" => x, "total" => y)
    function progresso_estagio($con, $id_estagio){
        $sql = "SELECT COUNT(*) AS total,
                       SUM(EXISTS(SELECT 1 FROM tb_entregas en
                                  WHERE en.id_estagio = ? AND en.id_etapa = t.id_etapa AND en.status = 'aprovado')) AS aprovadas
                FROM tb_etapas t WHERE t.ativo = 1 AND t.obrigatoria = 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_estagio);
        mysqli_stmt_execute($stmt);
        $linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        return array("aprovadas" => (int)$linha['aprovadas'], "total" => (int)$linha['total']);
    }

    function nome_situacao_estagio($situacao){
        $nomes = array("em_andamento" => "Em andamento", "concluido" => "Concluído", "cancelado" => "Cancelado");
        return $nomes[$situacao] ?? $situacao;
    }

    function nome_status_entrega($status){
        $nomes = array("pendente" => "Pendente", "enviado" => "Aguardando análise", "aprovado" => "Aprovado", "correcao" => "Correção pedida");
        return $nomes[$status] ?? $status;
    }

    // ---------------------------------------------------------
    // Chat interno da equipe
    // ---------------------------------------------------------

    // Total de mensagens não lidas: conversas diretas + canal geral
    function chat_nao_lidas($con, $id_usuario){
        $sql = "SELECT
                  (SELECT COUNT(*) FROM tb_chat WHERE id_destinatario = ? AND lida = 0) +
                  (SELECT COUNT(*) FROM tb_chat
                   WHERE id_destinatario IS NULL AND id_remetente <> ?
                   AND id_chat > COALESCE((SELECT ultimo_id FROM tb_chat_geral_lido WHERE id_usuario = ?), 0)) AS qtd";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $id_usuario, $id_usuario, $id_usuario);
        mysqli_stmt_execute($stmt);
        return (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['qtd'];
    }

    // Busca as mensagens de uma conversa (0 = canal geral) depois do id informado
    function chat_mensagens($con, $id_usuario, $com, $depois){
        if($com == 0){
            $sql = "SELECT c.id_chat, c.id_remetente, c.mensagem, c.criado_em, u.nome, u.nivel
                    FROM tb_chat c INNER JOIN tb_usuarios u ON u.id_usuario = c.id_remetente
                    WHERE c.id_destinatario IS NULL AND c.id_chat > ?
                    ORDER BY c.id_chat DESC LIMIT 100";
            $stmt = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "i", $depois);
        }
        else{
            $sql = "SELECT c.id_chat, c.id_remetente, c.mensagem, c.criado_em, u.nome, u.nivel
                    FROM tb_chat c INNER JOIN tb_usuarios u ON u.id_usuario = c.id_remetente
                    WHERE ((c.id_remetente = ? AND c.id_destinatario = ?) OR (c.id_remetente = ? AND c.id_destinatario = ?))
                    AND c.id_chat > ?
                    ORDER BY c.id_chat DESC LIMIT 100";
            $stmt = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "iiiii", $id_usuario, $com, $com, $id_usuario, $depois);
        }
        mysqli_stmt_execute($stmt);
        $lista = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        return array_reverse($lista);//mais antigas primeiro
    }

    // Marca a conversa como lida para o usuário
    function chat_marca_lida($con, $id_usuario, $com){
        if($com == 0){
            $sql = "INSERT INTO tb_chat_geral_lido (id_usuario, ultimo_id)
                    SELECT ?, COALESCE(MAX(id_chat), 0) FROM tb_chat WHERE id_destinatario IS NULL
                    ON DUPLICATE KEY UPDATE ultimo_id = VALUES(ultimo_id)";
            $stmt = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        }
        else{
            $sql = "UPDATE tb_chat SET lida = 1 WHERE id_remetente = ? AND id_destinatario = ? AND lida = 0";
            $stmt = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $com, $id_usuario);
        }
        mysqli_stmt_execute($stmt);
    }

    // Texto da mensagem com segurança: escapa o HTML e transforma "#15" em link para o chamado 15
    function chat_formata($texto){
        $seguro = nl2br(seguro($texto));
        return preg_replace("/(?<!&)#(\d{1,9})\b/", "<a href='chamado_ver.php?id=$1'>#$1</a>", $seguro);
    }

    // Upload de imagem (padrão da aula de upload): devolve o nome salvo ou "" em caso de erro
    function envia_imagem($campo, $pasta){
        if(!isset($_FILES[$campo]) || $_FILES[$campo]['size'] == 0){
            return "";
        }
        $tam = $_FILES[$campo]['size'];
        $origem = $_FILES[$campo]['tmp_name'];
        $tipo = mime_content_type($origem);
        if($tam > (2*1024*1024)){
            $_SESSION['erro'] = "Erro! Imagem muito grande (máximo 2 MB).";
            return "";
        }
        if($tipo == "image/png"){
            $ext = ".png";
        }
        else if($tipo == "image/jpeg"){
            $ext = ".jpg";
        }
        else{
            $_SESSION['erro'] = "Erro! A imagem precisa ser PNG ou JPG.";
            return "";
        }
        $nomenovo = date("YmdHis") . "_" . rand(1000, 9999) . $ext;
        if(!move_uploaded_file($origem, $pasta . $nomenovo)){
            $_SESSION['erro'] = "Falha no upload da imagem.";
            return "";
        }
        return $nomenovo;
    }
?>
