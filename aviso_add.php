<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $titulo = "Novo aviso";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Novo aviso</h1>
            <!-- enctype específico para envio de arquivos pra $_FILES -->
            <form action="aviso_add2.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="txttitulo" class="form-label">Título</label>
                    <input type="text" class="form-control" id="txttitulo" name="txttitulo" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label for="txttexto" class="form-label">Texto</label>
                    <textarea class="form-control" id="txttexto" name="txttexto" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="uplfoto" class="form-label">Imagem (opcional)</label>
                    <input type="file" class="form-control" id="uplfoto" name="uplfoto" accept=".png,.jpg,.jpeg">
                    <div class="form-text">PNG ou JPG de até 2 MB.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="dtate" class="form-label">Exibir até (opcional)</label>
                        <input type="date" class="form-control" id="dtate" name="dtate">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ckpublicado" name="ckpublicado" value="1" checked>
                            <label class="form-check-label" for="ckpublicado">Publicar agora</label>
                        </div>
                    </div>
                </div>
                <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                <a href="aviso_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
