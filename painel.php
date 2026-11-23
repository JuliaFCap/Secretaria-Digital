<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $nivel = $_SESSION['nivel'];
    // tabela dos documentos de estágio aguardando avaliação
    function lista_docs_estagio($docs){
        $html = "<h2 class='h4'>Documentos de estágio para avaliar</h2>";
        if(count($docs) == 0){
            return $html . "<p class='text-secondary'>Nenhum documento aguardando avaliação.</p>";
        }
        $html .= "<div class='tabela mb-4'><table class='table align-middle'><tr><th>Aluno</th><th>Documento</th><th>Empresa</th><th>Enviado em</th><th></th></tr>";
        foreach($docs as $d){
            $html .= "<tr><td>" . seguro($d['aluno']) . "</td><td>" . seguro($d['etapa']) . "</td><td>" . seguro($d['empresa']) . "</td>
                      <td>" . data_br($d['criado_em'], true) . "</td>
                      <td><a class='btn btn-sm btn-principal' href='estagio_ver.php?id=" . (int)$d['id_estagio'] . "'>Avaliar</a></td></tr>";
        }
        return $html . "</table></div>";
    }
    $id = $_SESSION['id_usuario'];

    // notificações do usuário (as 10 mais recentes)
    $sql = "SELECT n.mensagem, n.criado_em, n.lida, n.id_chamado
            FROM tb_notificacoes n WHERE n.id_usuario = ? AND n.canal = 'sistema'
            ORDER BY n.criado_em DESC, n.id_notificacao DESC LIMIT 10";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $notificacoes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

    if($nivel == "aluno"){
        $sql = "SELECT c.id_chamado, c.assunto, c.status, c.prazo, t.nome AS tipo
                FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                WHERE c.id_aluno = ? AND c.status NOT IN ('deferido','indeferido','concluido')
                ORDER BY c.criado_em DESC";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $chamados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }
    else if($nivel == "coordenacao"){
        $sql = "SELECT c.id_chamado, c.assunto, c.prazo, c.status, t.nome AS tipo, u.nome AS aluno
                FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                INNER JOIN tb_usuarios u ON u.id_usuario = c.id_aluno
                WHERE c.status = 'aguardando_coordenacao' AND u.id_curso = ?
                ORDER BY c.prazo ASC";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_curso']);
        mysqli_stmt_execute($stmt);
        $chamados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }
    else{
        // secretaria e admin: roda os lembretes automáticos e conta os números da fila
        $enviados = envia_lembretes($con);
        $sql = "SELECT
                  SUM(status NOT IN ('deferido','indeferido','concluido')) AS abertos,
                  SUM(status NOT IN ('deferido','indeferido','concluido','aguardando_aluno') AND prazo < CURDATE()) AS atrasados,
                  SUM(status = 'aguardando_aluno') AS aguardando,
                  SUM(id_responsavel IS NULL AND status = 'aberto') AS sem_responsavel
                FROM tb_chamados";
        $res = mysqli_query($con, $sql);
        $numeros = mysqli_fetch_assoc($res);
        $sql = "SELECT COUNT(*) AS qtd FROM tb_documentos WHERE MONTH(emitido_em) = MONTH(CURDATE()) AND YEAR(emitido_em) = YEAR(CURDATE())";
        $declaracoes_mes = mysqli_fetch_assoc(mysqli_query($con, $sql))['qtd'];
    }

    // estágio: o do aluno, ou os documentos esperando avaliação (coordenação vê só o seu curso)
    $meu_estagio = null;
    $docs_estagio = array();
    if($nivel == "aluno"){
        $sql = "SELECT id_estagio, empresa,
                       (SELECT COUNT(*) FROM tb_entregas en WHERE en.id_estagio = e.id_estagio AND en.status = 'correcao'
                        AND en.id_entrega = (SELECT MAX(x.id_entrega) FROM tb_entregas x WHERE x.id_estagio = en.id_estagio AND x.id_etapa = en.id_etapa)) AS correcoes
                FROM tb_estagios e WHERE id_aluno = ? AND situacao = 'em_andamento' ORDER BY id_estagio DESC LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $meu_estagio = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
    else{
        $curso_filtro = ($nivel == "coordenacao") ? (int)$_SESSION['id_curso'] : 0;
        $sql = "SELECT en.id_entrega, en.criado_em, t.nome AS etapa, e.id_estagio, e.empresa, u.nome AS aluno
                FROM tb_entregas en
                INNER JOIN tb_etapas t ON t.id_etapa = en.id_etapa
                INNER JOIN tb_estagios e ON e.id_estagio = en.id_estagio
                INNER JOIN tb_usuarios u ON u.id_usuario = e.id_aluno
                WHERE en.status = 'enviado' AND (? = 0 OR u.id_curso = ?)
                ORDER BY en.criado_em ASC LIMIT 20";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $curso_filtro, $curso_filtro);
        mysqli_stmt_execute($stmt);
        $docs_estagio = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }

    // marca as notificações como lidas depois de carregar
    $sql = "UPDATE tb_notificacoes SET lida = 1 WHERE id_usuario = ? AND lida = 0";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $titulo = "Painel";
    require_once("cabecalho.php");
?>
<h1 class="mb-1">Olá, <?php echo seguro(explode(" ", $_SESSION['nome'])[0]); ?>!</h1>
<p class="text-secondary mb-4">Perfil: <?php echo nome_nivel($nivel); ?></p>

<?php if($nivel == "aluno"){ ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><a class="atalho atalho-principal" href="chamado_add.php"><strong>+ Nova solicitação</strong><span>Requerimentos que precisam de análise</span></a></div>
        <div class="col-md-4"><a class="atalho" href="estagio_lista.php"><strong>Meu estágio</strong><span>Envie os documentos e acompanhe a aprovação</span></a></div>
        <div class="col-md-4"><a class="atalho" href="declaracao_lista.php"><strong>Declarações</strong><span>Matrícula e frequência na hora, com QR Code</span></a></div>
    </div>
    <?php
        if($meu_estagio){
            $p = progresso_estagio($con, $meu_estagio['id_estagio']);
            $pct = $p['total'] > 0 ? round($p['aprovadas'] / $p['total'] * 100) : 0;
            echo "<div class='cartao mb-4'>
                    <div class='d-flex flex-wrap justify-content-between gap-2 mb-2'>
                        <h2 class='h5 mb-0'>Estágio · " . seguro($meu_estagio['empresa']) . "</h2>
                        <a href='estagio_ver.php?id=" . $meu_estagio['id_estagio'] . "' class='btn btn-sm btn-contorno'>Ver documentos</a>
                    </div>
                    <div class='progress mb-1' role='progressbar' aria-label='Documentos aprovados' aria-valuenow='$pct' aria-valuemin='0' aria-valuemax='100'><div class='progress-bar' style='width: $pct%'></div></div>
                    <small>" . $p['aprovadas'] . " de " . $p['total'] . " documentos obrigatórios aprovados</small>";
            if($meu_estagio['correcoes'] > 0){
                echo "<div class='alert alert-danger small py-2 mt-2 mb-0'>" . $meu_estagio['correcoes'] . " documento(s) com correção pedida. Reenvie pela página do estágio.</div>";
            }
            echo "</div>";
        }
    ?>
    <h2 class="h4">Chamados em andamento</h2>
    <?php if(count($chamados) == 0){ ?>
        <p class="text-secondary">Você não tem chamados em andamento.</p>
    <?php } else { ?>
    <div class="tabela mb-4">
        <table class="table align-middle">
            <tr><th>Nº</th><th>Solicitação</th><th>Status</th><th>Prazo</th></tr>
            <?php
                foreach($chamados as $c){
                    $destaque = ($c['status'] == "aguardando_aluno") ? " class='table-warning'" : "";
                    echo "<tr$destaque>
                            <td>#" . $c['id_chamado'] . "</td>
                            <td><a href='chamado_ver.php?id=" . $c['id_chamado'] . "'>" . seguro($c['tipo']) . "</a><div class='small text-secondary'>" . seguro($c['assunto']) . "</div></td>
                            <td>" . etiqueta_status($c['status']) . "</td>
                            <td>" . texto_prazo($c['prazo'], $c['status']) . "</td>
                          </tr>";
                }
            ?>
        </table>
    </div>
    <?php } ?>

<?php } else if($nivel == "coordenacao"){ ?>
    <h2 class="h4">Pareceres pendentes do seu curso</h2>
    <?php if(count($chamados) == 0){ ?>
        <p class="text-secondary">Nenhum chamado aguardando parecer.</p>
    <?php } else { ?>
    <div class="tabela mb-4">
        <table class="table align-middle">
            <tr><th>Nº</th><th>Aluno</th><th>Tipo</th><th>Prazo</th><th></th></tr>
            <?php
                foreach($chamados as $c){
                    echo "<tr>
                            <td>#" . $c['id_chamado'] . "</td>
                            <td>" . seguro($c['aluno']) . "</td>
                            <td>" . seguro($c['tipo']) . "<div class='small text-secondary'>" . seguro($c['assunto']) . "</div></td>
                            <td>" . texto_prazo($c['prazo'], $c['status']) . "</td>
                            <td><a class='btn btn-sm btn-principal' href='chamado_ver.php?id=" . $c['id_chamado'] . "'>Dar parecer</a></td>
                          </tr>";
                }
            ?>
        </table>
    </div>
    <?php } ?>

    <?php echo lista_docs_estagio($docs_estagio); ?>

<?php } else { ?>
    <?php if($enviados > 0){ echo "<div class='alert alert-warning'>$enviados lembrete(s) automático(s) enviado(s) a alunos com chamados parados.</div>"; } ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg"><div class="cartao"><div class="rotulo">Em aberto</div><div class="numero"><?php echo (int)$numeros['abertos']; ?></div></div></div>
        <div class="col-6 col-lg"><div class="cartao" style="border-color:#F3B4B4"><div class="rotulo text-danger fw-semibold">Atrasados</div><div class="numero text-danger"><?php echo (int)$numeros['atrasados']; ?></div></div></div>
        <div class="col-6 col-lg"><div class="cartao"><div class="rotulo">Aguardando aluno</div><div class="numero"><?php echo (int)$numeros['aguardando']; ?></div></div></div>
        <div class="col-6 col-lg"><div class="cartao"><div class="rotulo">Sem responsável</div><div class="numero"><?php echo (int)$numeros['sem_responsavel']; ?></div></div></div>
        <div class="col-6 col-lg"><div class="cartao"><div class="rotulo">Docs. de estágio</div><div class="numero"><?php echo count($docs_estagio); ?></div></div></div>
        <div class="col-6 col-lg"><div class="cartao"><div class="rotulo">Declarações no mês</div><div class="numero"><?php echo (int)$declaracoes_mes; ?></div></div></div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3"><a class="atalho atalho-principal" href="kanban.php"><strong>Abrir o kanban</strong><span>Fila de chamados por status</span></a></div>
        <div class="col-md-6 col-lg-3"><a class="atalho" href="estagio_lista.php"><strong>Estágios</strong><span>Documentos enviados pelos alunos</span></a></div>
        <div class="col-md-6 col-lg-3"><a class="atalho" href="chat.php"><strong>Chat da equipe</strong><span>Conversa com secretaria e coordenação</span></a></div>
        <div class="col-md-6 col-lg-3"><a class="atalho" href="relatorios.php"><strong>Relatórios</strong><span>Chamados, estágios, cursos e atrasos</span></a></div>
    </div>
    <?php echo lista_docs_estagio($docs_estagio); ?>
<?php } ?>

<h2 class="h4" id="notificacoes">Notificações</h2>
<?php if(count($notificacoes) == 0){ ?>
    <p class="text-secondary">Nenhuma notificação.</p>
<?php } else { ?>
<ul class="list-group mb-4">
    <?php
        foreach($notificacoes as $n){
            $novo = ($n['lida'] == 0) ? " <span class='badge text-bg-danger'>nova</span>" : "";
            $link = $n['id_chamado'] ? " <a href='chamado_ver.php?id=" . (int)$n['id_chamado'] . "'>ver chamado</a>" : "";
            echo "<li class='list-group-item d-flex justify-content-between gap-3'>
                    <span>" . seguro($n['mensagem']) . "$novo$link</span>
                    <small class='text-secondary text-nowrap'>" . data_br($n['criado_em'], true) . "</small>
                  </li>";
        }
    ?>
</ul>
<?php } ?>
<?php require_once("rodape.php"); ?>
