<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    // busca o usuário atual para conferir permissão e login antigo
    $sql = "SELECT login, nivel FROM tb_usuarios WHERE id_usuario = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $atual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($atual == null){
        die("Usuário não encontrado");
    }
    if($_SESSION['nivel'] == "secretaria" && $atual['nivel'] != "aluno"){
        $_SESSION['erro'] = "A secretaria só pode editar alunos.";
        header("location: usuario_lista.php");
        exit;
    }
    require_once("usuario_valida.php");
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    // o salt da senha é o login: trocou o login, precisa de senha nova
    if($erro == "" && $login != $atual['login'] && $senha_nova == ""){
        $erro = "Ao trocar o login, defina também uma nova senha.";
    }
    // ninguém tira o próprio acesso de admin nem se desativa
    if($erro == "" && $id == $_SESSION['id_usuario'] && ($nivel != $_SESSION['nivel'] || $ativo == 0)){
        $erro = "Você não pode mudar o próprio nível nem se desativar.";
    }
    if($erro != ""){
        $_SESSION['erro'] = $erro;
        header("location: usuario_alt.php?id=$id");
        exit;
    }
    if(!mysqli_begin_transaction($con)){
        $_SESSION['erro'] = "Não foi possível iniciar a alteração.";
        header("location: usuario_alt.php?id=$id");
        exit;
    }
    $sql = "UPDATE tb_usuarios
            SET nome = ?, login = ?, email = ?, nivel = ?, id_curso = ?, ra = ?,
                semestre = ?, telefone = ?, aviso_whatsapp = ?, ativo = ?
            WHERE id_usuario = ?";
    $stmt = mysqli_stmt_init($con);
    if(!mysqli_stmt_prepare($stmt, $sql) ||
       !mysqli_stmt_bind_param($stmt, "ssssisisiii", $nome, $login, $email, $nivel, $curso_banco, $ra_banco, $semestre_banco, $telefone, $whats, $ativo, $id) ||
       !mysqli_stmt_execute($stmt)){
        mysqli_rollback($con);
        $_SESSION['erro'] = "Não foi possível salvar. Login, e-mail ou RA já estão em uso.";
        header("location: usuario_alt.php?id=$id");
        exit;
    }
    if($senha_nova != ""){
        $hash = gera_hash($senha_nova);
        $sql = "UPDATE tb_usuarios SET senha = ? WHERE id_usuario = ?";
        $stmt = mysqli_stmt_init($con);
        if(!mysqli_stmt_prepare($stmt, $sql) ||
           !mysqli_stmt_bind_param($stmt, "si", $hash, $id) ||
           !mysqli_stmt_execute($stmt) ||
           mysqli_stmt_affected_rows($stmt) != 1){
            mysqli_rollback($con);
            $_SESSION['erro'] = "Não foi possível salvar a nova senha.";
            header("location: usuario_alt.php?id=$id");
            exit;
        }
    }
    if(!mysqli_commit($con)){
        mysqli_rollback($con);
        $_SESSION['erro'] = "Não foi possível concluir a alteração.";
        header("location: usuario_alt.php?id=$id");
        exit;
    }
    if($id == $_SESSION['id_usuario']){
        $_SESSION['nome'] = $nome;
        $_SESSION['login'] = $login;
    }
    registra_auditoria($con, "alteracao", "tb_usuarios", $id, "Usuário $login" . ($senha_nova != "" ? " (senha redefinida)" : ""));
    $_SESSION['msg'] = "Usuário $id alterado com sucesso!";
    header("location: usuario_lista.php");
?>
