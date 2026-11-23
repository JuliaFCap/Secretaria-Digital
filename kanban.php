<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria", "admin"]);
    $pode_mexer = ($_SESSION['nivel'] == "secretaria");//admin só visualiza
    $enviados = envia_lembretes($con);
    // filtros
    $curso = (int)($_GET['selcurso'] ?? 0);
    $tipo = (int)($_GET['seltipo'] ?? 0);
    $so_meus = isset($_GET['ckmeus']) ? 1 : 0;
    $sql = "SELECT c.id_chamado, c.assunto, c.status, c.prazo, c.id_responsavel, c.aguardando_desde, c.ultimo_lembrete,
                   c.atualizado_em, t.nome AS tipo, a.nome AS aluno, cu.sigla, r.nome AS responsavel
            FROM tb_chamados c
            INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
            INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
            LEFT JOIN tb_cursos cu ON cu.id_curso = a.id_curso
            LEFT JOIN tb_usuarios r ON r.id_usuario = c.id_responsavel
            WHERE (c.status NOT IN ('deferido','indeferido','concluido') OR c.atualizado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY))
            AND (? = 0 OR a.id_curso = ?)
            AND (? = 0 OR c.id_tipo = ?)
            AND (? = 0 OR c.id_responsavel = ?)
            ORDER BY c.prazo ASC, c.id_chamado ASC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "iiiiii", $curso, $curso, $tipo, $tipo, $so_meus, $_SESSION['id_usuario']);
    mysqli_stmt_execute($stmt);
    $chamados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    // separa por coluna
    $colunas = array(
        "aberto" => array(),
        "em_analise" => array(),
        "aguardando_aluno" => array(),
        "aguardando_coordenacao" => array(),
        "finalizados" => array()
    );
    foreach($chamados as $c){
        $chave = in_array($c['status'], array("deferido", "indeferido", "concluido")) ? "finalizados" : $c['status'];
        $colunas[$chave][] = $c;
    }
    $nomes_colunas = array(
        "aberto" => "Aberto",
        "em_analise" => "Em análise",
        "aguardando_aluno" => "Aguardando aluno",
        "aguardando_coordenacao" => "Aguard. coordenação",
        "finalizados" => "Finalizados (7 dias)"
    );
    $cursos = mysqli_fetch_all(mysqli_query($con, "SELECT id_curso, sigla FROM tb_cursos ORDER BY sigla"), MYSQLI_ASSOC);
    $tipos = mysqli_fetch_all(mysqli_query($con, "SELECT id_tipo, nome FROM tb_tipos ORDER BY nome"), MYSQLI_ASSOC);
    $titulo = "Kanban";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Fila de chamados</h1>
    <a href="chamado_lista.php" class="btn btn-contorno">Ver em lista</a>
</div>
<?php
    if($enviados > 0){
        echo "<div class='alert alert-warning'>$enviados lembrete(s) automático(s) enviado(s) a alunos com chamados parados.</div>";
    }
    if(!$pode_mexer){
        echo "<div class='alert alert-secondary'>Perfil Administração: visualização apenas. Quem movimenta os chamados é a secretaria.</div>";
    }
?>
<form action="" method="get" class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <label for="selcurso" class="small fw-semibold">Curso</label>
    <select name="selcurso" id="selcurso" class="form-select form-select-sm" style="width:auto">
        <option value="0">Todos</option>
        <?php foreach($cursos as $cu){ $s = ($curso == $cu['id_curso']) ? " selected" : ""; echo "<option value='" . $cu['id_curso'] . "'$s>" . seguro($cu['sigla']) . "</option>"; } ?>
    </select>
    <label for="seltipo" class="small fw-semibold">Tipo</label>
    <select name="seltipo" id="seltipo" class="form-select form-select-sm" style="width:auto">
        <option value="0">Todos</option>
        <?php foreach($tipos as $t){ $s = ($tipo == $t['id_tipo']) ? " selected" : ""; echo "<option value='" . $t['id_tipo'] . "'$s>" . seguro($t['nome']) . "</option>"; } ?>
    </select>
    <?php if($pode_mexer){ ?>
    <div class="form-check ms-2">
        <input class="form-check-input" type="checkbox" name="ckmeus" id="ckmeus" value="1" <?php if($so_meus){ echo "checked"; } ?>>
        <label class="form-check-label small" for="ckmeus">Só os meus</label>
    </div>
    <?php } ?>
    <input type="submit" value="Filtrar" class="btn btn-sm btn-contorno">
</form>

<div class="kanban">
<?php
    foreach($colunas as $chave => $lista){
        $classe = ($chave == "finalizados") ? "col-finalizados" : "col-$chave";
        echo "<section class='coluna $classe' aria-label='" . $nomes_colunas[$chave] . "'>
                <h2>" . $nomes_colunas[$chave] . " <span class='badge text-bg-light'>" . count($lista) . "</span></h2>";
        if(count($lista) == 0){
            echo "<p class='small text-secondary mb-0'>Nenhum chamado.</p>";
        }
        foreach($lista as $c){
            $finalizado = ($chave == "finalizados");
            $atrasado = (!$finalizado && $c['status'] != "aguardando_aluno" && strtotime($c['prazo']) < strtotime(date("Y-m-d")));
            echo "<article class='card-chamado" . ($atrasado ? " atrasado" : "") . "'>
                    <div class='d-flex justify-content-between small'>
                        <a href='chamado_ver.php?id=" . $c['id_chamado'] . "' class='fw-bold'>#" . $c['id_chamado'] . "</a>
                        <span>" . ($finalizado ? etiqueta_status($c['status']) : texto_prazo($c['prazo'], $c['status'])) . "</span>
                    </div>
                    <strong>" . seguro($c['tipo']) . "</strong>
                    <span class='small text-secondary'>" . seguro($c['aluno']) . " · " . seguro($c['sigla']) . "</span>
                    <span class='small text-secondary'>Resp.: " . seguro($c['responsavel'] ?? "ninguém") . "</span>";
            // alerta de lembrete no card (o chamado não fecha sozinho)
            if($c['status'] == "aguardando_aluno" && $c['aguardando_desde']){
                $dias = (int)floor((time() - strtotime($c['aguardando_desde'])) / 86400);
                $lembrete = $c['ultimo_lembrete'] ? "Último lembrete em " . data_br($c['ultimo_lembrete'], true) . "." : "Nenhum lembrete enviado ainda.";
                echo "<div class='alerta-lembrete'><strong>$dias dia(s) sem resposta.</strong> $lembrete</div>";
            }
            if($pode_mexer && !$finalizado){
                if($c['id_responsavel'] == null){
                    echo "<form action='chamado_acao2.php' method='post'>
                            <input type='hidden' name='hidid' value='" . $c['id_chamado'] . "'>
                            <input type='hidden' name='hidacao' value='assumir'>
                            <input type='hidden' name='hidvoltar' value='kanban'>
                            <input type='submit' value='Assumir' class='btn btn-sm btn-contorno w-100'>
                          </form>";
                }
                if($c['status'] == "aguardando_aluno"){
                    echo "<form action='chamado_acao2.php' method='post'>
                            <input type='hidden' name='hidid' value='" . $c['id_chamado'] . "'>
                            <input type='hidden' name='hidacao' value='lembrete'>
                            <input type='hidden' name='hidvoltar' value='kanban'>
                            <input type='submit' value='Reenviar aviso' class='btn btn-sm btn-outline-secondary w-100'>
                          </form>";
                }
                echo "<form action='chamado_acao2.php' method='post' class='d-flex gap-1'>
                        <label class='visually-hidden' for='mover" . $c['id_chamado'] . "'>Mover chamado " . $c['id_chamado'] . "</label>
                        <select name='selstatus' id='mover" . $c['id_chamado'] . "' class='form-select form-select-sm'>";
                foreach(array("em_analise", "aguardando_aluno", "aguardando_coordenacao", "concluido") as $s){
                    if($s != $c['status']){
                        echo "<option value='$s'>" . nome_status($s) . "</option>";
                    }
                }
                echo "  </select>
                        <input type='hidden' name='hidid' value='" . $c['id_chamado'] . "'>
                        <input type='hidden' name='hidacao' value='status'>
                        <input type='hidden' name='hidvoltar' value='kanban'>
                        <input type='submit' value='Mover' class='btn btn-sm btn-principal'>
                      </form>";
            }
            echo "</article>";
        }
        echo "</section>";
    }
?>
</div>
<?php require_once("rodape.php"); ?>
