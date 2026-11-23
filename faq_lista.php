<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $ordem = ordem_sql(array(
        "id" => "f.id_faq",
        "pergunta" => "f.pergunta",
        "categoria" => "f.categoria",
        "autor" => "u.nome",
        "data" => "f.atualizado_em",
        "ativo" => "f.ativo"
    ), "categoria");
    $sql = "SELECT f.id_faq, f.pergunta, f.categoria, f.ativo, f.atualizado_em, u.nome AS autor
            FROM tb_faq f INNER JOIN tb_usuarios u ON u.id_usuario = f.id_autor
            ORDER BY $ordem";
    $res = mysqli_query($con, $sql);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "FAQ";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Perguntas frequentes (FAQ)</h1>
    <div class="d-flex gap-2">
        <a href="faq.php" class="btn btn-contorno">Ver como o aluno vê</a>
        <a href="faq_add.php" class="btn btn-principal">+ Nova pergunta</a>
    </div>
</div>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                echo th_ordem("Pergunta", "pergunta");
                echo th_ordem("Categoria", "categoria");
                echo th_ordem("Autor", "autor");
                echo th_ordem("Atualizada", "data");
                echo th_ordem("Situação", "ativo");
            ?>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='8' class='text-secondary'>Nenhuma pergunta cadastrada.</td></tr>";
            }
            foreach($dados as $linha){
                echo "
                    <tr>
                        <td>" . $linha['id_faq'] . "</td>
                        <td>" . seguro($linha['pergunta']) . "</td>
                        <td>" . seguro($linha['categoria']) . "</td>
                        <td>" . seguro($linha['autor']) . "</td>
                        <td>" . data_br($linha['atualizado_em']) . "</td>
                        <td>" . ($linha['ativo'] ? "Publicada" : "Oculta") . "</td>
                        <td><a href='faq_alt.php?id=" . $linha['id_faq'] . "'>ALT</a></td>
                        <td><a href='faq_del.php?id=" . $linha['id_faq'] . "' onclick=\"return confirm('Excluir esta pergunta?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
