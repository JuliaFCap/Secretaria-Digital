<?php
    if(!isset($tipo)){
        die("Acesso inválido");
    }
    // Campos do formulário de tipo de requerimento (usado no cadastro e na edição)
    // Antes de importar, a página define $tipo (vazio no cadastro)
?>
<div class="mb-3">
    <label for="txtnome" class="form-label">Nome</label>
    <input type="text" class="form-control" id="txtnome" name="txtnome" required maxlength="100" value="<?php echo seguro($tipo['nome'] ?? ""); ?>" placeholder="Ex.: Aproveitamento de estudos">
</div>
<div class="mb-3">
    <label for="txtdescricao" class="form-label">Descrição para o aluno</label>
    <textarea class="form-control" id="txtdescricao" name="txtdescricao" rows="3"><?php echo seguro($tipo['descricao'] ?? ""); ?></textarea>
</div>
<div class="mb-3">
    <label for="txtdocs" class="form-label">Documentos exigidos</label>
    <input type="text" class="form-control" id="txtdocs" name="txtdocs" maxlength="255" value="<?php echo seguro($tipo['documentos_exigidos'] ?? ""); ?>" placeholder="Ex.: histórico da outra instituição e ementas">
</div>
<div class="mb-3">
    <label for="numprazo" class="form-label">Prazo de atendimento (dias)</label>
    <input type="number" class="form-control" id="numprazo" name="numprazo" min="1" max="120" required value="<?php echo (int)($tipo['prazo_dias'] ?? 5); ?>">
</div>
<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" id="ckcoord" name="ckcoord" value="1" <?php if(!empty($tipo['precisa_coordenacao'])){ echo "checked"; } ?>>
    <label class="form-check-label" for="ckcoord">Precisa de parecer da coordenação</label>
</div>
<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" id="ckanexo" name="ckanexo" value="1" <?php if(!empty($tipo['exige_anexo'])){ echo "checked"; } ?>>
    <label class="form-check-label" for="ckanexo">Exige anexo na abertura</label>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="ckativo" name="ckativo" value="1" <?php if(!isset($tipo['ativo']) || $tipo['ativo']){ echo "checked"; } ?>>
    <label class="form-check-label" for="ckativo">Ativo (aparece para os alunos)</label>
</div>
