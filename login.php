<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    if(!isset($_POST['btn'])){
        header("location: index.php");
        exit;
    }
    $login = limpa($_POST['txtlogin']);
    $senha = $_POST['txtsenha'];
    $hash = gera_hash($login, $senha);//SHA-256 com salt baseado no login
    $sql = "SELECT id_usuario, nome, login, nivel, id_curso
            FROM tb_usuarios
            WHERE login = ? AND senha = ? AND ativo = 1
            LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $login, $hash);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 1){
        session_regenerate_id(true);//novo ID de sessão após o login (segurança)
        $_SESSION['id_usuario'] = $dados[0]['id_usuario'];
        $_SESSION['nome'] = $dados[0]['nome'];
        $_SESSION['login'] = $dados[0]['login'];
        $_SESSION['nivel'] = $dados[0]['nivel'];
        $_SESSION['id_curso'] = $dados[0]['id_curso'];
        registra_auditoria($con, "login", "tb_usuarios", $dados[0]['id_usuario'], "Entrou no sistema");
        header("location: painel.php");
    }
    else{
        registra_auditoria($con, "login_falhou", "tb_usuarios", null, "Tentativa com o usuário " . $login);
        $_SESSION['erro'] = "Usuário ou senha inválidos.";
        header("location: index.php");
    }
?>
