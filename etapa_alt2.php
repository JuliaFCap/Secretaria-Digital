<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $nome = limpa($_POST['txtnome'] ?? "");
    $descricao = limpa($_POST['txtdescricao'] ?? "");
    $ordem = (int)($_POST['numordem'] ?? 1);
    $prazo = ($_POST['numprazo'] ?? "") === "" ? null : (int)$_POST['numprazo'];
    $obrigatoria = isset($_POST['ckobrigatoria']) ? 1 : 0;
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($nome == "" || $ordem < 1){
        $_SESSION['erro'] = "Preencha o nome e a ordem.";
        header("location: etapa_alt.php?id=$id");
        exit;
    }
    $sql = "SELECT modelo, modelo_nome FROM tb_etapas WHERE id_etapa = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $atual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($atual == null){
        die("Etapa não encontrada");
    }
    $pasta = "uploads/modelos/";
    $arquivo = $atual['modelo'];
    $arquivo_nome = $atual['modelo_nome'];
    $novo = envia_modelo("uplmodelo", $pasta);
    if(isset($_SESSION['erro'])){
        header("location: etapa_alt.php?id=$id");
        exit;
    }
    if($novo != null || isset($_POST['ckremover'])){
        // trocou ou removeu: apaga o modelo antigo do servidor
        if($arquivo && file_exists($pasta . basename($arquivo))){
            unlink($pasta . basename($arquivo));
        }
        $arquivo = $novo['arquivo'] ?? null;
        $arquivo_nome = $novo['original'] ?? null;
    }
    $sql = "UPDATE tb_etapas SET nome = ?, descricao = ?, ordem = ?, prazo_dias = ?, obrigatoria = ?,
                   modelo = ?, modelo_nome = ?, ativo = ?
            WHERE id_etapa = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssiiissii", $nome, $descricao, $ordem, $prazo, $obrigatoria, $arquivo, $arquivo_nome, $ativo, $id);
    mysqli_stmt_execute($stmt);
    registra_auditoria($con, "alteracao", "tb_etapas", $id, $nome);
    $_SESSION['msg'] = "Etapa $id alterada com sucesso!";
    header("location: etapa_lista.php");
?>
