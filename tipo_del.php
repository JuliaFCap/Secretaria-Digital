<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "DELETE FROM tb_tipos WHERE id_tipo = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "exclusao", "tb_tipos", $id, "Tipo excluído");
        $_SESSION['msg'] = "Tipo $id excluído com sucesso!";
    }
    else{
        // tipo já usado em chamados não pode ser apagado: fica inativo
        $sql = "UPDATE tb_tipos SET ativo = 0 WHERE id_tipo = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        registra_auditoria($con, "desativacao", "tb_tipos", $id, "Tipo com chamados foi desativado");
        $_SESSION['erro'] = "O tipo $id já tem chamados e não pode ser apagado. Ele foi marcado como inativo.";
    }
    header("location: tipo_lista.php");
?>
