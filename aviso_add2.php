<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $titulo_aviso = limpa($_POST['txttitulo'] ?? "");
    $texto = limpa($_POST['txttexto'] ?? "");
    $publicado = isset($_POST['ckpublicado']) ? 1 : 0;
    $ate = $_POST['dtate'] ?? "";
    // aceita só data válida no formato AAAA-MM-DD
    $ate = (preg_match("/^\d{4}-\d{2}-\d{2}$/", $ate) && strtotime($ate)) ? $ate : null;
    if($titulo_aviso == "" || $texto == ""){
        $_SESSION['erro'] = "Preencha o título e o texto.";
        header("location: aviso_add.php");
        exit;
    }
    $pasta = "uploads/avisos/";//caminho da pasta no servidor
    $imagem = envia_imagem("uplfoto", $pasta);
    if(isset($_SESSION['erro'])){
        header("location: aviso_add.php");
        exit;
    }
    $sql = "INSERT INTO tb_avisos (titulo, texto, imagem, publicado, exibir_ate, id_autor)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssisi", $titulo_aviso, $texto, $imagem, $publicado, $ate, $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    $qtd = mysqli_stmt_affected_rows($stmt);
    $ult = mysqli_insert_id($con);
    if($qtd == 1){
        registra_auditoria($con, "cadastro", "tb_avisos", $ult, $titulo_aviso . ($imagem != "" ? " (com imagem)" : ""));
        $_SESSION['msg'] = "Aviso $ult incluído com sucesso!";
        header("location: aviso_lista.php");
    }
    else{
        if($imagem != ""){
            unlink($pasta . $imagem);//não deixa imagem órfã
        }
        $_SESSION['erro'] = "Não foi possível cadastrar o aviso.";
        header("location: aviso_add.php");
    }
?>
