<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    // os documentos do estágio ficam numa pasta bloqueada; só são entregues aqui, depois de conferir a permissão
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT id_estagio, nome_original, arquivo FROM tb_entregas WHERE id_entrega = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $entrega = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($entrega == null){
        die("Documento não encontrado");
    }
    if(!pode_ver_estagio(busca_estagio($con, $entrega['id_estagio']))){
        die("Sem permissão para ver este documento");
    }
    $caminho = "uploads/estagio/" . basename($entrega['arquivo']);
    if(!file_exists($caminho)){
        die("Arquivo não encontrado no servidor");
    }
    header("Content-Type: " . mime_content_type($caminho));
    header("Content-Disposition: inline; filename=\"" . str_replace('"', "", $entrega['nome_original']) . "\"");
    header("Content-Length: " . filesize($caminho));
    header("X-Content-Type-Options: nosniff");
    readfile($caminho);
?>
