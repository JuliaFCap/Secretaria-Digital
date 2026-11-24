<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $sql = "SELECT nome, login, email, telefone, aviso_whatsapp FROM tb_usuarios WHERE id_usuario = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    $eu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $integracao = busca_config($con, "whatsapp_ativo") == "1";
    $titulo = "Meus dados";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="cartao">
            <h1 class="h3 mb-3">Meus dados</h1>
            <dl class="row small mb-3">
                <dt class="col-4">Nome</dt><dd class="col-8"><?php echo seguro($eu['nome']); ?></dd>
                <dt class="col-4">Login</dt><dd class="col-8"><?php echo seguro($eu['login']); ?></dd>
                <dt class="col-4">E-mail</dt><dd class="col-8"><?php echo seguro($eu['email']); ?></dd>
            </dl>
            <form action="perfil_alt2.php" method="post">
                <div class="mb-3">
                    <label for="txttelefone" class="form-label">Celular / WhatsApp</label>
                    <input type="text" class="form-control" id="txttelefone" name="txttelefone" required maxlength="20" value="<?php echo seguro($eu['telefone']); ?>" placeholder="(16) 99999-9999">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ckwhats" name="ckwhats" value="1" <?php if($eu['aviso_whatsapp']){ echo "checked"; } ?>>
                    <label class="form-check-label" for="ckwhats">Receber os avisos dos meus chamados por WhatsApp</label>
                </div>
                <?php if(!$integracao){ echo "<div class='alert alert-secondary small'>A integração com o WhatsApp ainda não foi ligada pela administração. Seus avisos continuam aparecendo aqui no sistema.</div>"; } ?>
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="senha_alt.php" class="btn btn-outline-secondary">Trocar senha</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
