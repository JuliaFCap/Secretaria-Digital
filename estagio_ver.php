<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $estagio = busca_estagio($con, $id);
    if(!pode_ver_estagio($estagio)){
        $_SESSION['erro'] = "Estágio não encontrado ou sem permissão de acesso.";
        header("location: estagio_lista.php");
        exit;
    }
    $nivel = $_SESSION['nivel'];
    $em_andamento = ($estagio['situacao'] == "em_andamento");
    $pode_avaliar = pode_avaliar_estagio($estagio);
    // etapas ativas + as que já têm entrega neste estágio
    $sql = "SELECT t.* FROM tb_etapas t
            WHERE t.ativo = 1 OR EXISTS(SELECT 1 FROM tb_entregas e WHERE e.id_etapa = t.id_etapa AND e.id_estagio = ?)
            ORDER BY t.ordem ASC, t.id_etapa ASC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $etapas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    // todas as entregas do estágio, da mais nova para a mais antiga
    $sql = "SELECT en.*, u.nome AS avaliador FROM tb_entregas en
            LEFT JOIN tb_usuarios u ON u.id_usuario = en.id_avaliador
            WHERE en.id_estagio = ? ORDER BY en.id_entrega DESC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $entregas = array();
    foreach(mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC) as $en){
        $entregas[$en['id_etapa']][] = $en;
    }
    $p = progresso_estagio($con, $id);
    $pct = $p['total'] > 0 ? round($p['aprovadas'] / $p['total'] * 100) : 0;
    $titulo = "Estágio #$id";
    require_once("cabecalho.php");
?>
<a href="estagio_lista.php" class="small fw-semibold">← Voltar</a>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-1 mb-4">
    <div>
        <h1 class="mb-1">Estágio · <?php echo seguro($estagio['empresa']); ?></h1>
        <span class="text-secondary"><?php echo seguro($estagio['aluno']); ?> (RA <?php echo seguro($estagio['ra']); ?>, <?php echo seguro($estagio['sigla']); ?>) · <?php echo nome_situacao_estagio($estagio['situacao']); ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="estagio_guia.php" class="btn btn-contorno">Guia do estágio</a>
        <?php if(($nivel == "aluno" && $em_andamento) || $nivel == "secretaria" || $nivel == "coordenacao"){ ?>
            <a href="estagio_alt.php?id=<?php echo $id; ?>" class="btn btn-contorno">Editar dados</a>
        <?php } ?>
        <?php if($nivel == "secretaria" || $nivel == "admin"){ ?>
            <a href="estagio_del.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary" onclick="return confirm('Excluir este estágio e todos os documentos enviados?')">Excluir</a>
        <?php } ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8 d-flex flex-column gap-3">
        <div class="cartao">
            <div class="d-flex justify-content-between small mb-1"><strong>Documentos aprovados</strong><span><?php echo $p['aprovadas']; ?> de <?php echo $p['total']; ?> obrigatórios</span></div>
            <div class="progress" role="progressbar" aria-label="Documentos aprovados" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?php echo $pct; ?>%"></div></div>
            <?php if($p['total'] > 0 && $p['aprovadas'] == $p['total'] && $em_andamento){ echo "<p class='small text-success fw-semibold mt-2 mb-0'>Todos os documentos obrigatórios foram aprovados.</p>"; } ?>
        </div>
        <?php
            if(count($etapas) == 0){
                echo "<div class='alert alert-secondary'>A secretaria ainda não cadastrou as etapas do estágio.</div>";
            }
            $n = 0;
            foreach($etapas as $t){
                $n++;
                $lista = $entregas[$t['id_etapa']] ?? array();
                $ultima = $lista[0] ?? null;
                $status = $ultima ? $ultima['status'] : "pendente";
                $prazo_txt = "";
                if($t['prazo_dias'] !== null){
                    $limite = date("Y-m-d", strtotime($estagio['data_inicio'] . " +" . (int)$t['prazo_dias'] . " days"));
                    $prazo_txt = "Entregar até " . data_br($limite);
                    if($status != "aprovado" && strtotime($limite) < strtotime(date("Y-m-d")) && $em_andamento){
                        $prazo_txt = "<span class='text-danger fw-semibold'>Prazo vencido em " . data_br($limite) . "</span>";
                    }
                }
                echo "<section class='etapa $status' aria-label='" . seguro($t['nome']) . "'>
                        <div class='d-flex gap-3'>
                            <span class='numero-etapa'>$n</span>
                            <div class='flex-grow-1 min-w-0'>
                                <div class='d-flex flex-wrap justify-content-between gap-2'>
                                    <h2 class='h6 fw-bold mb-1'>" . seguro($t['nome']) . ($t['obrigatoria'] ? "" : " <span class='fw-normal text-secondary'>(opcional)</span>") . "</h2>
                                    <span class='status status-$status'>" . nome_status_entrega($status) . "</span>
                                </div>
                                <div class='small text-secondary mb-2'>$prazo_txt" . ($t['modelo'] ? ($prazo_txt ? " · " : "") . "<a href='uploads/modelos/" . seguro($t['modelo']) . "' download>baixar modelo</a>" : "") . "</div>";
                // última entrega e a avaliação
                if($ultima){
                    echo "<div class='small mb-2'>Enviado em " . data_br($ultima['criado_em'], true) . ": <a href='estagio_arquivo.php?id=" . $ultima['id_entrega'] . "' target='_blank'>" . seguro($ultima['nome_original']) . "</a></div>";
                    if($ultima['status'] != "enviado"){
                        $cor = ($ultima['status'] == "aprovado") ? "alert-success" : "alert-danger";
                        $obs = $ultima['observacao'] ? "<br>" . nl2br(seguro($ultima['observacao'])) : "";
                        echo "<div class='alert $cor small py-2 mb-2'><strong>" . nome_status_entrega($ultima['status']) . "</strong> por " . seguro($ultima['avaliador']) . " em " . data_br($ultima['avaliado_em'], true) . $obs . "</div>";
                    }
                }
                // aluno envia (ou reenvia, se pediram correção)
                if($nivel == "aluno" && $em_andamento && $status != "aprovado" && $status != "enviado"){
                    echo "<form action='estagio_entrega2.php' method='post' enctype='multipart/form-data' class='d-flex flex-wrap gap-2 align-items-center'>
                            <label class='visually-hidden' for='upl" . $t['id_etapa'] . "'>Arquivo para " . seguro($t['nome']) . "</label>
                            <input type='file' class='form-control form-control-sm' style='max-width:340px' id='upl" . $t['id_etapa'] . "' name='upldoc' accept='.pdf,.png,.jpg,.jpeg' required>
                            <input type='hidden' name='hidid' value='$id'>
                            <input type='hidden' name='hidetapa' value='" . $t['id_etapa'] . "'>
                            <input type='submit' value='" . ($status == "correcao" ? "Reenviar" : "Enviar") . "' class='btn btn-sm btn-principal'>
                          </form>";
                }
                // secretaria/coordenação avaliam a entrega aguardando análise
                if($pode_avaliar && $ultima && $ultima['status'] == "enviado"){
                    echo "<form action='estagio_avaliar2.php' method='post' class='border-top pt-2 mt-1'>
                            <div class='d-flex flex-wrap gap-3 mb-2'>
                                <div class='form-check'><input class='form-check-input' type='radio' name='rdresultado' id='ap" . $ultima['id_entrega'] . "' value='aprovado' required><label class='form-check-label' for='ap" . $ultima['id_entrega'] . "'>Aprovar</label></div>
                                <div class='form-check'><input class='form-check-input' type='radio' name='rdresultado' id='co" . $ultima['id_entrega'] . "' value='correcao'><label class='form-check-label' for='co" . $ultima['id_entrega'] . "'>Pedir correção</label></div>
                            </div>
                            <label class='visually-hidden' for='obs" . $ultima['id_entrega'] . "'>Observação</label>
                            <textarea class='form-control form-control-sm mb-2' id='obs" . $ultima['id_entrega'] . "' name='txtobs' rows='2' maxlength='500' placeholder='Observação para o aluno (obrigatória ao pedir correção)'></textarea>
                            <input type='hidden' name='hidentrega' value='" . $ultima['id_entrega'] . "'>
                            <input type='submit' value='Registrar avaliação' class='btn btn-sm btn-principal'>
                          </form>";
                }
                // histórico de envios anteriores
                if(count($lista) > 1){
                    echo "<details class='small mt-2'><summary>Envios anteriores (" . (count($lista) - 1) . ")</summary><ul class='mb-0 mt-1'>";
                    foreach(array_slice($lista, 1) as $antiga){
                        echo "<li>" . data_br($antiga['criado_em'], true) . " · <a href='estagio_arquivo.php?id=" . $antiga['id_entrega'] . "' target='_blank'>" . seguro($antiga['nome_original']) . "</a> · " . nome_status_entrega($antiga['status']) . ($antiga['observacao'] ? " — " . seguro($antiga['observacao']) : "") . "</li>";
                    }
                    echo "</ul></details>";
                }
                echo "      </div>
                        </div>
                      </section>";
            }
        ?>
    </div>
    <aside class="col-lg-4">
        <div class="cartao">
            <h2 class="h6 fw-bold">Dados do estágio</h2>
            <dl class="row small mb-0">
                <dt class="col-5">Empresa</dt><dd class="col-7"><?php echo seguro($estagio['empresa']); ?></dd>
                <dt class="col-5">CNPJ</dt><dd class="col-7"><?php echo seguro($estagio['cnpj'] ?? "—"); ?></dd>
                <dt class="col-5">Supervisor(a)</dt><dd class="col-7"><?php echo seguro($estagio['supervisor']); ?></dd>
                <dt class="col-5">E-mail</dt><dd class="col-7 text-break"><?php echo seguro($estagio['email_supervisor'] ?? "—"); ?></dd>
                <dt class="col-5">Área</dt><dd class="col-7"><?php echo seguro($estagio['area'] ?? "—"); ?></dd>
                <dt class="col-5">Carga horária</dt><dd class="col-7"><?php echo (int)$estagio['horas_semana']; ?> h/semana</dd>
                <dt class="col-5">Período</dt><dd class="col-7"><?php echo data_br($estagio['data_inicio']); ?> a <?php echo data_br($estagio['data_fim']); ?></dd>
                <dt class="col-5">Situação</dt><dd class="col-7"><?php echo nome_situacao_estagio($estagio['situacao']); ?></dd>
            </dl>
        </div>
    </aside>
</div>
<?php require_once("rodape.php"); ?>
