<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_login();
    $nivel = $_SESSION['nivel'];
    $status = $_GET['selstatus'] ?? "";
    $todos_status = array("aberto", "em_analise", "aguardando_aluno", "aguardando_coordenacao", "deferido", "indeferido", "concluido");
    if(!in_array($status, $todos_status)){
        $status = "";
    }
    $busca = limpa($_GET['txtbusca'] ?? "");
    $termo = "%" . $busca . "%";
    $ordem = ordem_sql(array(
        "id" => "c.id_chamado",
        "aluno" => "a.nome",
        "tipo" => "t.nome",
        "status" => "c.status",
        "prazo" => "c.prazo",
        "abertura" => "c.criado_em",
        "responsavel" => "r.nome"
    ), "id");
    // cada nível vê só o que pode: aluno os seus, coordenação o seu curso, secretaria/admin todos
    if($nivel == "aluno"){
        $filtro = "c.id_aluno = ?";
        $valor = $_SESSION['id_usuario'];
    }
    else if($nivel == "coordenacao"){
        $filtro = "a.id_curso = ?";
        $valor = $_SESSION['id_curso'];
    }
    else{
        $filtro = "? = 0";
        $valor = 0;
    }
    $sql = "SELECT c.id_chamado, c.assunto, c.status, c.prazo, c.criado_em,
                   t.nome AS tipo, a.nome AS aluno, cu.sigla, r.nome AS responsavel
            FROM tb_chamados c
            INNER JOIN tb_tipos t ON t.id_tipo = c.id_tipo
            INNER JOIN tb_usuarios a ON a.id_usuario = c.id_aluno
            LEFT JOIN tb_cursos cu ON cu.id_curso = a.id_curso
            LEFT JOIN tb_usuarios r ON r.id_usuario = c.id_responsavel
            WHERE $filtro
            AND (? = '' OR c.status = ?)
            AND (c.assunto LIKE ? OR a.nome LIKE ? OR a.ra LIKE ? OR t.nome LIKE ? OR c.id_chamado = ?)
            ORDER BY $ordem";
    $id_busca = (int)$busca;
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "issssssi", $valor, $status, $status, $termo, $termo, $termo, $termo, $id_busca);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulos = array("aluno" => "Meus chamados", "coordenacao" => "Chamados do curso", "secretaria" => "Chamados", "admin" => "Chamados");
    $titulo = $titulos[$nivel];
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0"><?php echo $titulo; ?></h1>
    <?php if($nivel == "aluno"){ ?><a href="chamado_add.php" class="btn btn-principal">+ Nova solicitação</a><?php } ?>
    <?php if($nivel == "secretaria" || $nivel == "admin"){ ?><a href="kanban.php" class="btn btn-contorno">Ver no kanban</a><?php } ?>
</div>
<form action="" method="get" class="d-flex flex-wrap gap-2 mb-3">
    <label for="txtbusca" class="visually-hidden">Buscar</label>
    <input type="search" class="form-control" style="max-width: 320px" id="txtbusca" name="txtbusca" placeholder="Nº, assunto, aluno ou RA" value="<?php echo seguro($busca); ?>">
    <label for="selstatus" class="visually-hidden">Status</label>
    <select name="selstatus" id="selstatus" class="form-select" style="max-width: 240px">
        <option value="">Todos os status</option>
        <?php
            foreach($todos_status as $s){
                $sel = ($status == $s) ? " selected" : "";
                echo "<option value='$s'$sel>" . nome_status($s) . "</option>";
            }
        ?>
    </select>
    <input type="submit" value="Filtrar" class="btn btn-contorno">
</form>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("Nº", "id");
                if($nivel != "aluno"){
                    echo th_ordem("Aluno", "aluno");
                }
                echo th_ordem("Solicitação", "tipo");
                echo th_ordem("Status", "status");
                echo th_ordem("Prazo", "prazo");
                echo th_ordem("Abertura", "abertura");
                echo th_ordem("Responsável", "responsavel");
            ?>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='7' class='text-secondary'>Nenhum chamado encontrado.</td></tr>";
            }
            foreach($dados as $c){
                echo "<tr>
                        <td><a href='chamado_ver.php?id=" . $c['id_chamado'] . "'>#" . $c['id_chamado'] . "</a></td>";
                if($nivel != "aluno"){
                    echo "<td>" . seguro($c['aluno']) . "<div class='small text-secondary'>" . seguro($c['sigla']) . "</div></td>";
                }
                echo "  <td><a href='chamado_ver.php?id=" . $c['id_chamado'] . "' class='fw-semibold text-decoration-none'>" . seguro($c['tipo']) . "</a><div class='small text-secondary'>" . seguro($c['assunto']) . "</div></td>
                        <td>" . etiqueta_status($c['status']) . "</td>
                        <td>" . texto_prazo($c['prazo'], $c['status']) . "</td>
                        <td>" . data_br($c['criado_em']) . "</td>
                        <td>" . seguro($c['responsavel'] ?? "—") . "</td>
                      </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
