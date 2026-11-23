<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $ordem = ordem_sql(array(
        "id" => "r.id_resposta",
        "titulo" => "r.titulo",
        "autor" => "u.nome",
        "data" => "r.criado_em",
        "ativo" => "r.ativo"
    ), "titulo");
    $sql = "SELECT r.id_resposta, r.titulo, r.texto, r.ativo, r.criado_em, u.nome AS autor
            FROM tb_respostas r INNER JOIN tb_usuarios u ON u.id_usuario = r.id_autor
            ORDER BY $ordem";
    $res = mysqli_query($con, $sql);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "Respostas prontas";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Respostas prontas</h1>
    <a href="resposta_add.php" class="btn btn-principal">+ Nova resposta</a>
</div>
<p class="text-secondary">Modelos de texto que a secretaria escolhe na hora de responder um chamado.</p>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                echo th_ordem("Título", "titulo");
                echo "<th>Texto</th>";
                echo th_ordem("Autor", "autor");
                echo th_ordem("Situação", "ativo");
            ?>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='7' class='text-secondary'>Nenhuma resposta pronta cadastrada.</td></tr>";
            }
            foreach($dados as $linha){
                $resumo = mb_strimwidth($linha['texto'], 0, 90, "...");
                echo "
                    <tr>
                        <td>" . $linha['id_resposta'] . "</td>
                        <td>" . seguro($linha['titulo']) . "</td>
                        <td class='small text-secondary'>" . seguro($resumo) . "</td>
                        <td>" . seguro($linha['autor']) . "</td>
                        <td>" . ($linha['ativo'] ? "Ativa" : "Inativa") . "</td>
                        <td><a href='resposta_alt.php?id=" . $linha['id_resposta'] . "'>ALT</a></td>
                        <td><a href='resposta_del.php?id=" . $linha['id_resposta'] . "' onclick=\"return confirm('Excluir esta resposta?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
