<?php
    // Devolve em JSON as mensagens novas de uma conversa (usado pela atualização automática do chat)
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    header("Content-Type: application/json; charset=utf-8");
    if(!isset($_SESSION['id_usuario']) || !in_array($_SESSION['nivel'], array("secretaria", "coordenacao", "admin"))){
        http_response_code(403);
        exit("[]");
    }
    $eu = $_SESSION['id_usuario'];
    $com = (int)($_GET['com'] ?? 0);
    $depois = (int)($_GET['depois'] ?? 0);
    // conversa direta só com alguém da equipe
    if($com > 0){
        $sql = "SELECT id_usuario FROM tb_usuarios WHERE id_usuario = ? AND nivel IN ('secretaria','coordenacao','admin') LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $com);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) != 1){
            exit("[]");
        }
    }
    $novas = chat_mensagens($con, $eu, $com, $depois);
    if(count($novas) > 0){
        chat_marca_lida($con, $eu, $com);
    }
    $saida = array();
    foreach($novas as $m){
        // o texto vai cru: a página monta com textContent, então nenhum HTML é interpretado
        $saida[] = array(
            "id" => (int)$m['id_chat'],
            "nome" => $m['nome'],
            "data" => data_br($m['criado_em'], true),
            "mensagem" => $m['mensagem'],
            "meu" => ($m['id_remetente'] == $eu)
        );
    }
    echo json_encode($saida, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
