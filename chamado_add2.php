<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno"]);
    $tipo = (int)($_POST['rdtipo'] ?? 0);
    $assunto = limpa($_POST['txtassunto'] ?? "");
    $descricao = limpa($_POST['txtdescricao'] ?? "");
    if($tipo < 1 || $assunto == "" || $descricao == ""){
        $_SESSION['erro'] = "Escolha o tipo e preencha assunto e descrição.";
        header("location: chamado_add.php");
        exit;
    }
    // confere o tipo no banco (regra de negócio validada no PHP, não só no navegador)
    $sql = "SELECT prazo_dias, exige_anexo FROM tb_tipos WHERE id_tipo = ? AND ativo = 1 LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $tipo);
    mysqli_stmt_execute($stmt);
    $dados_tipo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($dados_tipo == null){
        $_SESSION['erro'] = "Tipo de requerimento inválido.";
        header("location: chamado_add.php");
        exit;
    }
    $anexo = envia_anexo("uplanexo", "uploads/anexos/");
    if(isset($_SESSION['erro'])){
        header("location: chamado_add.php");
        exit;
    }
    if($dados_tipo['exige_anexo'] && $anexo == null){
        $_SESSION['erro'] = "Este tipo de requerimento exige um anexo.";
        header("location: chamado_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_chamados (id_aluno, id_tipo, assunto, descricao, prazo)
            VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY))";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "iissi", $_SESSION['id_usuario'], $tipo, $assunto, $descricao, $dados_tipo['prazo_dias']);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    $ult = mysqli_insert_id($con);
    if($qtd == 1){
        if($anexo != null){
            grava_anexo($con, $ult, $anexo);
        }
        registra_historico($con, $ult, "Chamado aberto pelo aluno", null, "aberto", null);
        notifica($con, $_SESSION['id_usuario'], $ult, "Recebemos sua solicitação. O número do chamado é #$ult.");
        // avisa a equipe da secretaria
        $sql = "SELECT id_usuario FROM tb_usuarios WHERE nivel = 'secretaria' AND ativo = 1";
        $res = mysqli_query($con, $sql);
        foreach(mysqli_fetch_all($res, MYSQLI_ASSOC) as $sec){
            notifica($con, $sec['id_usuario'], $ult, "Novo chamado #$ult na fila.");
        }
        $_SESSION['msg'] = "Solicitação enviada! O número do seu chamado é #$ult.";
        header("location: chamado_ver.php?id=$ult");
    }
    else{
        $_SESSION['erro'] = "Não foi possível abrir o chamado.";
        header("location: chamado_add.php");
    }
?>
