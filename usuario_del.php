<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    if($id == $_SESSION['id_usuario']){
        $_SESSION['erro'] = "Você não pode excluir a si mesmo.";
        header("location: usuario_lista.php");
        exit;
    }
    // secretaria só exclui aluno
    $sql = "SELECT nivel FROM tb_usuarios WHERE id_usuario = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $alvo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($alvo == null || ($_SESSION['nivel'] == "secretaria" && $alvo['nivel'] != "aluno")){
        $_SESSION['erro'] = "Exclusão não permitida.";
        header("location: usuario_lista.php");
        exit;
    }
    $sql = "DELETE FROM tb_usuarios WHERE id_usuario = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "exclusao", "tb_usuarios", $id, "Usuário excluído");
        $_SESSION['msg'] = "Usuário $id excluído com sucesso!";
    }
    else{
        // quem já tem chamados, mensagens ou registros no histórico é desativado, não apagado
        $sql = "UPDATE tb_usuarios SET ativo = 0 WHERE id_usuario = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        registra_auditoria($con, "desativacao", "tb_usuarios", $id, "Usuário com histórico foi desativado");
        $_SESSION['erro'] = "O usuário $id tem histórico no sistema e não pode ser apagado. Ele foi desativado e não consegue mais entrar.";
    }
    header("location: usuario_lista.php");
?>
