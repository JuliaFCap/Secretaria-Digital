<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $ordem = ordem_sql(array(
        "ordem" => "t.ordem",
        "nome" => "t.nome",
        "prazo" => "t.prazo_dias",
        "obrigatoria" => "t.obrigatoria",
        "entregas" => "entregas",
        "ativo" => "t.ativo"
    ), "ordem");
    $sql = "SELECT t.*, (SELECT COUNT(*) FROM tb_entregas e WHERE e.id_etapa = t.id_etapa) AS entregas
            FROM tb_etapas t ORDER BY $ordem";
    $dados = mysqli_fetch_all(mysqli_query($con, $sql), MYSQLI_ASSOC);
    $titulo = "Etapas do estágio";
    require_once("cabecalho.php");
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Etapas do estágio</h1>
    <div class="d-flex gap-2">
        <a href="estagio_guia.php" class="btn btn-contorno">Ver o guia</a>
        <a href="etapa_add.php" class="btn btn-principal">+ Nova etapa</a>
    </div>
</div>
<p class="text-secondary">Documentos que o aluno precisa entregar durante o estágio. Elas aparecem no guia e na área de estágio de cada aluno.</p>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("Ordem", "ordem");
                echo th_ordem("Etapa", "nome");
                echo th_ordem("Prazo", "prazo");
                echo th_ordem("Obrigatória", "obrigatoria");
                echo "<th>Modelo</th>";
                echo th_ordem("Entregas", "entregas");
                echo th_ordem("Situação", "ativo");
            ?>
            <th>ALT</th>
            <th>DEL</th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='9' class='text-secondary'>Nenhuma etapa cadastrada.</td></tr>";
            }
            foreach($dados as $linha){
                $modelo = $linha['modelo'] ? "<a href='uploads/modelos/" . seguro($linha['modelo']) . "' download>" . seguro($linha['modelo_nome']) . "</a>" : "<span class='text-secondary small'>sem modelo</span>";
                $prazo = $linha['prazo_dias'] ? (int)$linha['prazo_dias'] . " dia(s) após o início" : "—";
                echo "
                    <tr>
                        <td>" . (int)$linha['ordem'] . "</td>
                        <td>" . seguro($linha['nome']) . "</td>
                        <td>$prazo</td>
                        <td>" . ($linha['obrigatoria'] ? "Sim" : "Não") . "</td>
                        <td>$modelo</td>
                        <td>" . $linha['entregas'] . "</td>
                        <td>" . ($linha['ativo'] ? "Ativa" : "Inativa") . "</td>
                        <td><a href='etapa_alt.php?id=" . $linha['id_etapa'] . "'>ALT</a></td>
                        <td><a href='etapa_del.php?id=" . $linha['id_etapa'] . "' onclick=\"return confirm('Excluir esta etapa?')\">DEL</a></td>
                    </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
