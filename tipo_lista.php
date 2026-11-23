<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $ordem = ordem_sql(array(
        "id" => "t.id_tipo",
        "nome" => "t.nome",
        "prazo" => "t.prazo_dias",
        "coord" => "t.precisa_coordenacao",
        "chamados" => "chamados",
        "ativo" => "t.ativo"
    ), "nome");
    $sql = "SELECT t.*, (SELECT COUNT(*) FROM tb_chamados c WHERE c.id_tipo = t.id_tipo) AS chamados
            FROM tb_tipos t ORDER BY $ordem";
    $res = mysqli_query($con, $sql);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "Tipos de requerimento";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Tipos de requerimento</h1>
    <a href="tipo_add.php" class="btn btn-principal">+ Novo tipo</a>
</div>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                echo th_ordem("Nome", "nome");
                echo th_ordem("Prazo", "prazo");
                echo th_ordem("Coordenação", "coord");
                echo th_ordem("Chamados", "chamados");
                echo th_ordem("Situação", "ativo");
            ?>
            <th>Anexo</th>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='9' class='text-secondary'>Nenhum tipo cadastrado.</td></tr>";
            }
            foreach($dados as $linha){
                echo "
                    <tr>
                        <td>" . $linha['id_tipo'] . "</td>
                        <td>" . seguro($linha['nome']) . "<div class='small text-secondary'>" . seguro($linha['documentos_exigidos']) . "</div></td>
                        <td>" . $linha['prazo_dias'] . " dia(s)</td>
                        <td>" . ($linha['precisa_coordenacao'] ? "Sim" : "Não") . "</td>
                        <td>" . $linha['chamados'] . "</td>
                        <td>" . ($linha['ativo'] ? "Ativo" : "Inativo") . "</td>
                        <td>" . ($linha['exige_anexo'] ? "Obrigatório" : "Opcional") . "</td>
                        <td><a href='tipo_alt.php?id=" . $linha['id_tipo'] . "'>ALT</a></td>
                        <td><a href='tipo_del.php?id=" . $linha['id_tipo'] . "' onclick=\"return confirm('Excluir este tipo?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
