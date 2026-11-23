<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    $id_etapa = (int)($_POST['hidetapa'] ?? 0);
    if($id < 1 || $id_etapa < 1){
        die("ID inválido");
    }
    $estagio = busca_estagio($con, $id);
    if(!pode_ver_estagio($estagio)){
        $_SESSION['erro'] = "Estágio não encontrado.";
        header("location: estagio_lista.php");
        exit;
    }
    if($estagio['situacao'] != "em_andamento"){
        $_SESSION['erro'] = "Este estágio já foi encerrado.";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    // etapa precisa existir e estar ativa
    $sql = "SELECT nome FROM tb_etapas WHERE id_etapa = ? AND ativo = 1 LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_etapa);
    mysqli_stmt_execute($stmt);
    $etapa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($etapa == null){
        die("Etapa inválida");
    }
    // não deixa reenviar o que já foi aprovado ou está em análise
    $sql = "SELECT status FROM tb_entregas WHERE id_estagio = ? AND id_etapa = ? ORDER BY id_entrega DESC LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_etapa);
    mysqli_stmt_execute($stmt);
    $ultima = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($ultima != null && $ultima['status'] != "correcao"){
        $_SESSION['erro'] = "Este documento já foi enviado e está " . strtolower(nome_status_entrega($ultima['status'])) . ".";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    // mesmo padrão de upload dos anexos: PDF, PNG ou JPG até 2 MB, pasta bloqueada
    $arquivo = envia_anexo("upldoc", "uploads/estagio/");
    if(isset($_SESSION['erro'])){
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    if($arquivo == null){
        $_SESSION['erro'] = "Escolha o arquivo do documento.";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    $sql = "INSERT INTO tb_entregas (id_estagio, id_etapa, arquivo, nome_original, tamanho) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "iissi", $id, $id_etapa, $arquivo['arquivo'], $arquivo['original'], $arquivo['tamanho']);
    mysqli_stmt_execute($stmt);
    $ult = mysqli_insert_id($con);
    registra_auditoria($con, "envio", "tb_entregas", $ult, "Documento de estágio: " . $etapa['nome']);
    // avisa quem avalia: coordenação do curso do aluno
    $sql = "SELECT id_usuario FROM tb_usuarios WHERE nivel = 'coordenacao' AND ativo = 1 AND id_curso = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $estagio['curso_aluno']);
    mysqli_stmt_execute($stmt);
    foreach(mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC) as $coord){
        notifica($con, $coord['id_usuario'], null, $estagio['aluno'] . " enviou o documento \"" . $etapa['nome'] . "\" do estágio #$id.");
    }
    $_SESSION['msg'] = "Documento enviado! Agora é só aguardar a análise.";
    header("location: estagio_ver.php?id=$id");
?>
