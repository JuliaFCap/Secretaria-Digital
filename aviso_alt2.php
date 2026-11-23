<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_POST['hidid'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $titulo_aviso = limpa($_POST['txttitulo'] ?? "");
    $texto = limpa($_POST['txttexto'] ?? "");
    $publicado = isset($_POST['ckpublicado']) ? 1 : 0;
    $ate = $_POST['dtate'] ?? "";
    $ate = (preg_match("/^\d{4}-\d{2}-\d{2}$/", $ate) && strtotime($ate)) ? $ate : null;
    if($titulo_aviso == "" || $texto == ""){
        $_SESSION['erro'] = "Preencha o título e o texto.";
        header("location: aviso_alt.php?id=$id");
        exit;
    }
    // imagem atual
    $sql = "SELECT imagem FROM tb_avisos WHERE id_aviso = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $atual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if($atual == null){
        die("Aviso não encontrado");
    }
    $pasta = "uploads/avisos/";
    $imagem = $atual['imagem'];
    $nova = envia_imagem("uplfoto", $pasta);
    if(isset($_SESSION['erro'])){
        header("location: aviso_alt.php?id=$id");
        exit;
    }
    if($nova != ""){
        // trocou a imagem: apaga a antiga do servidor
        if($imagem != "" && file_exists($pasta . $imagem)){
            unlink($pasta . $imagem);
        }
        $imagem = $nova;
    }
    else if(isset($_POST['ckremover']) && $imagem != ""){
        // remoção da imagem
        if(file_exists($pasta . $imagem)){
            unlink($pasta . $imagem);
        }
        $imagem = null;
    }
    $sql = "UPDATE tb_avisos SET titulo = ?, texto = ?, imagem = ?, publicado = ?, exibir_ate = ? WHERE id_aviso = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssisi", $titulo_aviso, $texto, $imagem, $publicado, $ate, $id);
    mysqli_stmt_execute($stmt);
    registra_auditoria($con, "alteracao", "tb_avisos", $id, $titulo_aviso);
    $_SESSION['msg'] = "Aviso $id alterado com sucesso!";
    header("location: aviso_lista.php");
?>
