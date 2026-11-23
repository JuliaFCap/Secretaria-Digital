<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_avisos WHERE id_aviso = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Aviso não encontrado");
    }
    $aviso = $dados[0];
    $titulo = "Editar aviso";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar aviso</h1>
            <form action="aviso_alt2.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="txttitulo" class="form-label">Título</label>
                    <input type="text" class="form-control" id="txttitulo" name="txttitulo" required maxlength="150" value="<?php echo seguro($aviso['titulo']); ?>">
                </div>
                <div class="mb-3">
                    <label for="txttexto" class="form-label">Texto</label>
                    <textarea class="form-control" id="txttexto" name="txttexto" rows="5" required><?php echo seguro($aviso['texto']); ?></textarea>
                </div>
                <div class="mb-3">
                    <span class="form-label d-block">Imagem atual</span>
                    <?php if($aviso['imagem'] != ""){ ?>
                        <img src="uploads/avisos/<?php echo seguro($aviso['imagem']); ?>" alt="Imagem atual do aviso" style="max-width: 300px" class="rounded border d-block mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ckremover" name="ckremover" value="1">
                            <label class="form-check-label" for="ckremover">Remover a imagem</label>
                        </div>
                    <?php } else { ?>
                        <p class="text-secondary small">Sem imagem.</p>
                    <?php } ?>
                </div>
                <div class="mb-3">
                    <label for="uplfoto" class="form-label"><?php echo $aviso['imagem'] != "" ? "Trocar imagem" : "Enviar imagem"; ?></label>
                    <input type="file" class="form-control" id="uplfoto" name="uplfoto" accept=".png,.jpg,.jpeg">
                    <div class="form-text">PNG ou JPG de até 2 MB. A imagem antiga é apagada do servidor.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="dtate" class="form-label">Exibir até (opcional)</label>
                        <input type="date" class="form-control" id="dtate" name="dtate" value="<?php echo seguro($aviso['exibir_ate']); ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ckpublicado" name="ckpublicado" value="1" <?php if($aviso['publicado']){ echo "checked"; } ?>>
                            <label class="form-check-label" for="ckpublicado">Publicado</label>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="hidid" value="<?php echo $aviso['id_aviso']; ?>">
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="aviso_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
