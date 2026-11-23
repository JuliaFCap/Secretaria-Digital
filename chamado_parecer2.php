<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["coordenacao"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $chamado = busca_chamado($con, $id);
    // coordenação só dá parecer em chamado do próprio curso
    if(!pode_ver_chamado($chamado)){
        $_SESSION['erro'] = "Chamado não encontrado ou de outro curso.";
        header("location: painel.php");
        exit;
    }
    if($chamado['status'] != "aguardando_coordenacao"){
        $_SESSION['erro'] = "Este chamado não está aguardando parecer.";
        header("location: chamado_ver.php?id=$id");
        exit;
    }
    $decisao = $_POST['rdparecer'] ?? "";
    $texto = limpa($_POST['txtparecer'] ?? "");
    if(!in_array($decisao, array("deferido", "indeferido", "devolver")) || $texto == ""){
        $_SESSION['erro'] = "Escolha a decisão e escreva a justificativa.";
        header("location: chamado_ver.php?id=$id");
        exit;
    }
    if($decisao == "devolver"){
        // volta para a secretaria com uma nota interna explicando
        $sql = "INSERT INTO tb_mensagens (id_chamado, id_usuario, mensagem, interna) VALUES (?, ?, ?, 1)";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $id, $_SESSION['id_usuario'], $texto);
        mysqli_stmt_execute($stmt);
        muda_status($con, $chamado, "em_analise", "Devolvido pela coordenação");
        if($chamado['id_responsavel']){
            notifica($con, $chamado['id_responsavel'], $id, "A coordenação devolveu o chamado #$id.");
        }
        $_SESSION['msg'] = "Chamado #$id devolvido para a secretaria.";
    }
    else{
        $sql = "UPDATE tb_chamados SET parecer = ?, id_parecerista = ?, parecer_em = NOW() WHERE id_chamado = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $texto, $_SESSION['id_usuario'], $id);
        mysqli_stmt_execute($stmt);
        muda_status($con, $chamado, $decisao, "Parecer da coordenação");
        if($chamado['id_responsavel']){
            notifica($con, $chamado['id_responsavel'], $id, "A coordenação deu parecer no chamado #$id: " . nome_status($decisao) . ".");
        }
        $_SESSION['msg'] = "Parecer registrado: chamado #$id " . strtolower(nome_status($decisao)) . ".";
    }
    header("location: chamado_ver.php?id=$id");
?>
