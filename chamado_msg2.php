<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno", "secretaria", "coordenacao"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $chamado = busca_chamado($con, $id);
    if(!pode_ver_chamado($chamado)){
        $_SESSION['erro'] = "Chamado não encontrado ou sem permissão de acesso.";
        header("location: chamado_lista.php");
        exit;
    }
    if(in_array($chamado['status'], array("deferido", "indeferido", "concluido"))){
        $_SESSION['erro'] = "Este chamado já foi finalizado.";
        header("location: chamado_ver.php?id=$id");
        exit;
    }
    $mensagem = limpa($_POST['txtmensagem'] ?? "");
    // só a equipe pode marcar como nota interna
    $interna = ($_SESSION['nivel'] != "aluno" && isset($_POST['ckinterna'])) ? 1 : 0;
    if($mensagem == ""){
        $_SESSION['erro'] = "Escreva uma mensagem.";
        header("location: chamado_ver.php?id=$id");
        exit;
    }
    $anexo = envia_anexo("uplanexo", "uploads/anexos/");
    if(isset($_SESSION['erro'])){
        header("location: chamado_ver.php?id=$id");
        exit;
    }
    $sql = "INSERT INTO tb_mensagens (id_chamado, id_usuario, mensagem, interna) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "iisi", $id, $_SESSION['id_usuario'], $mensagem, $interna);
    mysqli_stmt_execute($stmt);
    if($anexo != null){
        grava_anexo($con, $id, $anexo);
    }
    if($_SESSION['nivel'] == "aluno"){
        // aluno respondeu: se estava aguardando ele, volta para análise
        if($chamado['status'] == "aguardando_aluno"){
            muda_status($con, $chamado, "em_analise", "Aluno respondeu");
        }
        if($chamado['id_responsavel']){
            notifica($con, $chamado['id_responsavel'], $id, "O aluno respondeu no chamado #$id.");
        }
    }
    else if($interna == 0){
        notifica($con, $chamado['id_aluno'], $id, "Nova resposta no seu chamado #$id.");
    }
    $_SESSION['msg'] = $interna ? "Nota interna registrada." : "Mensagem enviada.";
    header("location: chamado_ver.php?id=$id");
?>
