<?php
    if(!isset($usuario)){
        die("Acesso inválido");
    }
    // Campos do formulário de usuário (cadastro e edição)
    // Antes de importar, a página define $usuario (vazio no cadastro) e $cursos
    $editando = isset($usuario['id_usuario']);
    $nivel_atual = $usuario['nivel'] ?? "aluno";
?>
<div class="row g-3">
    <div class="col-md-8">
        <label for="txtnome" class="form-label">Nome completo</label>
        <input type="text" class="form-control" id="txtnome" name="txtnome" required maxlength="120" value="<?php echo seguro($usuario['nome'] ?? ""); ?>">
    </div>
    <div class="col-md-4">
        <label for="selnivel" class="form-label">Nível de acesso</label>
        <?php if($_SESSION['nivel'] == "admin"){ ?>
            <select class="form-select" id="selnivel" name="selnivel" required>
                <?php
                    foreach(array("aluno", "secretaria", "coordenacao", "admin") as $n){
                        $sel = ($nivel_atual == $n) ? " selected" : "";
                        echo "<option value='$n'$sel>" . nome_nivel($n) . "</option>";
                    }
                ?>
            </select>
        <?php } else { ?>
            <input type="text" class="form-control" id="selnivel" value="Aluno" disabled>
        <?php } ?>
    </div>
    <div class="col-md-6">
        <label for="txtlogin" class="form-label">Login</label>
        <input type="text" class="form-control" id="txtlogin" name="txtlogin" required maxlength="50" pattern="[a-zA-Z0-9._-]+" value="<?php echo seguro($usuario['login'] ?? ""); ?>">
        <div class="form-text">Letras, números, ponto, hífen ou sublinhado. Ao trocar o login, defina uma nova senha.</div>
    </div>
    <div class="col-md-6">
        <label for="txtsenha" class="form-label"><?php echo $editando ? "Nova senha (deixe em branco para manter)" : "Senha inicial"; ?></label>
        <input type="password" class="form-control" id="txtsenha" name="txtsenha" minlength="6" <?php echo $editando ? "" : "required"; ?> autocomplete="new-password">
    </div>
    <div class="col-md-6">
        <label for="txtemail" class="form-label">E-mail</label>
        <input type="email" class="form-control" id="txtemail" name="txtemail" required maxlength="120" value="<?php echo seguro($usuario['email'] ?? ""); ?>">
    </div>
    <div class="col-md-6">
        <label for="txttelefone" class="form-label">Celular / WhatsApp</label>
        <input type="text" class="form-control" id="txttelefone" name="txttelefone" maxlength="20" value="<?php echo seguro($usuario['telefone'] ?? ""); ?>" placeholder="(16) 99999-9999">
    </div>
    <div class="col-md-6">
        <label for="selcurso" class="form-label">Curso</label>
        <select class="form-select" id="selcurso" name="selcurso">
            <option value="0">Sem curso (secretaria/admin)</option>
            <?php
                foreach($cursos as $curso){
                    $sel = (($usuario['id_curso'] ?? 0) == $curso['id_curso']) ? " selected" : "";
                    echo "<option value='" . $curso['id_curso'] . "'$sel>" . seguro($curso['nome']) . "</option>";
                }
            ?>
        </select>
        <div class="form-text">Obrigatório para aluno e coordenação.</div>
    </div>
    <div class="col-md-3">
        <label for="txtra" class="form-label">RA</label>
        <input type="text" class="form-control" id="txtra" name="txtra" maxlength="20" value="<?php echo seguro($usuario['ra'] ?? ""); ?>">
        <div class="form-text">Obrigatório para aluno.</div>
    </div>
    <div class="col-md-3">
        <label for="numsemestre" class="form-label">Semestre</label>
        <input type="number" class="form-control" id="numsemestre" name="numsemestre" min="1" max="10" value="<?php echo seguro($usuario['semestre'] ?? ""); ?>">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="ckwhats" name="ckwhats" value="1" <?php if(!empty($usuario['aviso_whatsapp'])){ echo "checked"; } ?>>
            <label class="form-check-label" for="ckwhats">Receber avisos por WhatsApp</label>
        </div>
        <?php if($editando){ ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="ckativo" name="ckativo" value="1" <?php if($usuario['ativo']){ echo "checked"; } ?>>
            <label class="form-check-label" for="ckativo">Usuário ativo (pode entrar no sistema)</label>
        </div>
        <?php } ?>
    </div>
</div>
