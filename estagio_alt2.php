<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno", "secretaria", "coordenacao"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $estagio = busca_estagio($con, $id);
    if(!pode_ver_estagio($estagio)){
        $_SESSION['erro'] = "Estágio não encontrado ou sem permissão de acesso.";
        header("location: estagio_lista.php");
        exit;
    }
    if($_SESSION['nivel'] == "aluno" && $estagio['situacao'] != "em_andamento"){
        die("Estágio encerrado");
    }
    require_once("estagio_valida.php");
    // só secretaria e coordenação mudam a situação; o aluno mantém a atual
    $situacao = $estagio['situacao'];
    if($_SESSION['nivel'] != "aluno" && in_array($_POST['selsituacao'] ?? "", array("em_andamento", "concluido", "cancelado"))){
        $situacao = $_POST['selsituacao'];
    }
    if($erro != ""){
        $_SESSION['erro'] = $erro;
        header("location: estagio_alt.php?id=$id");
        exit;
    }
    $sql = "UPDATE tb_estagios
            SET empresa = ?, cnpj = ?, supervisor = ?, email_supervisor = ?, area = ?, horas_semana = ?,
                data_inicio = ?, data_fim = ?, situacao = ?
            WHERE id_estagio = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssssisssi", $empresa, $cnpj, $supervisor, $email_sup, $area, $horas, $inicio, $fim, $situacao, $id);
    mysqli_stmt_execute($stmt);
    if($situacao != $estagio['situacao']){
        notifica($con, $estagio['id_aluno'], null, "Seu estágio na " . $empresa . " agora está: " . nome_situacao_estagio($situacao) . ".");
    }
    registra_auditoria($con, "alteracao", "tb_estagios", $id, "Estágio em $empresa (" . nome_situacao_estagio($situacao) . ")");
    $_SESSION['msg'] = "Estágio #$id atualizado.";
    header("location: estagio_ver.php?id=$id");
?>
