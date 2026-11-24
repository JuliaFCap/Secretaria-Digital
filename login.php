<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    if(!existe_administrador($con)){
        header("location: setup_admin.php");
        exit;
    }
    if(!isset($_POST['btn'])){
        header("location: index.php");
        exit;
    }
    $login = $_POST['txtlogin'] ?? "";
    $login = is_string($login) ? limpa($login) : "";
    $senha = $_POST['txtsenha'] ?? "";
    if(!is_string($senha)){
        $senha = "";
    }
    $sql = "SELECT id_usuario, nome, login, senha, nivel, id_curso
            FROM tb_usuarios
            WHERE login = ? AND ativo = 1
            LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    if(!mysqli_stmt_prepare($stmt, $sql) ||
       !mysqli_stmt_bind_param($stmt, "s", $login) ||
       !mysqli_stmt_execute($stmt)){
        $_SESSION['erro'] = "Não foi possível verificar o acesso.";
        header("location: index.php");
        exit;
    }
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($usuario && verifica_senha($usuario['login'], $senha, $usuario['senha'])){
        if(senha_legada($usuario['senha']) || password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT)){
            $hash = gera_hash($senha);
            $sql = "UPDATE tb_usuarios SET senha = ? WHERE id_usuario = ?";
            $stmt = mysqli_stmt_init($con);
            if(!mysqli_stmt_prepare($stmt, $sql) ||
               !mysqli_stmt_bind_param($stmt, "si", $hash, $usuario['id_usuario']) ||
               !mysqli_stmt_execute($stmt) ||
               mysqli_stmt_affected_rows($stmt) != 1){
                $_SESSION['erro'] = "Não foi possível atualizar a proteção da senha. Tente novamente.";
                header("location: index.php");
                exit;
            }
        }
        session_regenerate_id(true);//novo ID de sessão após o login (segurança)
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['login'] = $usuario['login'];
        $_SESSION['nivel'] = $usuario['nivel'];
        $_SESSION['id_curso'] = $usuario['id_curso'];
        registra_auditoria($con, "login", "tb_usuarios", $usuario['id_usuario'], "Entrou no sistema");
        header("location: painel.php");
        exit;
    }
    else{
        registra_auditoria($con, "login_falhou", "tb_usuarios", null, "Tentativa com o usuário " . $login);
        $_SESSION['erro'] = "Usuário ou senha inválidos.";
        header("location: index.php");
        exit;
    }
?>
