<?php
    // Webhook da Evolution API: a Evolution chama este endereço quando alguém manda mensagem
    // para o WhatsApp da secretaria. O aluno escreve "status 15" e recebe o andamento do chamado 15.
    // Não usa sessão: quem chama é o servidor da Evolution, não um usuário logado.
    require_once("conecta.php");
    require_once("funcoes.php");

    // só aceita chamadas com a chave secreta gerada na tela de integração
    $chave = busca_config($con, "whatsapp_webhook_chave");
    if($chave == "" || !hash_equals($chave, (string)($_GET['chave'] ?? ""))){
        http_response_code(403);
        exit("Acesso negado");
    }
    http_response_code(200);
    $corpo = json_decode(file_get_contents("php://input"), true);
    if(!is_array($corpo) || busca_config($con, "whatsapp_ativo") != "1"){
        exit("ok");
    }
    // a Evolution manda o nome do evento como "messages.upsert" ou "MESSAGES_UPSERT"
    $evento = strtolower(str_replace("_", ".", $corpo['event'] ?? ""));
    if($evento != "messages.upsert"){
        exit("ok");
    }
    $dados = $corpo['data'] ?? array();
    // alguns envios trazem uma lista de mensagens, outros uma só
    $lista = isset($dados['key']) ? array($dados) : ($dados['messages'] ?? array());
    foreach($lista as $msg){
        $jid = $msg['key']['remoteJid'] ?? "";
        if(!empty($msg['key']['fromMe']) || str_ends_with($jid, "@g.us")){
            continue;//ignora mensagens enviadas pela própria secretaria e mensagens de grupo
        }
        // quando o WhatsApp usa identificador @lid, o número real vem em remoteJidAlt/senderPn
        if(str_ends_with($jid, "@lid")){
            $jid = $msg['key']['remoteJidAlt'] ?? ($msg['key']['senderPn'] ?? "");
        }
        $remetente = preg_replace("/\D/", "", explode("@", $jid)[0]);
        $texto = $msg['message']['conversation'] ?? ($msg['message']['extendedTextMessage']['text'] ?? "");
        $texto = strtolower(trim((string)$texto));
        if($remetente == "" || $texto == ""){
            continue;
        }
        if(preg_match("/status\s*#?\s*(\d+)/", $texto, $achou)){
            $id = (int)$achou[1];
            $chamado = busca_chamado($con, $id);
            // segurança: só responde se o celular de quem perguntou é o do aluno dono do chamado
            $dono = null;
            if($chamado != null){
                $sql = "SELECT telefone FROM tb_usuarios WHERE id_usuario = ? LIMIT 1";
                $stmt = mysqli_stmt_init($con);
                mysqli_stmt_prepare($stmt, $sql);
                mysqli_stmt_bind_param($stmt, "i", $chamado['id_aluno']);
                mysqli_stmt_execute($stmt);
                $dono = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            }
            if($chamado != null && $dono != null && mesmo_whatsapp(telefone_whatsapp($dono['telefone']), $remetente)){
                $prazo = in_array($chamado['status'], array("deferido", "indeferido", "concluido", "aguardando_aluno"))
                    ? "" : " Prazo: " . data_br($chamado['prazo']) . ".";
                $resposta = "Chamado #$id (" . $chamado['tipo'] . "): " . nome_status($chamado['status']) . "." . $prazo;
                if($chamado['status'] == "aguardando_aluno"){
                    $resposta .= " A secretaria está esperando uma resposta sua no sistema.";
                }
            }
            else{
                $resposta = "Não encontramos o chamado #$id para este número. Confira o número do chamado ou o celular cadastrado no sistema.";
            }
            envia_whatsapp($con, $remetente, $resposta);
        }
        else if(preg_match("/^(oi|olá|ola|menu|ajuda|status)$/", $texto)){
            envia_whatsapp($con, $remetente, "Olá! Aqui é a Secretaria Digital da FATEC. Para consultar um chamado, envie: status NÚMERO (ex.: status 15).");
        }
        // outras mensagens não recebem resposta automática: a secretaria pode responder pelo próprio WhatsApp
    }
    echo "ok";
?>
