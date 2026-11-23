<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    require_once("usuario_valida.php");
    if($erro == "" && $senha_nova == ""){
        $erro = "Defina a senha inicial.";
    }
    if($erro != ""){
        $_SESSION['erro'] = $erro;
        header("location: usuario_add.php");
        exit;
    }
    $hash = gera_hash($login, $senha_nova);//SHA-256 com salt baseado no login
    $sql = "INSERT INTO tb_usuarios
            (nome, login, senha, email, nivel, id_curso, ra, semestre, telefone, aviso_whatsapp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssssisisi", $nome, $login, $hash, $email, $nivel, $curso_banco, $ra_banco, $semestre_banco, $telefone, $whats);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    $ult = mysqli_insert_id($con);
    if($qtd == 1){
        registra_auditoria($con, "cadastro", "tb_usuarios", $ult, "Usuário $login (" . $nivel . ")");
        $_SESSION['msg'] = "Usuário $ult incluído com sucesso!";
        header("location: usuario_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível cadastrar. Login, e-mail ou RA já estão em uso.";
        header("location: usuario_add.php");
    }
?>
