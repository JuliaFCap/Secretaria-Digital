<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    $nota = (int)($_POST['rdnota'] ?? 0);
    if($id < 1 || $nota < 1 || $nota > 5){
        die("Dados inválidos");
    }
    // só avalia o próprio chamado, depois de finalizado, uma vez
    $sql = "UPDATE tb_chamados SET avaliacao = ?
            WHERE id_chamado = ? AND id_aluno = ? AND avaliacao IS NULL
            AND status IN ('deferido','indeferido','concluido')";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $nota, $id, $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_historico($con, $id, "Aluno avaliou o atendimento com nota $nota", null, null, null);
        $_SESSION['msg'] = "Obrigado pela avaliação!";
    }
    else{
        $_SESSION['erro'] = "Não foi possível registrar a avaliação.";
    }
    header("location: chamado_ver.php?id=$id");
?>
