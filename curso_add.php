<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $titulo = "Novo curso";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="cartao">
            <h1 class="h3 mb-3">Novo curso</h1>
            <form action="curso_add2.php" method="post">
                <div class="mb-3">
                    <label for="txtnome" class="form-label">Nome do curso</label>
                    <input type="text" class="form-control" id="txtnome" name="txtnome" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label for="txtsigla" class="form-label">Sigla</label>
                    <input type="text" class="form-control" id="txtsigla" name="txtsigla" required maxlength="10" placeholder="Ex.: ADS">
                </div>
                <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                <a href="curso_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
