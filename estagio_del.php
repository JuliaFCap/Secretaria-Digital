<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria", "admin"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    // guarda os arquivos antes de apagar o registro (as entregas somem junto, em cascata)
    $sql = "SELECT arquivo FROM tb_entregas WHERE id_estagio = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $arquivos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    $sql = "DELETE FROM tb_estagios WHERE id_estagio = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    if(mysqli_stmt_affected_rows($stmt) == 1){
        foreach($arquivos as $a){
            $caminho = "uploads/estagio/" . basename($a['arquivo']);
            if(file_exists($caminho)){
                unlink($caminho);
            }
        }
        registra_auditoria($con, "exclusao", "tb_estagios", $id, "Estágio e " . count($arquivos) . " documento(s) excluídos");
        $_SESSION['msg'] = "Estágio #$id excluído com sucesso!";
    }
    else{
        $_SESSION['erro'] = "Estágio não encontrado.";
    }
    header("location: estagio_lista.php");
?>
