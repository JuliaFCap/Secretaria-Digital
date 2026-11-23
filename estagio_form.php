<?php
    if(!isset($estagio)){
        die("Acesso inválido");
    }
    // Campos do formulário de estágio (cadastro e edição)
?>
<div class="row g-3">
    <div class="col-md-8">
        <label for="txtempresa" class="form-label">Empresa / instituição concedente</label>
        <input type="text" class="form-control" id="txtempresa" name="txtempresa" required maxlength="150" value="<?php echo seguro($estagio['empresa'] ?? ""); ?>">
    </div>
    <div class="col-md-4">
        <label for="txtcnpj" class="form-label">CNPJ</label>
        <input type="text" class="form-control" id="txtcnpj" name="txtcnpj" maxlength="20" value="<?php echo seguro($estagio['cnpj'] ?? ""); ?>" placeholder="00.000.000/0000-00">
    </div>
    <div class="col-md-6">
        <label for="txtsupervisor" class="form-label">Supervisor(a) na empresa</label>
        <input type="text" class="form-control" id="txtsupervisor" name="txtsupervisor" required maxlength="120" value="<?php echo seguro($estagio['supervisor'] ?? ""); ?>">
    </div>
    <div class="col-md-6">
        <label for="txtemailsup" class="form-label">E-mail do(a) supervisor(a)</label>
        <input type="email" class="form-control" id="txtemailsup" name="txtemailsup" maxlength="120" value="<?php echo seguro($estagio['email_supervisor'] ?? ""); ?>">
    </div>
    <div class="col-md-6">
        <label for="txtarea" class="form-label">Área de atuação</label>
        <input type="text" class="form-control" id="txtarea" name="txtarea" maxlength="120" value="<?php echo seguro($estagio['area'] ?? ""); ?>" placeholder="Ex.: Desenvolvimento web">
    </div>
    <div class="col-md-6">
        <label for="numhoras" class="form-label">Horas por semana</label>
        <input type="number" class="form-control" id="numhoras" name="numhoras" required min="1" max="40" value="<?php echo seguro($estagio['horas_semana'] ?? ""); ?>">
        <div class="form-text">A Lei do Estágio limita a 30 horas semanais para estudantes do ensino superior.</div>
    </div>
    <div class="col-md-6">
        <label for="dtinicio" class="form-label">Início</label>
        <input type="date" class="form-control" id="dtinicio" name="dtinicio" required value="<?php echo seguro($estagio['data_inicio'] ?? ""); ?>">
    </div>
    <div class="col-md-6">
        <label for="dtfim" class="form-label">Término previsto</label>
        <input type="date" class="form-control" id="dtfim" name="dtfim" required value="<?php echo seguro($estagio['data_fim'] ?? ""); ?>">
    </div>
    <?php if(isset($estagio['id_estagio']) && in_array($_SESSION['nivel'], array("secretaria", "coordenacao"))){ ?>
    <div class="col-md-6">
        <label for="selsituacao" class="form-label">Situação</label>
        <select class="form-select" id="selsituacao" name="selsituacao">
            <?php
                foreach(array("em_andamento", "concluido", "cancelado") as $s){
                    $sel = ($estagio['situacao'] == $s) ? " selected" : "";
                    echo "<option value='$s'$sel>" . nome_situacao_estagio($s) . "</option>";
                }
            ?>
        </select>
    </div>
    <?php } ?>
</div>
