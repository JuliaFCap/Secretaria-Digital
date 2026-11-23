<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria", "coordenacao", "admin"]);
    // coordenação só enxerga o próprio curso
    $curso_filtro = ($_SESSION['nivel'] == "coordenacao") ? (int)$_SESSION['id_curso'] : 0;
    $relatorios = array(
        "chamados" => "Chamados (detalhado)",
        "tipos" => "Por tipo de requerimento",
        "cursos" => "Por curso",
        "atrasados" => "Atrasados",
        "alunos" => "Por aluno",
        "estagios" => "Estágios"
    );
    $rel = $_GET['rel'] ?? "chamados";
    if(!array_key_exists($rel, $relatorios)){
        $rel = "chamados";
    }
    // período (vale para os relatórios de chamados, tipos, cursos e alunos)
    $dtini = $_GET['dtini'] ?? date("Y-01-01");
    $dtfim = $_GET['dtfim'] ?? date("Y-m-d");
    if(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dtini)){ $dtini = date("Y-01-01"); }
    if(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dtfim)){ $dtfim = date("Y-m-d"); }

    if($rel == "chamados"){
        $colunas = array("id" => "Nº", "aluno" => "Aluno", "curso" => "Curso", "tipo" => "Tipo", "status" => "Status",
                         "abertura" => "Abertura", "prazo" => "Prazo", "responsavel" => "Responsável", "avaliacao" => "Avaliação");
        $ordem = ordem_sql(array("id" => "c.id_chamado", "aluno" => "a.nome", "curso" => "cu.sigla", "tipo" => "t.nome",
            "status" => "c.status", "abertura" => "c.criado_em", "prazo" => "c.prazo", "responsavel" => "r.nome", "avaliacao" => "c.avaliacao"), "id");
        $sql = "SELECT c.id_chamado AS id, a.nome AS aluno, cu.sigla AS curso, t.nome AS tipo, c.status,
                       c.criado_em AS abertura, c.prazo, r.nome AS responsavel, c.avaliacao
                FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
                LEFT JOIN tb_cursos cu ON cu.id_curso = a.id_curso
                LEFT JOIN tb_usuarios r ON r.id_usuario = c.id_responsavel
                WHERE DATE(c.criado_em) BETWEEN ? AND ? AND (? = 0 OR a.id_curso = ?)
                ORDER BY $ordem";
    }
    else if($rel == "tipos"){
        $colunas = array("tipo" => "Tipo", "total" => "Total", "andamento" => "Em andamento", "deferidos" => "Deferidos",
                         "indeferidos" => "Indeferidos", "concluidos" => "Concluídos", "atrasados" => "Atrasados", "media" => "Nota média");
        $ordem = ordem_sql(array("tipo" => "tipo", "total" => "total", "andamento" => "andamento", "deferidos" => "deferidos",
            "indeferidos" => "indeferidos", "concluidos" => "concluidos", "atrasados" => "atrasados", "media" => "media"), "total");
        $sql = "SELECT t.nome AS tipo, COUNT(*) AS total,
                       SUM(c.status NOT IN ('deferido','indeferido','concluido')) AS andamento,
                       SUM(c.status = 'deferido') AS deferidos, SUM(c.status = 'indeferido') AS indeferidos,
                       SUM(c.status = 'concluido') AS concluidos,
                       SUM(c.status NOT IN ('deferido','indeferido','concluido','aguardando_aluno') AND c.prazo < CURDATE()) AS atrasados,
                       ROUND(AVG(c.avaliacao), 1) AS media
                FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
                WHERE DATE(c.criado_em) BETWEEN ? AND ? AND (? = 0 OR a.id_curso = ?)
                GROUP BY t.id_tipo, t.nome
                ORDER BY $ordem";
    }
    else if($rel == "cursos"){
        $colunas = array("curso" => "Curso", "alunos" => "Alunos com chamado", "total" => "Chamados", "andamento" => "Em andamento",
                         "atrasados" => "Atrasados", "media" => "Nota média");
        $ordem = ordem_sql(array("curso" => "curso", "alunos" => "alunos", "total" => "total", "andamento" => "andamento",
            "atrasados" => "atrasados", "media" => "media"), "total");
        $sql = "SELECT cu.nome AS curso, COUNT(DISTINCT c.id_aluno) AS alunos, COUNT(*) AS total,
                       SUM(c.status NOT IN ('deferido','indeferido','concluido')) AS andamento,
                       SUM(c.status NOT IN ('deferido','indeferido','concluido','aguardando_aluno') AND c.prazo < CURDATE()) AS atrasados,
                       ROUND(AVG(c.avaliacao), 1) AS media
                FROM tb_chamados c
                INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
                INNER JOIN tb_cursos cu ON cu.id_curso = a.id_curso
                WHERE DATE(c.criado_em) BETWEEN ? AND ? AND (? = 0 OR a.id_curso = ?)
                GROUP BY cu.id_curso, cu.nome
                ORDER BY $ordem";
    }
    else if($rel == "atrasados"){
        $colunas = array("id" => "Nº", "aluno" => "Aluno", "curso" => "Curso", "tipo" => "Tipo", "status" => "Status",
                         "prazo" => "Prazo", "dias" => "Dias de atraso", "responsavel" => "Responsável");
        $ordem = ordem_sql(array("id" => "c.id_chamado", "aluno" => "a.nome", "curso" => "cu.sigla", "tipo" => "t.nome",
            "status" => "c.status", "prazo" => "c.prazo", "dias" => "dias", "responsavel" => "r.nome"), "dias");
        if(!isset($_GET['ordem'])){
            $ordem = "dias DESC";
        }
        // atrasados não dependem do período: considera todos
        $dtini = "2000-01-01";
        $dtfim = date("Y-m-d");
        $sql = "SELECT c.id_chamado AS id, a.nome AS aluno, cu.sigla AS curso, t.nome AS tipo, c.status, c.prazo,
                       DATEDIFF(CURDATE(), c.prazo) AS dias, r.nome AS responsavel
                FROM tb_chamados c
                INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
                INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
                LEFT JOIN tb_cursos cu ON cu.id_curso = a.id_curso
                LEFT JOIN tb_usuarios r ON r.id_usuario = c.id_responsavel
                WHERE c.status NOT IN ('deferido','indeferido','concluido','aguardando_aluno') AND c.prazo < CURDATE()
                AND DATE(c.criado_em) BETWEEN ? AND ? AND (? = 0 OR a.id_curso = ?)
                ORDER BY $ordem";
    }
    else if($rel == "estagios"){
        $colunas = array("estagio" => "Nº", "aluno" => "Aluno", "curso" => "Curso", "empresa" => "Empresa", "inicio" => "Início",
                         "fim" => "Fim", "situacao" => "Situação", "aprovados" => "Docs. aprovados", "pendentes" => "Para avaliar", "correcoes" => "Com correção");
        $ordem = ordem_sql(array("estagio" => "estagio", "aluno" => "aluno", "curso" => "curso", "empresa" => "empresa", "inicio" => "inicio",
            "fim" => "fim", "situacao" => "situacao", "aprovados" => "aprovados", "pendentes" => "pendentes", "correcoes" => "correcoes"), "inicio");
        $sql = "SELECT e.id_estagio AS estagio, u.nome AS aluno, cu.sigla AS curso, e.empresa, e.data_inicio AS inicio, e.data_fim AS fim,
                       CASE e.situacao WHEN 'em_andamento' THEN 'Em andamento' WHEN 'concluido' THEN 'Concluído' ELSE 'Cancelado' END AS situacao,
                       (SELECT COUNT(DISTINCT en.id_etapa) FROM tb_entregas en WHERE en.id_estagio = e.id_estagio AND en.status = 'aprovado') AS aprovados,
                       (SELECT COUNT(*) FROM tb_entregas en WHERE en.id_estagio = e.id_estagio AND en.status = 'enviado') AS pendentes,
                       (SELECT COUNT(*) FROM tb_entregas en WHERE en.id_estagio = e.id_estagio AND en.status = 'correcao'
                        AND en.id_entrega = (SELECT MAX(x.id_entrega) FROM tb_entregas x WHERE x.id_estagio = en.id_estagio AND x.id_etapa = en.id_etapa)) AS correcoes
                FROM tb_estagios e
                INNER JOIN tb_usuarios u ON u.id_usuario = e.id_aluno
                LEFT JOIN tb_cursos cu ON cu.id_curso = u.id_curso
                WHERE e.data_inicio BETWEEN ? AND ? AND (? = 0 OR u.id_curso = ?)
                ORDER BY $ordem";
    }
    else{
        $colunas = array("aluno" => "Aluno", "ra" => "RA", "curso" => "Curso", "total" => "Chamados",
                         "andamento" => "Em andamento", "declaracoes" => "Declarações emitidas");
        $ordem = ordem_sql(array("aluno" => "aluno", "ra" => "ra", "curso" => "curso", "total" => "total",
            "andamento" => "andamento", "declaracoes" => "declaracoes"), "total");
        $sql = "SELECT a.nome AS aluno, a.ra, cu.sigla AS curso,
                       COUNT(c.id_chamado) AS total,
                       COALESCE(SUM(c.status NOT IN ('deferido','indeferido','concluido')), 0) AS andamento,
                       (SELECT COUNT(*) FROM tb_documentos d WHERE d.id_aluno = a.id_usuario) AS declaracoes
                FROM tb_usuarios a
                LEFT JOIN tb_cursos cu ON cu.id_curso = a.id_curso
                LEFT JOIN tb_chamados c ON c.id_aluno = a.id_usuario AND DATE(c.criado_em) BETWEEN ? AND ?
                WHERE a.nivel = 'aluno' AND (? = 0 OR a.id_curso = ?)
                GROUP BY a.id_usuario, a.nome, a.ra, cu.sigla
                ORDER BY $ordem";
    }
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $dtini, $dtfim, $curso_filtro, $curso_filtro);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    $titulo = "Relatórios";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Relatórios</h1>
    <button type="button" class="btn btn-contorno nao-imprimir" onclick="window.print()">Imprimir</button>
</div>
<ul class="nav nav-pills mb-3 nao-imprimir">
    <?php
        foreach($relatorios as $chave => $nome){
            $ativo = ($chave == $rel) ? " active" : "";
            echo "<li class='nav-item'><a class='nav-link$ativo' href='?rel=$chave&dtini=$dtini&dtfim=$dtfim'>$nome</a></li>";
        }
    ?>
</ul>
<?php if($rel != "atrasados"){ ?>
<form action="" method="get" class="d-flex flex-wrap align-items-end gap-2 mb-3 nao-imprimir">
    <input type="hidden" name="rel" value="<?php echo $rel; ?>">
    <div>
        <label for="dtini" class="form-label small mb-1">De</label>
        <input type="date" class="form-control form-control-sm" id="dtini" name="dtini" value="<?php echo seguro($dtini); ?>">
    </div>
    <div>
        <label for="dtfim" class="form-label small mb-1">Até</label>
        <input type="date" class="form-control form-control-sm" id="dtfim" name="dtfim" value="<?php echo seguro($dtfim); ?>">
    </div>
    <input type="submit" value="Aplicar período" class="btn btn-sm btn-contorno">
</form>
<?php } ?>
<h2 class="h5"><?php echo $relatorios[$rel]; ?> <small class="text-secondary fw-normal">· <?php echo count($dados); ?> linha(s)<?php echo $curso_filtro ? " · somente o seu curso" : ""; ?></small></h2>
<p class="small text-secondary nao-imprimir">Clique no título de uma coluna para ordenar.</p>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                foreach($colunas as $campo => $rotulo){
                    echo th_ordem($rotulo, $campo);
                }
            ?>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='" . count($colunas) . "' class='text-secondary'>Nenhum dado no período.</td></tr>";
            }
            foreach($dados as $linha){
                echo "<tr>";
                foreach($colunas as $campo => $rotulo){
                    $valor = $linha[$campo];
                    if($campo == "id"){
                        $valor = "<a href='chamado_ver.php?id=" . (int)$valor . "'>#" . (int)$valor . "</a>";
                    }
                    else if($campo == "estagio"){
                        $valor = "<a href='estagio_ver.php?id=" . (int)$valor . "'>#" . (int)$valor . "</a>";
                    }
                    else if($campo == "inicio" || $campo == "fim"){
                        $valor = data_br($valor);
                    }
                    else if($campo == "status"){
                        $valor = etiqueta_status($valor);
                    }
                    else if($campo == "abertura" || $campo == "prazo"){
                        $valor = data_br($valor);
                    }
                    else if($valor === null || $valor === ""){
                        $valor = "—";
                    }
                    else{
                        $valor = seguro($valor);
                    }
                    echo "<td>$valor</td>";
                }
                echo "</tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
