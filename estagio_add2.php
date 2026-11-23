<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno"]);
    require_once("estagio_valida.php");
    // um estágio em andamento por vez
    $sql = "SELECT COUNT(*) AS qtd FROM tb_estagios WHERE id_aluno = ? AND situacao = 'em_andamento'";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    if($erro == "" && mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['qtd'] > 0){
        $erro = "Você já tem um estágio em andamento. Peça para a secretaria concluir ou cancelar o anterior.";
    }
    if($erro != ""){
        $_SESSION['erro'] = $erro;
        header("location: estagio_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_estagios (id_aluno, empresa, cnpj, supervisor, email_supervisor, area, horas_semana, data_inicio, data_fim)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "isssssiss", $_SESSION['id_usuario'], $empresa, $cnpj, $supervisor, $email_sup, $area, $horas, $inicio, $fim);
    mysqli_stmt_execute($stmt);
    $ult = mysqli_insert_id($con);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "cadastro", "tb_estagios", $ult, "Estágio em $empresa");
        $_SESSION['msg'] = "Estágio cadastrado! Agora envie os documentos de cada etapa.";
        header("location: estagio_ver.php?id=$ult");
    }
    else{
        $_SESSION['erro'] = "Não foi possível cadastrar o estágio.";
        header("location: estagio_add.php");
    }
?>
