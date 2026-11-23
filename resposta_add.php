<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $titulo = "Nova resposta pronta";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Nova resposta pronta</h1>
            <form action="resposta_add2.php" method="post">
                <div class="mb-3">
                    <label for="txttitulo" class="form-label">Título</label>
                    <input type="text" class="form-control" id="txttitulo" name="txttitulo" required maxlength="100" placeholder="Ex.: Documento ilegível">
                </div>
                <div class="mb-3">
                    <label for="txttexto" class="form-label">Texto da resposta</label>
                    <textarea class="form-control" id="txttexto" name="txttexto" rows="5" required></textarea>
                </div>
                <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                <a href="resposta_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
