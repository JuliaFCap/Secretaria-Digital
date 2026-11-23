<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $nome = limpa($_POST['txtnome'] ?? "");
    $descricao = limpa($_POST['txtdescricao'] ?? "");
    $docs = limpa($_POST['txtdocs'] ?? "");
    $prazo = (int)($_POST['numprazo'] ?? 0);
    $coord = isset($_POST['ckcoord']) ? 1 : 0;
    $anexo = isset($_POST['ckanexo']) ? 1 : 0;
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($nome == "" || $prazo < 1){
        $_SESSION['erro'] = "Preencha o nome e um prazo de pelo menos 1 dia.";
        header("location: tipo_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_tipos (nome, descricao, documentos_exigidos, prazo_dias, precisa_coordenacao, exige_anexo, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssiiii", $nome, $descricao, $docs, $prazo, $coord, $anexo, $ativo);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    $ult = mysqli_insert_id($con);
    if($qtd == 1){
        registra_auditoria($con, "cadastro", "tb_tipos", $ult, $nome);
        $_SESSION['msg'] = "Tipo $ult incluído com sucesso!";
        header("location: tipo_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível cadastrar. Já existe um tipo com esse nome?";
        header("location: tipo_add.php");
    }
?>
