<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $etapa = array();
    $titulo = "Nova etapa do estágio";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Nova etapa do estágio</h1>
            <form action="etapa_add2.php" method="post" enctype="multipart/form-data">
                <?php require("etapa_form.php"); ?>
                <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                <a href="etapa_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
