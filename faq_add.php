<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $faq = array();
    $titulo = "Nova pergunta";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Nova pergunta da FAQ</h1>
            <form action="faq_add2.php" method="post">
                <?php require("faq_form.php"); ?>
                <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                <a href="faq_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
