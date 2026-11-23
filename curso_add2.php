<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $nome = limpa($_POST['txtnome'] ?? "");
    $sigla = strtoupper(limpa($_POST['txtsigla'] ?? ""));
    if($nome == "" || $sigla == ""){
        $_SESSION['erro'] = "Preencha o nome e a sigla.";
        header("location: curso_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_cursos (nome, sigla) VALUES (?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $nome, $sigla);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);//conta as alterações
    $ult = mysqli_insert_id($con);//último registro inserido
    if($qtd == 1){
        registra_auditoria($con, "cadastro", "tb_cursos", $ult, "Curso $sigla");
        $_SESSION['msg'] = "Curso $ult incluído com sucesso!";
        header("location: curso_lista.php");
    }
    else{
        $_SESSION['erro'] = "Não foi possível cadastrar. Já existe um curso com essa sigla?";
        header("location: curso_add.php");
    }
?>
