<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $titulo_resp = limpa($_POST['txttitulo'] ?? "");
    $texto = limpa($_POST['txttexto'] ?? "");
    if($titulo_resp == "" || $texto == ""){
        $_SESSION['erro'] = "Preencha o título e o texto.";
        header("location: resposta_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_respostas (titulo, texto, id_autor) VALUES (?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $titulo_resp, $texto, $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    $ult = mysqli_insert_id($con);
    if($qtd == 1){
        registra_auditoria($con, "cadastro", "tb_respostas", $ult, $titulo_resp);
        $_SESSION['msg'] = "Resposta $ult incluída com sucesso!";
        header("location: resposta_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível cadastrar a resposta.";
        header("location: resposta_add.php");
    }
?>
