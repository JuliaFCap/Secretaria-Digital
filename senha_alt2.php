<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $atual = $_POST['txtatual'] ?? "";
    $nova = $_POST['txtnova'] ?? "";
    $confirma = $_POST['txtconfirma'] ?? "";
    if(strlen($nova) < 6){
        $_SESSION['erro'] = "A nova senha precisa ter pelo menos 6 caracteres.";
        header("location: senha_alt.php");
        exit;
    }
    if($nova == $atual){
        $_SESSION['erro'] = "A nova senha precisa ser diferente da atual.";
        header("location: senha_alt.php");
        exit;
    }
    if($nova != $confirma){
        $_SESSION['erro'] = "A confirmação não confere com a nova senha.";
        header("location: senha_alt.php");
        exit;
    }
    $login = $_SESSION['login'];
    $hash_atual = gera_hash($login, $atual);
    $hash_nova = gera_hash($login, $nova);
    $sql = "UPDATE tb_usuarios SET senha = ? WHERE id_usuario = ? AND senha = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sis", $hash_nova, $_SESSION['id_usuario'], $hash_atual);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    if($qtd == 1){
        registra_auditoria($con, "senha_alterada", "tb_usuarios", $_SESSION['id_usuario'], "Trocou a própria senha");
        $_SESSION['msg'] = "Senha alterada com sucesso!";
        header("location: painel.php");
    }
    else{
        $_SESSION['erro'] = "Senha atual incorreta.";
        header("location: senha_alt.php");
    }
?>
