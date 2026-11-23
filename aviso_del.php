<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    // pega o nome da imagem antes de apagar o registro
    $sql = "SELECT imagem FROM tb_avisos WHERE id_aviso = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $aviso = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($aviso == null){
        die("Aviso não encontrado");
    }
    $sql = "DELETE FROM tb_avisos WHERE id_aviso = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        $arquivo = "uploads/avisos/" . basename((string)$aviso['imagem']);
        if($aviso['imagem'] != "" && file_exists($arquivo)){
            unlink($arquivo);//remove a imagem do servidor
        }
        registra_auditoria($con, "exclusao", "tb_avisos", $id, "Aviso e imagem excluídos");
        $_SESSION['msg'] = "Aviso $id excluído com sucesso!";
    }
    header("location: aviso_lista.php");
?>
