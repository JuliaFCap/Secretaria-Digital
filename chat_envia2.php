<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria", "coordenacao", "admin"]);
    $com = (int)($_POST['hidcom'] ?? 0);//prevenção de ataque
    $mensagem = trim(strip_tags((string)($_POST['txtmensagem'] ?? "")));
    $mensagem = mb_substr($mensagem, 0, 1000);
    if($mensagem == ""){
        header("location: chat.php" . ($com > 0 ? "?com=$com" : ""));
        exit;
    }
    if($com > 0){
        // conversa direta: o destinatário precisa ser da equipe e estar ativo
        $sql = "SELECT id_usuario FROM tb_usuarios WHERE id_usuario = ? AND ativo = 1 AND nivel IN ('secretaria','coordenacao','admin') LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $com);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) != 1 || $com == $_SESSION['id_usuario']){
            $_SESSION['erro'] = "Destinatário inválido.";
            header("location: chat.php");
            exit;
        }
        $sql = "INSERT INTO tb_chat (id_remetente, id_destinatario, mensagem) VALUES (?, ?, ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $_SESSION['id_usuario'], $com, $mensagem);
    }
    else{
        // canal geral da equipe
        $sql = "INSERT INTO tb_chat (id_remetente, id_destinatario, mensagem) VALUES (?, NULL, ?)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "is", $_SESSION['id_usuario'], $mensagem);
    }
    mysqli_stmt_execute($stmt);
    header("location: chat.php" . ($com > 0 ? "?com=$com" : ""));
?>
