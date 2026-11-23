<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno"]);
    $estagio = array();
    $titulo = "Cadastrar estágio";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="cartao">
            <h1 class="h3 mb-1">Cadastrar estágio</h1>
            <p class="text-secondary">Depois de cadastrar, você envia cada documento pela página do estágio.</p>
            <form action="estagio_add2.php" method="post">
                <?php require("estagio_form.php"); ?>
                <div class="mt-4">
                    <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                    <a href="estagio_lista.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
