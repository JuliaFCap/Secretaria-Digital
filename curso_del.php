<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "DELETE FROM tb_cursos WHERE id_curso = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "exclusao", "tb_cursos", $id, "Curso excluído");
        $_SESSION['msg'] = "Curso $id excluído com sucesso!";
    }
    else{
        // curso com usuários vinculados não pode ser apagado: fica inativo
        $sql = "UPDATE tb_cursos SET ativo = 0 WHERE id_curso = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        registra_auditoria($con, "desativacao", "tb_cursos", $id, "Curso com vínculos foi desativado");
        $_SESSION['erro'] = "O curso $id tem usuários vinculados e não pode ser apagado. Ele foi marcado como inativo.";
    }
    header("location: curso_lista.php");
?>
