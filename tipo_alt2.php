<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $nome = limpa($_POST['txtnome'] ?? "");
    $descricao = limpa($_POST['txtdescricao'] ?? "");
    $docs = limpa($_POST['txtdocs'] ?? "");
    $prazo = (int)($_POST['numprazo'] ?? 0);
    $coord = isset($_POST['ckcoord']) ? 1 : 0;
    $anexo = isset($_POST['ckanexo']) ? 1 : 0;
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($nome == "" || $prazo < 1){
        $_SESSION['erro'] = "Preencha o nome e um prazo de pelo menos 1 dia.";
        header("location: tipo_alt.php?id=$id");
        exit;
    }
    $sql = "UPDATE tb_tipos
            SET nome = ?, descricao = ?, documentos_exigidos = ?, prazo_dias = ?,
                precisa_coordenacao = ?, exige_anexo = ?, ativo = ?
            WHERE id_tipo = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssiiiii", $nome, $descricao, $docs, $prazo, $coord, $anexo, $ativo, $id);
    if(mysqli_stmt_execute($stmt)){
        registra_auditoria($con, "alteracao", "tb_tipos", $id, $nome);
        $_SESSION['msg'] = "Tipo $id alterado com sucesso!";
        header("location: tipo_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível salvar. Já existe um tipo com esse nome?";
        header("location: tipo_alt.php?id=$id");
    }
?>
