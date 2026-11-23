<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_faq WHERE id_faq = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Pergunta não encontrada");
    }
    $faq = $dados[0];
    $titulo = "Editar pergunta";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar pergunta da FAQ</h1>
            <form action="faq_alt2.php" method="post">
                <?php require("faq_form.php"); ?>
                <input type="hidden" name="hidid" value="<?php echo $faq['id_faq']; ?>">
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="faq_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
