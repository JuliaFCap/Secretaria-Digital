<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $nome = limpa($_POST['txtnome'] ?? "");
    $descricao = limpa($_POST['txtdescricao'] ?? "");
    $ordem = (int)($_POST['numordem'] ?? 1);
    $prazo = ($_POST['numprazo'] ?? "") === "" ? null : (int)$_POST['numprazo'];
    $obrigatoria = isset($_POST['ckobrigatoria']) ? 1 : 0;
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($nome == "" || $ordem < 1){
        $_SESSION['erro'] = "Preencha o nome e a ordem.";
        header("location: etapa_add.php");
        exit;
    }
    $pasta = "uploads/modelos/";
    $modelo = envia_modelo("uplmodelo", $pasta);
    if(isset($_SESSION['erro'])){
        header("location: etapa_add.php");
        exit;
    }
    $arquivo = $modelo['arquivo'] ?? null;
    $arquivo_nome = $modelo['original'] ?? null;
    $sql = "INSERT INTO tb_etapas (nome, descricao, ordem, prazo_dias, obrigatoria, modelo, modelo_nome, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssiiissi", $nome, $descricao, $ordem, $prazo, $obrigatoria, $arquivo, $arquivo_nome, $ativo);
    mysqli_stmt_execute($stmt);
    $ult = mysqli_insert_id($con);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        registra_auditoria($con, "cadastro", "tb_etapas", $ult, $nome);
        $_SESSION['msg'] = "Etapa $ult incluída com sucesso!";
        header("location: etapa_lista.php");
    }
    else{
        if($arquivo){
            unlink($pasta . $arquivo);
        }
        $_SESSION['erro'] = "Não foi possível cadastrar a etapa.";
        header("location: etapa_add.php");
    }
?>
