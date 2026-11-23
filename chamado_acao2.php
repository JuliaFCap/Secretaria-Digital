<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $acao = $_POST['hidacao'] ?? "";
    // volta para a página de onde veio (detalhe ou kanban)
    $voltar = (($_POST['hidvoltar'] ?? "") == "kanban") ? "kanban.php" : "chamado_ver.php?id=$id";
    $chamado = busca_chamado($con, $id);
    if($chamado == null){
        die("Chamado não encontrado");
    }
    if($acao == "assumir"){
        $sql = "UPDATE tb_chamados SET id_responsavel = ? WHERE id_chamado = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['id_usuario'], $id);
        mysqli_stmt_execute($stmt);
        registra_historico($con, $id, "Assumiu o chamado", null, null, null);
        if($chamado['status'] == "aberto"){
            muda_status($con, $chamado, "em_analise", null);
        }
        $_SESSION['msg'] = "Você assumiu o chamado #$id.";
    }
    else if($acao == "status"){
        $novo = $_POST['selstatus'] ?? "";
        $obs = limpa($_POST['txtobs'] ?? "");
        // deferir/indeferir é exclusivo da coordenação
        if(!in_array($novo, array("em_analise", "aguardando_aluno", "aguardando_coordenacao", "concluido"))){
            die("Status inválido");
        }
        if($novo == $chamado['status']){
            $_SESSION['erro'] = "O chamado já está com esse status.";
        }
        else{
            muda_status($con, $chamado, $novo, $obs != "" ? $obs : null);
            // quem mexe no chamado sem responsável passa a ser o responsável
            if($chamado['id_responsavel'] == null){
                $sql = "UPDATE tb_chamados SET id_responsavel = ? WHERE id_chamado = ?";
                $stmt = mysqli_stmt_init($con);
                mysqli_stmt_prepare($stmt, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $_SESSION['id_usuario'], $id);
                mysqli_stmt_execute($stmt);
            }
            $_SESSION['msg'] = "Chamado #$id movido para: " . nome_status($novo) . ".";
        }
    }
    else if($acao == "lembrete"){
        if($chamado['status'] != "aguardando_aluno"){
            $_SESSION['erro'] = "Só é possível reenviar aviso de chamado aguardando o aluno.";
        }
        else{
            notifica($con, $chamado['id_aluno'], $id, "Lembrete: o chamado #$id está aguardando sua resposta.");
            $sql = "UPDATE tb_chamados SET ultimo_lembrete = NOW() WHERE id_chamado = ?";
            $stmt = mysqli_stmt_init($con);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            registra_historico($con, $id, "Aviso reenviado ao aluno", null, null, null);
            $_SESSION['msg'] = "Aviso reenviado ao aluno do chamado #$id.";
        }
    }
    else{
        die("Ação inválida");
    }
    header("location: $voltar");
?>
