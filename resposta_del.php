<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "DELETE FROM tb_respostas WHERE id_resposta = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "exclusao", "tb_respostas", $id, "Resposta pronta");
        $_SESSION['msg'] = "Resposta $id excluída com sucesso!";
    }
    else{
        $_SESSION['erro'] = "Resposta não encontrada.";
    }
    header("location: resposta_lista.php");
?>
