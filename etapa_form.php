<?php
    if(!isset($etapa)){
        die("Acesso inválido");
    }
    // Campos do formulário de etapa do estágio (cadastro e edição)
?>
<div class="row g-3 mb-3">
    <div class="col-md-9">
        <label for="txtnome" class="form-label">Nome da etapa / documento</label>
        <input type="text" class="form-control" id="txtnome" name="txtnome" required maxlength="120" value="<?php echo seguro($etapa['nome'] ?? ""); ?>" placeholder="Ex.: Termo de Compromisso de Estágio">
    </div>
    <div class="col-md-3">
        <label for="numordem" class="form-label">Ordem</label>
        <input type="number" class="form-control" id="numordem" name="numordem" min="1" max="99" required value="<?php echo (int)($etapa['ordem'] ?? 1); ?>">
    </div>
</div>
<div class="mb-3">
    <label for="txtdescricao" class="form-label">Como fazer (aparece no guia)</label>
    <textarea class="form-control" id="txtdescricao" name="txtdescricao" rows="4"><?php echo seguro($etapa['descricao'] ?? ""); ?></textarea>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="numprazo" class="form-label">Prazo (dias após o início do estágio)</label>
        <input type="number" class="form-control" id="numprazo" name="numprazo" min="0" max="730" value="<?php echo seguro($etapa['prazo_dias'] ?? ""); ?>">
        <div class="form-text">Deixe vazio se não houver prazo.</div>
    </div>
    <div class="col-md-6 d-flex flex-column justify-content-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="ckobrigatoria" name="ckobrigatoria" value="1" <?php if(!isset($etapa['obrigatoria']) || $etapa['obrigatoria']){ echo "checked"; } ?>>
            <label class="form-check-label" for="ckobrigatoria">Obrigatória para concluir o estágio</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="ckativo" name="ckativo" value="1" <?php if(!isset($etapa['ativo']) || $etapa['ativo']){ echo "checked"; } ?>>
            <label class="form-check-label" for="ckativo">Ativa</label>
        </div>
    </div>
</div>
<div class="mb-3">
    <?php if(!empty($etapa['modelo'])){ ?>
        <p class="mb-1"><strong>Modelo atual:</strong> <a href="uploads/modelos/<?php echo seguro($etapa['modelo']); ?>" download><?php echo seguro($etapa['modelo_nome']); ?></a></p>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="ckremover" name="ckremover" value="1">
            <label class="form-check-label" for="ckremover">Remover o modelo</label>
        </div>
    <?php } ?>
    <label for="uplmodelo" class="form-label"><?php echo !empty($etapa['modelo']) ? "Trocar modelo" : "Modelo para o aluno baixar (opcional)"; ?></label>
    <input type="file" class="form-control" id="uplmodelo" name="uplmodelo" accept=".pdf,.doc,.docx">
    <div class="form-text">PDF, DOC ou DOCX de até 2 MB.</div>
</div>
