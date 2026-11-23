<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria", "coordenacao"]);
    $id_entrega = (int)($_POST['hidentrega'] ?? 0);//prevenção de ataque
    if($id_entrega < 1){
        die("ID inválido");
    }
    $resultado = $_POST['rdresultado'] ?? "";
    $obs = limpa($_POST['txtobs'] ?? "");
    $sql = "SELECT en.id_estagio, en.status, t.nome AS etapa FROM tb_entregas en
            INNER JOIN tb_etapas t ON t.id_etapa = en.id_etapa
            WHERE en.id_entrega = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_entrega);
    mysqli_stmt_execute($stmt);
    $entrega = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($entrega == null){
        die("Entrega não encontrada");
    }
    $id = $entrega['id_estagio'];
    $estagio = busca_estagio($con, $id);
    // coordenação só avalia estágio de aluno do próprio curso
    if(!pode_avaliar_estagio($estagio)){
        $_SESSION['erro'] = "Sem permissão para avaliar este estágio.";
        header("location: estagio_lista.php");
        exit;
    }
    if(!in_array($resultado, array("aprovado", "correcao"))){
        $_SESSION['erro'] = "Escolha aprovar ou pedir correção.";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    if($resultado == "correcao" && $obs == ""){
        $_SESSION['erro'] = "Explique para o aluno o que precisa ser corrigido.";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    if($entrega['status'] != "enviado"){
        $_SESSION['erro'] = "Este documento já foi avaliado.";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    $obs_banco = ($obs != "") ? $obs : null;
    $sql = "UPDATE tb_entregas SET status = ?, observacao = ?, id_avaliador = ?, avaliado_em = NOW()
            WHERE id_entrega = ? AND status = 'enviado'";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $resultado, $obs_banco, $_SESSION['id_usuario'], $id_entrega);
    mysqli_stmt_execute($stmt);
    registra_auditoria($con, "avaliacao", "tb_entregas", $id_entrega, $entrega['etapa'] . ": " . nome_status_entrega($resultado));
    $aviso = ($resultado == "aprovado")
        ? "Seu documento \"" . $entrega['etapa'] . "\" do estágio foi aprovado."
        : "Seu documento \"" . $entrega['etapa'] . "\" do estágio precisa de correção: " . $obs;
    notifica($con, $estagio['id_aluno'], null, mb_substr($aviso, 0, 250));
    $_SESSION['msg'] = "Avaliação registrada: " . strtolower(nome_status_entrega($resultado)) . ".";
    header("location: estagio_ver.php?id=$id");
?>
