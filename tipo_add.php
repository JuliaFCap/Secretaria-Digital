<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $tipo = array();
    $titulo = "Novo tipo de requerimento";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Novo tipo de requerimento</h1>
            <form action="tipo_add2.php" method="post">
                <?php require("tipo_form.php"); ?>
                <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                <a href="tipo_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
