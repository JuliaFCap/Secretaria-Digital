<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    // os anexos ficam numa pasta bloqueada; só são entregues aqui, depois de conferir a permissão
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT id_chamado, nome_original, arquivo FROM tb_anexos WHERE id_anexo = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $anexo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($anexo == null){
        die("Anexo não encontrado");
    }
    $chamado = busca_chamado($con, $anexo['id_chamado']);
    if(!pode_ver_chamado($chamado)){
        die("Sem permissão para ver este anexo");
    }
    $caminho = "uploads/anexos/" . basename($anexo['arquivo']);
    if(!file_exists($caminho)){
        die("Arquivo não encontrado no servidor");
    }
    header("Content-Type: " . mime_content_type($caminho));
    header("Content-Disposition: inline; filename=\"" . str_replace('"', "", $anexo['nome_original']) . "\"");
    header("Content-Length: " . filesize($caminho));
    header("X-Content-Type-Options: nosniff");
    readfile($caminho);
?>
