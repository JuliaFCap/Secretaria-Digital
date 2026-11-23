<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $titulo_resp = limpa($_POST['txttitulo'] ?? "");
    $texto = limpa($_POST['txttexto'] ?? "");
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($titulo_resp == "" || $texto == ""){
        $_SESSION['erro'] = "Preencha o título e o texto.";
        header("location: resposta_alt.php?id=$id");
        exit;
    }
    $sql = "UPDATE tb_respostas SET titulo = ?, texto = ?, ativo = ? WHERE id_resposta = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $titulo_resp, $texto, $ativo, $id);
    mysqli_stmt_execute($stmt);
    registra_auditoria($con, "alteracao", "tb_respostas", $id, $titulo_resp);
    $_SESSION['msg'] = "Resposta $id alterada com sucesso!";
    header("location: resposta_lista.php");
?>
