<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    // secretaria só enxerga e cadastra alunos
    if($_SESSION['nivel'] == "secretaria"){
        $filtro_nivel = "aluno";
    }
    else{
        $filtro_nivel = $_GET['selnivel'] ?? "";
        if(!in_array($filtro_nivel, array("", "aluno", "secretaria", "coordenacao", "admin"))){
            $filtro_nivel = "";
        }
    }
    $busca = limpa($_GET['txtbusca'] ?? "");
    $termo = "%" . $busca . "%";
    $ordem = ordem_sql(array(
        "id" => "u.id_usuario",
        "nome" => "u.nome",
        "login" => "u.login",
        "nivel" => "u.nivel",
        "curso" => "c.sigla",
        "ra" => "u.ra",
        "ativo" => "u.ativo"
    ), "nome");
    $sql = "SELECT u.id_usuario, u.nome, u.login, u.email, u.nivel, u.ra, u.semestre, u.ativo, c.sigla
            FROM tb_usuarios u
            LEFT JOIN tb_cursos c ON c.id_curso = u.id_curso
            WHERE (? = '' OR u.nivel = ?)
            AND (u.nome LIKE ? OR u.login LIKE ? OR u.ra LIKE ? OR u.email LIKE ?)
            ORDER BY $ordem";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $filtro_nivel, $filtro_nivel, $termo, $termo, $termo, $termo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = ($_SESSION['nivel'] == "secretaria") ? "Alunos" : "Usuários";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0"><?php echo $titulo; ?></h1>
    <a href="usuario_add.php" class="btn btn-principal">+ Novo <?php echo ($_SESSION['nivel'] == "secretaria") ? "aluno" : "usuário"; ?></a>
</div>
<form action="" method="get" class="d-flex flex-wrap gap-2 mb-3">
    <label for="txtbusca" class="visually-hidden">Buscar</label>
    <input type="search" class="form-control" style="max-width: 320px" id="txtbusca" name="txtbusca" placeholder="Nome, login, RA ou e-mail" value="<?php echo seguro($busca); ?>">
    <?php if($_SESSION['nivel'] == "admin"){ ?>
        <label for="selnivel" class="visually-hidden">Nível</label>
        <select name="selnivel" id="selnivel" class="form-select" style="max-width: 200px">
            <option value="">Todos os níveis</option>
            <?php
                foreach(array("aluno", "secretaria", "coordenacao", "admin") as $n){
                    $sel = ($filtro_nivel == $n) ? " selected" : "";
                    echo "<option value='$n'$sel>" . nome_nivel($n) . "</option>";
                }
            ?>
        </select>
    <?php } ?>
    <input type="submit" value="Filtrar" class="btn btn-contorno">
</form>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                echo th_ordem("Nome", "nome");
                echo th_ordem("Login", "login");
                echo th_ordem("Nível", "nivel");
                echo th_ordem("Curso", "curso");
                echo th_ordem("RA", "ra");
                echo th_ordem("Situação", "ativo");
            ?>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='9' class='text-secondary'>Nenhum usuário encontrado.</td></tr>";
            }
            foreach($dados as $linha){
                echo "
                    <tr>
                        <td>" . $linha['id_usuario'] . "</td>
                        <td>" . seguro($linha['nome']) . "<div class='small text-secondary'>" . seguro($linha['email']) . "</div></td>
                        <td>" . seguro($linha['login']) . "</td>
                        <td>" . nome_nivel($linha['nivel']) . "</td>
                        <td>" . seguro($linha['sigla'] ?? "—") . ($linha['semestre'] ? " · " . (int)$linha['semestre'] . "º sem" : "") . "</td>
                        <td>" . seguro($linha['ra'] ?? "—") . "</td>
                        <td>" . ($linha['ativo'] ? "Ativo" : "Inativo") . "</td>
                        <td><a href='usuario_alt.php?id=" . $linha['id_usuario'] . "'>ALT</a></td>
                        <td><a href='usuario_del.php?id=" . $linha['id_usuario'] . "' onclick=\"return confirm('Excluir este usuário?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
