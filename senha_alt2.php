<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $atual = $_POST['txtatual'] ?? "";
    $nova = $_POST['txtnova'] ?? "";
    $confirma = $_POST['txtconfirma'] ?? "";
    if(!is_string($atual) || !is_string($nova) || !is_string($confirma)){
        $_SESSION['erro'] = "Informe senhas válidas.";
        header("location: senha_alt.php");
        exit;
    }
    if(strlen($nova) < 6 || strlen($nova) > 72){
        $_SESSION['erro'] = "A nova senha precisa ter entre 6 e 72 bytes.";
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
    $sql = "SELECT senha FROM tb_usuarios WHERE id_usuario = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    if(!mysqli_stmt_prepare($stmt, $sql) ||
       !mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_usuario']) ||
       !mysqli_stmt_execute($stmt)){
        $_SESSION['erro'] = "Não foi possível verificar a senha atual.";
        header("location: senha_alt.php");
        exit;
    }
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$usuario || !verifica_senha($_SESSION['login'], $atual, $usuario['senha'])){
        $_SESSION['erro'] = "Senha atual incorreta.";
        header("location: senha_alt.php");
        exit;
    }
    $hash_nova = gera_hash($nova);
    $sql = "UPDATE tb_usuarios SET senha = ? WHERE id_usuario = ? AND senha = ?";
    $stmt = mysqli_stmt_init($con);
    if(!mysqli_stmt_prepare($stmt, $sql) ||
       !mysqli_stmt_bind_param($stmt, "sis", $hash_nova, $_SESSION['id_usuario'], $usuario['senha']) ||
       !mysqli_stmt_execute($stmt)){
        $_SESSION['erro'] = "Não foi possível salvar a nova senha.";
        header("location: senha_alt.php");
        exit;
    }
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "senha_alterada", "tb_usuarios", $_SESSION['id_usuario'], "Trocou a própria senha");
        $_SESSION['msg'] = "Senha alterada com sucesso!";
        header("location: painel.php");
        exit;
    }
    else{
        $_SESSION['erro'] = "A senha foi alterada em outra sessão. Tente novamente.";
        header("location: senha_alt.php");
        exit;
    }
?>
