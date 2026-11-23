<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_respostas WHERE id_resposta = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Resposta não encontrada");
    }
    $resp = $dados[0];
    $titulo = "Editar resposta pronta";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar resposta pronta</h1>
            <form action="resposta_alt2.php" method="post">
                <div class="mb-3">
                    <label for="txttitulo" class="form-label">Título</label>
                    <input type="text" class="form-control" id="txttitulo" name="txttitulo" required maxlength="100" value="<?php echo seguro($resp['titulo']); ?>">
                </div>
                <div class="mb-3">
                    <label for="txttexto" class="form-label">Texto da resposta</label>
                    <textarea class="form-control" id="txttexto" name="txttexto" rows="5" required><?php echo seguro($resp['texto']); ?></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ckativo" name="ckativo" value="1" <?php if($resp['ativo']){ echo "checked"; } ?>>
                    <label class="form-check-label" for="ckativo">Ativa (aparece na hora de responder)</label>
                </div>
                <input type="hidden" name="hidid" value="<?php echo $resp['id_resposta']; ?>">
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="resposta_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
