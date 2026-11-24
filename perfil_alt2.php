<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $telefone = limpa($_POST['txttelefone'] ?? "");
    $whats = isset($_POST['ckwhats']) ? 1 : 0;
    if($telefone == "" || strlen($telefone) > 20){
        $_SESSION['erro'] = "Informe um telefone com até 20 caracteres.";
        header("location: perfil_alt.php");
        exit;
    }
    if($whats == 1 && telefone_whatsapp($telefone) == ""){
        $_SESSION['erro'] = "Para receber avisos por WhatsApp, informe um celular válido com DDD.";
        header("location: perfil_alt.php");
        exit;
    }
    $sql = "UPDATE tb_usuarios SET telefone = ?, aviso_whatsapp = ? WHERE id_usuario = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $telefone, $whats, $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    registra_auditoria($con, "alteracao", "tb_usuarios", $_SESSION['id_usuario'], "Atualizou os próprios dados de contato");
    $_SESSION['msg'] = "Dados salvos.";
    header("location: perfil_alt.php");
?>
