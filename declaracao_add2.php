<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno", "secretaria"]);
    $tipo = $_POST['seltipo'] ?? "";
    if($tipo != "matricula" && $tipo != "frequencia"){
        die("Tipo de declaração inválido");
    }
    // aluno emite a própria; secretaria escolhe o aluno
    if($_SESSION['nivel'] == "aluno"){
        $id_aluno = $_SESSION['id_usuario'];
    }
    else{
        $id_aluno = (int)($_POST['selaluno'] ?? 0);
    }
    $sql = "SELECT id_usuario FROM tb_usuarios WHERE id_usuario = ? AND nivel = 'aluno' AND ativo = 1 LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_aluno);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if(mysqli_stmt_num_rows($stmt) != 1){
        $_SESSION['erro'] = "Aluno inválido.";
        header("location: declaracao_lista.php");
        exit;
    }
    $codigo = gera_codigo();
    $sql = "INSERT INTO tb_documentos (codigo, id_aluno, tipo, valido_ate)
            VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 90 DAY))";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sis", $codigo, $id_aluno, $tipo);
    mysqli_stmt_execute($stmt);
    $ult = mysqli_insert_id($con);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "emissao", "tb_documentos", $ult, "Declaração de $tipo, código $codigo");
        header("location: declaracao_ver.php?id=$ult");
    }
    else{
        $_SESSION['erro'] = "Não foi possível emitir a declaração.";
        header("location: declaracao_lista.php");
    }
?>
