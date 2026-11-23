<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $nivel = $_SESSION['nivel'];
    $situacao = $_GET['selsituacao'] ?? "";
    if(!in_array($situacao, array("", "em_andamento", "concluido", "cancelado"))){
        $situacao = "";
    }
    $busca = limpa($_GET['txtbusca'] ?? "");
    $termo = "%" . $busca . "%";
    $ordem = ordem_sql(array(
        "id" => "e.id_estagio",
        "aluno" => "u.nome",
        "curso" => "c.sigla",
        "empresa" => "e.empresa",
        "inicio" => "e.data_inicio",
        "fim" => "e.data_fim",
        "situacao" => "e.situacao",
        "pendentes" => "pendentes"
    ), "pendentes");
    if(!isset($_GET['ordem'])){
        $ordem = "pendentes DESC, e.data_inicio DESC";
    }
    // cada nível vê só o que pode
    if($nivel == "aluno"){
        $filtro = "e.id_aluno = ?";
        $valor = $_SESSION['id_usuario'];
    }
    else if($nivel == "coordenacao"){
        $filtro = "u.id_curso = ?";
        $valor = $_SESSION['id_curso'];
    }
    else{
        $filtro = "? = 0";
        $valor = 0;
    }
    $sql = "SELECT e.id_estagio, e.empresa, e.data_inicio, e.data_fim, e.situacao, u.nome AS aluno, c.sigla,
                   (SELECT COUNT(*) FROM tb_entregas en WHERE en.id_estagio = e.id_estagio AND en.status = 'enviado') AS pendentes
            FROM tb_estagios e
            INNER JOIN tb_usuarios u ON u.id_usuario = e.id_aluno
            LEFT JOIN tb_cursos c ON c.id_curso = u.id_curso
            WHERE $filtro AND (? = '' OR e.situacao = ?)
            AND (e.empresa LIKE ? OR u.nome LIKE ? OR u.ra LIKE ?)
            ORDER BY $ordem";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "isssss", $valor, $situacao, $situacao, $termo, $termo, $termo);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    $titulo = ($nivel == "aluno") ? "Meu estágio" : "Estágios";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0"><?php echo $titulo; ?></h1>
    <div class="d-flex gap-2">
        <a href="estagio_guia.php" class="btn btn-contorno">Guia do estágio</a>
        <?php if($nivel == "aluno"){ ?><a href="estagio_add.php" class="btn btn-principal">+ Cadastrar estágio</a><?php } ?>
    </div>
</div>
<?php if($nivel != "aluno"){ ?>
<form action="" method="get" class="d-flex flex-wrap gap-2 mb-3">
    <label for="txtbusca" class="visually-hidden">Buscar</label>
    <input type="search" class="form-control" style="max-width: 320px" id="txtbusca" name="txtbusca" placeholder="Aluno, RA ou empresa" value="<?php echo seguro($busca); ?>">
    <label for="selsituacao" class="visually-hidden">Situação</label>
    <select name="selsituacao" id="selsituacao" class="form-select" style="max-width: 220px">
        <option value="">Todas as situações</option>
        <?php
            foreach(array("em_andamento", "concluido", "cancelado") as $s){
                $sel = ($situacao == $s) ? " selected" : "";
                echo "<option value='$s'$sel>" . nome_situacao_estagio($s) . "</option>";
            }
        ?>
    </select>
    <input type="submit" value="Filtrar" class="btn btn-contorno">
</form>
<?php } ?>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("Nº", "id");
                if($nivel != "aluno"){
                    echo th_ordem("Aluno", "aluno");
                    echo th_ordem("Curso", "curso");
                }
                echo th_ordem("Empresa", "empresa");
                echo th_ordem("Início", "inicio");
                echo th_ordem("Fim", "fim");
                echo th_ordem("Situação", "situacao");
                echo "<th>Documentos</th>";
                echo th_ordem("Para avaliar", "pendentes");
            ?>
        </tr>
        <?php
            if(count($dados) == 0){
                $vazio = ($nivel == "aluno") ? "Você ainda não cadastrou seu estágio. Clique em <strong>+ Cadastrar estágio</strong>." : "Nenhum estágio encontrado.";
                echo "<tr><td colspan='9' class='text-secondary'>$vazio</td></tr>";
            }
            foreach($dados as $e){
                $p = progresso_estagio($con, $e['id_estagio']);
                $pct = $p['total'] > 0 ? round($p['aprovadas'] / $p['total'] * 100) : 0;
                echo "<tr><td><a href='estagio_ver.php?id=" . $e['id_estagio'] . "'>#" . $e['id_estagio'] . "</a></td>";
                if($nivel != "aluno"){
                    echo "<td>" . seguro($e['aluno']) . "</td><td>" . seguro($e['sigla']) . "</td>";
                }
                echo "<td><a href='estagio_ver.php?id=" . $e['id_estagio'] . "' class='fw-semibold text-decoration-none'>" . seguro($e['empresa']) . "</a></td>
                      <td>" . data_br($e['data_inicio']) . "</td>
                      <td>" . data_br($e['data_fim']) . "</td>
                      <td>" . nome_situacao_estagio($e['situacao']) . "</td>
                      <td style='min-width:140px'><div class='progress' role='progressbar' aria-label='Documentos aprovados' aria-valuenow='$pct' aria-valuemin='0' aria-valuemax='100'><div class='progress-bar' style='width: $pct%'></div></div><small>" . $p['aprovadas'] . " de " . $p['total'] . " aprovados</small></td>
                      <td>" . ($e['pendentes'] > 0 ? "<span class='status status-enviado'>" . $e['pendentes'] . "</span>" : "—") . "</td>
                      </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
