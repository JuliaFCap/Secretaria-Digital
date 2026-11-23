<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT modelo FROM tb_etapas WHERE id_etapa = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $etapa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($etapa == null){
        die("Etapa não encontrada");
    }
    $sql = "DELETE FROM tb_etapas WHERE id_etapa = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        if($etapa['modelo'] && file_exists("uploads/modelos/" . basename($etapa['modelo']))){
            unlink("uploads/modelos/" . basename($etapa['modelo']));
        }
        registra_auditoria($con, "exclusao", "tb_etapas", $id, "Etapa do estágio");
        $_SESSION['msg'] = "Etapa $id excluída com sucesso!";
    }
    else{
        // etapa que já recebeu documentos não pode ser apagada: fica inativa
        $sql = "UPDATE tb_etapas SET ativo = 0 WHERE id_etapa = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        registra_auditoria($con, "desativacao", "tb_etapas", $id, "Etapa com entregas foi desativada");
        $_SESSION['erro'] = "A etapa $id já recebeu documentos e não pode ser apagada. Ela foi marcada como inativa.";
    }
    header("location: etapa_lista.php");
?>
