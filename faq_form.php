<?php
    if(!isset($faq)){
        die("Acesso inválido");
    }
    // Campos do formulário da FAQ (cadastro e edição)
?>
<div class="mb-3">
    <label for="txtpergunta" class="form-label">Pergunta</label>
    <input type="text" class="form-control" id="txtpergunta" name="txtpergunta" required maxlength="255" value="<?php echo seguro($faq['pergunta'] ?? ""); ?>">
</div>
<div class="mb-3">
    <label for="txtresposta" class="form-label">Resposta</label>
    <textarea class="form-control" id="txtresposta" name="txtresposta" rows="6" required><?php echo seguro($faq['resposta'] ?? ""); ?></textarea>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-5">
        <label for="txtcategoria" class="form-label">Categoria</label>
        <input type="text" class="form-control" id="txtcategoria" name="txtcategoria" maxlength="60" value="<?php echo seguro($faq['categoria'] ?? ""); ?>" placeholder="Ex.: Matrícula, Estágio">
    </div>
    <div class="col-md-7">
        <label for="txtchaves" class="form-label">Palavras-chave para a busca</label>
        <input type="text" class="form-control" id="txtchaves" name="txtchaves" maxlength="255" value="<?php echo seguro($faq['palavras_chave'] ?? ""); ?>" placeholder="Ex.: trancar, trancamento, pausar curso">
    </div>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="ckativo" name="ckativo" value="1" <?php if(!isset($faq['ativo']) || $faq['ativo']){ echo "checked"; } ?>>
    <label class="form-check-label" for="ckativo">Publicada (aparece para todos)</label>
</div>
