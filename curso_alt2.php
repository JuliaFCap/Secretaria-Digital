<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $nome = limpa($_POST['txtnome'] ?? "");
    $sigla = strtoupper(limpa($_POST['txtsigla'] ?? ""));
    $ativo = isset($_POST['ckativo']) ? 1 : 0;
    if($nome == "" || $sigla == ""){
        $_SESSION['erro'] = "Preencha o nome e a sigla.";
        header("location: curso_alt.php?id=$id");
        exit;
    }
    $sql = "UPDATE tb_cursos SET nome = ?, sigla = ?, ativo = ? WHERE id_curso = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $nome, $sigla, $ativo, $id);
    if(mysqli_stmt_execute($stmt)){
        registra_auditoria($con, "alteracao", "tb_cursos", $id, "Curso $sigla");
        $_SESSION['msg'] = "Curso $id alterado com sucesso!";
        header("location: curso_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível salvar. Já existe um curso com essa sigla?";
        header("location: curso_alt.php?id=$id");
    }
?>
