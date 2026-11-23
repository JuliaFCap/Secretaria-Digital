<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $pergunta = limpa($_POST['txtpergunta'] ?? "");
    $resposta = limpa($_POST['txtresposta'] ?? "");
    $categoria = limpa($_POST['txtcategoria'] ?? "");
    $chaves = limpa($_POST['txtchaves'] ?? "");
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($pergunta == "" || $resposta == ""){
        $_SESSION['erro'] = "Preencha a pergunta e a resposta.";
        header("location: faq_alt.php?id=$id");
        exit;
    }
    $sql = "UPDATE tb_faq SET pergunta = ?, resposta = ?, categoria = ?, palavras_chave = ?, ativo = ?
            WHERE id_faq = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssssii", $pergunta, $resposta, $categoria, $chaves, $ativo, $id);
    mysqli_stmt_execute($stmt);
    registra_auditoria($con, "alteracao", "tb_faq", $id, "Pergunta da FAQ");
    $_SESSION['msg'] = "Pergunta $id alterada com sucesso!";
    header("location: faq_lista.php");
?>
