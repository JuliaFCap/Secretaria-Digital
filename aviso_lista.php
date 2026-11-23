<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $ordem = ordem_sql(array(
        "id" => "a.id_aviso",
        "titulo" => "a.titulo",
        "autor" => "u.nome",
        "data" => "a.criado_em",
        "ate" => "a.exibir_ate",
        "publicado" => "a.publicado"
    ), "data");
    if(!isset($_GET['ordem'])){
        $ordem = "a.criado_em DESC";//mais recentes primeiro
    }
    $sql = "SELECT a.id_aviso, a.titulo, a.imagem, a.publicado, a.exibir_ate, a.criado_em, u.nome AS autor
            FROM tb_avisos a INNER JOIN tb_usuarios u ON u.id_usuario = a.id_autor
            ORDER BY $ordem";
    $res = mysqli_query($con, $sql);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "Mural de avisos";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Mural de avisos</h1>
    <a href="aviso_add.php" class="btn btn-principal">+ Novo aviso</a>
</div>
<p class="text-secondary">Os avisos publicados aparecem na página inicial do sistema.</p>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                echo "<th>Imagem</th>";
                echo th_ordem("Título", "titulo");
                echo th_ordem("Autor", "autor");
                echo th_ordem("Criado em", "data");
                echo th_ordem("Exibir até", "ate");
                echo th_ordem("Situação", "publicado");
            ?>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='9' class='text-secondary'>Nenhum aviso cadastrado.</td></tr>";
            }
            foreach($dados as $linha){
                $img = $linha['imagem'] != "" ? "<img src='uploads/avisos/" . seguro($linha['imagem']) . "' class='miniatura' alt='Imagem do aviso'>" : "<span class='text-secondary small'>sem imagem</span>";
                echo "
                    <tr>
                        <td>" . $linha['id_aviso'] . "</td>
                        <td>$img</td>
                        <td>" . seguro($linha['titulo']) . "</td>
                        <td>" . seguro($linha['autor']) . "</td>
                        <td>" . data_br($linha['criado_em']) . "</td>
                        <td>" . data_br($linha['exibir_ate']) . "</td>
                        <td>" . ($linha['publicado'] ? "Publicado" : "Rascunho") . "</td>
                        <td><a href='aviso_alt.php?id=" . $linha['id_aviso'] . "'>ALT</a></td>
                        <td><a href='aviso_del.php?id=" . $linha['id_aviso'] . "' onclick=\"return confirm('Excluir este aviso e a imagem?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
