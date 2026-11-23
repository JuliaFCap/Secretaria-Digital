<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $chamado = busca_chamado($con, $id);
    if(!pode_ver_chamado($chamado)){
        $_SESSION['erro'] = "Chamado não encontrado ou sem permissão de acesso.";
        header("location: chamado_lista.php");
        exit;
    }
    $nivel = $_SESSION['nivel'];
    $equipe = ($nivel != "aluno");//secretaria, coordenação e admin veem notas internas
    $finalizado = in_array($chamado['status'], array("deferido", "indeferido", "concluido"));

    // mensagens (aluno não vê as notas internas)
    $sql = "SELECT m.mensagem, m.interna, m.criado_em, u.nome, u.nivel
            FROM tb_mensagens m INNER JOIN tb_usuarios u ON u.id_usuario = m.id_usuario
            WHERE m.id_chamado = ? AND (m.interna = 0 OR ? = 1)
            ORDER BY m.criado_em ASC, m.id_mensagem ASC";
    $ve_interna = $equipe ? 1 : 0;
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $ve_interna);
    mysqli_stmt_execute($stmt);
    $mensagens = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    // anexos
    $sql = "SELECT x.id_anexo, x.nome_original, x.tamanho, x.criado_em, u.nome
            FROM tb_anexos x INNER JOIN tb_usuarios u ON u.id_usuario = x.id_usuario
            WHERE x.id_chamado = ? ORDER BY x.criado_em ASC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $anexos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    // histórico (auditoria do chamado)
    $sql = "SELECT h.acao, h.status_anterior, h.status_novo, h.observacao, h.criado_em, u.nome
            FROM tb_historico h LEFT JOIN tb_usuarios u ON u.id_usuario = h.id_usuario
            WHERE h.id_chamado = ? ORDER BY h.criado_em DESC, h.id_historico DESC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $historico = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    // respostas prontas para a secretaria
    $respostas = array();
    if($nivel == "secretaria"){
        $res = mysqli_query($con, "SELECT titulo, texto FROM tb_respostas WHERE ativo = 1 ORDER BY titulo ASC");
        $respostas = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
    // outros chamados do mesmo aluno (ficha do aluno, o "CRM")
    $outros = array();
    if($equipe){
        $sql = "SELECT c.id_chamado, c.status, t.nome AS tipo FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                WHERE c.id_aluno = ? AND c.id_chamado <> ? ORDER BY c.id_chamado DESC LIMIT 5";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $chamado['id_aluno'], $id);
        mysqli_stmt_execute($stmt);
        $outros = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }
    $titulo = "Chamado #$id";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="chamado_lista.php" class="small fw-semibold">← Voltar para a lista</a>
        <h1 class="mb-1 mt-1">#<?php echo $id; ?> · <?php echo seguro($chamado['tipo']); ?></h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php echo etiqueta_status($chamado['status']); ?>
            <span class="text-secondary small">Aberto em <?php echo data_br($chamado['criado_em'], true); ?> · Prazo <?php echo data_br($chamado['prazo']); ?> (<?php echo strip_tags(texto_prazo($chamado['prazo'], $chamado['status'])); ?>) · Responsável: <?php echo seguro($chamado['responsavel'] ?? "ninguém ainda"); ?></span>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8 d-flex flex-column gap-3">
        <article class="mensagem mensagem-aluno">
            <div class="d-flex justify-content-between small mb-2">
                <strong><?php echo seguro($chamado['aluno']); ?> <span class="fw-normal text-secondary">· aluno(a)</span></strong>
                <span class="text-secondary"><?php echo data_br($chamado['criado_em'], true); ?></span>
            </div>
            <h2 class="h6 fw-bold"><?php echo seguro($chamado['assunto']); ?></h2>
            <p class="mb-0"><?php echo nl2br(seguro($chamado['descricao'])); ?></p>
        </article>

        <?php
            foreach($mensagens as $m){
                $classe = $m['interna'] ? "mensagem mensagem-interna" : ($m['nivel'] == "aluno" ? "mensagem mensagem-aluno" : "mensagem");
                $rotulo = $m['interna'] ? "<span class='text-warning-emphasis'>Nota interna · só a equipe vê</span>" : nome_nivel($m['nivel']);
                echo "<article class='$classe'>
                        <div class='d-flex justify-content-between small mb-2'>
                            <strong>" . seguro($m['nome']) . " <span class='fw-normal text-secondary'>· $rotulo</span></strong>
                            <span class='text-secondary'>" . data_br($m['criado_em'], true) . "</span>
                        </div>
                        <p class='mb-0'>" . nl2br(seguro($m['mensagem'])) . "</p>
                      </article>";
            }
        ?>

        <?php if($chamado['parecer'] != ""){ ?>
            <div class="alert <?php echo $chamado['status'] == 'indeferido' ? 'alert-danger' : 'alert-success'; ?> mb-0">
                <strong>Parecer da coordenação (<?php echo seguro($chamado['parecerista']); ?>, <?php echo data_br($chamado['parecer_em'], true); ?>):</strong><br>
                <?php echo nl2br(seguro($chamado['parecer'])); ?>
            </div>
        <?php } ?>

        <?php if($nivel != "admin" && !$finalizado){ ?>
        <form action="chamado_msg2.php" method="post" enctype="multipart/form-data" class="cartao">
            <h2 class="h6 fw-bold">Responder</h2>
            <?php if(count($respostas) > 0){ ?>
                <label for="selresposta" class="form-label small">Resposta pronta</label>
                <select id="selresposta" class="form-select form-select-sm mb-2">
                    <option value="">Escolher modelo…</option>
                    <?php
                        foreach($respostas as $r){
                            echo "<option value='" . seguro($r['texto']) . "'>" . seguro($r['titulo']) . "</option>";
                        }
                    ?>
                </select>
            <?php } ?>
            <label for="txtmensagem" class="visually-hidden">Mensagem</label>
            <textarea class="form-control mb-2" id="txtmensagem" name="txtmensagem" rows="4" required placeholder="Escreva sua mensagem…"></textarea>
            <label for="uplanexo" class="form-label small">Anexo (opcional, PDF/PNG/JPG até 2 MB)</label>
            <input type="file" class="form-control form-control-sm mb-2" id="uplanexo" name="uplanexo" accept=".pdf,.png,.jpg,.jpeg">
            <?php if($equipe){ ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="ckinterna" name="ckinterna" value="1">
                    <label class="form-check-label" for="ckinterna">Nota interna (o aluno não vê)</label>
                </div>
            <?php } ?>
            <input type="hidden" name="hidid" value="<?php echo $id; ?>">
            <input type="submit" name="btn" value="Enviar" class="btn btn-principal">
        </form>
        <?php } ?>

        <?php if($nivel == "aluno" && $finalizado && $chamado['avaliacao'] == null){ ?>
        <form action="chamado_avaliar2.php" method="post" class="cartao">
            <h2 class="h6 fw-bold">Como foi o atendimento?</h2>
            <div class="d-flex flex-wrap gap-3 mb-2">
                <?php
                    for($n = 1; $n <= 5; $n++){
                        echo "<div class='form-check'>
                                <input class='form-check-input' type='radio' name='rdnota' id='nota$n' value='$n' required>
                                <label class='form-check-label' for='nota$n'>$n</label>
                              </div>";
                    }
                ?>
            </div>
            <input type="hidden" name="hidid" value="<?php echo $id; ?>">
            <input type="submit" name="btn" value="Avaliar" class="btn btn-contorno btn-sm">
        </form>
        <?php } else if($chamado['avaliacao'] != null){ ?>
            <p class="text-secondary small mb-0">Avaliação do aluno: <?php echo (int)$chamado['avaliacao']; ?> de 5.</p>
        <?php } ?>
    </div>

    <aside class="col-lg-4 d-flex flex-column gap-3">
        <?php if($nivel == "secretaria" && !$finalizado){ ?>
        <div class="cartao">
            <h2 class="h6 fw-bold">Ações</h2>
            <?php if($chamado['id_responsavel'] != $_SESSION['id_usuario']){ ?>
                <form action="chamado_acao2.php" method="post" class="mb-2">
                    <input type="hidden" name="hidid" value="<?php echo $id; ?>">
                    <input type="hidden" name="hidacao" value="assumir">
                    <input type="submit" value="Assumir este chamado" class="btn btn-principal w-100">
                </form>
            <?php } ?>
            <form action="chamado_acao2.php" method="post">
                <label for="selstatus" class="form-label small">Alterar status</label>
                <select class="form-select mb-2" id="selstatus" name="selstatus">
                    <?php
                        foreach(array("em_analise", "aguardando_aluno", "aguardando_coordenacao", "concluido") as $s){
                            $sel = ($chamado['status'] == $s) ? " selected" : "";
                            echo "<option value='$s'$sel>" . nome_status($s) . "</option>";
                        }
                    ?>
                </select>
                <label for="txtobs" class="form-label small">Observação (vai para o histórico)</label>
                <input type="text" class="form-control mb-2" id="txtobs" name="txtobs" maxlength="255" placeholder="Ex.: falta a ementa">
                <input type="hidden" name="hidid" value="<?php echo $id; ?>">
                <input type="hidden" name="hidacao" value="status">
                <input type="submit" value="Salvar status" class="btn btn-contorno w-100">
            </form>
            <?php if($chamado['status'] == "aguardando_aluno"){ ?>
                <form action="chamado_acao2.php" method="post" class="mt-2">
                    <input type="hidden" name="hidid" value="<?php echo $id; ?>">
                    <input type="hidden" name="hidacao" value="lembrete">
                    <input type="submit" value="Reenviar aviso ao aluno" class="btn btn-outline-secondary w-100">
                </form>
            <?php } ?>
            <p class="small text-secondary mt-2 mb-0">Deferir ou indeferir é feito só pela coordenação.</p>
        </div>
        <?php } ?>

        <?php if($nivel == "coordenacao" && $chamado['status'] == "aguardando_coordenacao"){ ?>
        <form action="chamado_parecer2.php" method="post" class="cartao">
            <h2 class="h6 fw-bold">Parecer da coordenação</h2>
            <div class="form-check"><input class="form-check-input" type="radio" name="rdparecer" id="pdef" value="deferido" required><label class="form-check-label" for="pdef">Deferir</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="rdparecer" id="pind" value="indeferido"><label class="form-check-label" for="pind">Indeferir</label></div>
            <div class="form-check mb-2"><input class="form-check-input" type="radio" name="rdparecer" id="pdev" value="devolver"><label class="form-check-label" for="pdev">Devolver para a secretaria</label></div>
            <label for="txtparecer" class="form-label small">Justificativa</label>
            <textarea class="form-control mb-2" id="txtparecer" name="txtparecer" rows="4" required></textarea>
            <input type="hidden" name="hidid" value="<?php echo $id; ?>">
            <input type="submit" name="btn" value="Registrar parecer" class="btn btn-principal w-100">
        </form>
        <?php } ?>

        <div class="cartao">
            <h2 class="h6 fw-bold">Anexos (<?php echo count($anexos); ?>)</h2>
            <?php
                if(count($anexos) == 0){
                    echo "<p class='small text-secondary mb-0'>Nenhum anexo.</p>";
                }
                foreach($anexos as $x){
                    echo "<div class='small mb-1'><a href='anexo_ver.php?id=" . $x['id_anexo'] . "' target='_blank'>" . seguro($x['nome_original']) . "</a>
                          <span class='text-secondary'>· " . round($x['tamanho'] / 1024) . " KB · " . seguro($x['nome']) . "</span></div>";
                }
            ?>
        </div>

        <?php if($equipe){ ?>
        <div class="cartao">
            <h2 class="h6 fw-bold">Ficha do aluno</h2>
            <dl class="row small mb-2">
                <dt class="col-5">Nome</dt><dd class="col-7"><?php echo seguro($chamado['aluno']); ?></dd>
                <dt class="col-5">RA</dt><dd class="col-7"><?php echo seguro($chamado['ra']); ?></dd>
                <dt class="col-5">Curso</dt><dd class="col-7"><?php echo seguro($chamado['sigla']); ?><?php echo $chamado['semestre'] ? " · " . (int)$chamado['semestre'] . "º sem" : ""; ?></dd>
                <dt class="col-5">E-mail</dt><dd class="col-7 text-break"><?php echo seguro($chamado['email_aluno']); ?></dd>
            </dl>
            <strong class="small">Outros chamados</strong>
            <?php
                if(count($outros) == 0){
                    echo "<p class='small text-secondary mb-0'>Nenhum.</p>";
                }
                foreach($outros as $o){
                    echo "<div class='small'><a href='chamado_ver.php?id=" . $o['id_chamado'] . "'>#" . $o['id_chamado'] . " " . seguro($o['tipo']) . "</a> · " . nome_status($o['status']) . "</div>";
                }
            ?>
        </div>
        <?php } ?>

        <div class="cartao">
            <h2 class="h6 fw-bold">Histórico</h2>
            <ol class="list-unstyled small mb-0 d-flex flex-column gap-2">
                <?php
                    foreach($historico as $h){
                        $mudanca = $h['status_novo'] ? " → " . nome_status($h['status_novo']) : "";
                        $obs = $h['observacao'] ? "<div class='text-secondary'>" . seguro($h['observacao']) . "</div>" : "";
                        echo "<li><strong>" . data_br($h['criado_em'], true) . "</strong> · " . seguro($h['nome'] ?? "Sistema") . "<br>" . seguro($h['acao']) . $mudanca . $obs . "</li>";
                    }
                ?>
            </ol>
        </div>
    </aside>
</div>
<script>
    // resposta pronta: copia o texto do modelo para a caixa de mensagem
    var sel = document.getElementById("selresposta");
    if(sel){
        sel.addEventListener("change", function(){
            if(sel.value != ""){
                document.getElementById("txtmensagem").value = sel.value;
            }
        });
    }
</script>
<?php require_once("rodape.php"); ?>
