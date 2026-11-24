<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $titulo = "Trocar senha";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="cartao">
            <h1 class="h3 mb-3">Trocar senha</h1>
            <form action="senha_alt2.php" method="post">
                <div class="mb-3">
                    <label for="txtatual" class="form-label">Senha atual</label>
                    <input type="password" class="form-control" id="txtatual" name="txtatual" required>
                </div>
                <div class="mb-3">
                    <label for="txtnova" class="form-label">Nova senha</label>
                    <input type="password" class="form-control" id="txtnova" name="txtnova" required minlength="6" maxlength="72">
                    <div class="form-text">Mínimo de 6 caracteres e máximo de 72 bytes.</div>
                </div>
                <div class="mb-3">
                    <label for="txtconfirma" class="form-label">Confirme a nova senha</label>
                    <input type="password" class="form-control" id="txtconfirma" name="txtconfirma" required minlength="6" maxlength="72">
                </div>
                <input type="submit" name="btn" value="Salvar nova senha" class="btn btn-principal">
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
