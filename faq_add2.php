<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $pergunta = limpa($_POST['txtpergunta'] ?? "");
    $resposta = limpa($_POST['txtresposta'] ?? "");
    $categoria = limpa($_POST['txtcategoria'] ?? "");
    $chaves = limpa($_POST['txtchaves'] ?? "");
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($pergunta == "" || $resposta == ""){
        $_SESSION['erro'] = "Preencha a pergunta e a resposta.";
        header("location: faq_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_faq (pergunta, resposta, categoria, palavras_chave, ativo, id_autor)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssssii", $pergunta, $resposta, $categoria, $chaves, $ativo, $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    $ult = mysqli_insert_id($con);
    if($qtd == 1){
        registra_auditoria($con, "cadastro", "tb_faq", $ult, "Pergunta da FAQ");
        $_SESSION['msg'] = "Pergunta $ult incluída com sucesso!";
        header("location: faq_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível cadastrar a pergunta.";
        header("location: faq_add.php");
    }
?>
