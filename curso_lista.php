<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $ordem = ordem_sql(array(
        "id" => "c.id_curso",
        "nome" => "c.nome",
        "sigla" => "c.sigla",
        "alunos" => "alunos",
        "ativo" => "c.ativo"
    ), "nome");
    $sql = "SELECT c.id_curso, c.nome, c.sigla, c.ativo,
                   (SELECT COUNT(*) FROM tb_usuarios u WHERE u.id_curso = c.id_curso AND u.nivel = 'aluno') AS alunos
            FROM tb_cursos c
            ORDER BY $ordem";
    $res = mysqli_query($con, $sql);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "Cursos";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Cursos</h1>
    <a href="curso_add.php" class="btn btn-principal">+ Novo curso</a>
</div>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                echo th_ordem("Nome", "nome");
                echo th_ordem("Sigla", "sigla");
                echo th_ordem("Alunos", "alunos");
                echo th_ordem("Situação", "ativo");
            ?>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='7' class='text-secondary'>Nenhum curso cadastrado.</td></tr>";
            }
            foreach($dados as $linha){
                $situacao = $linha['ativo'] ? "Ativo" : "Inativo";
                echo "
                    <tr>
                        <td>" . $linha['id_curso'] . "</td>
                        <td>" . seguro($linha['nome']) . "</td>
                        <td>" . seguro($linha['sigla']) . "</td>
                        <td>" . $linha['alunos'] . "</td>
                        <td>$situacao</td>
                        <td><a href='curso_alt.php?id=" . $linha['id_curso'] . "'>ALT</a></td>
                        <td><a href='curso_del.php?id=" . $linha['id_curso'] . "' onclick=\"return confirm('Excluir este curso?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
