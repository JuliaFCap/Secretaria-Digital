<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $ordem = ordem_sql(array(
        "data" => "a.criado_em",
        "usuario" => "u.nome",
        "acao" => "a.acao",
        "tabela" => "a.tabela",
        "ip" => "a.ip"
    ), "data");
    if(!isset($_GET['ordem'])){
        $ordem = "a.criado_em DESC";
    }
    $busca = limpa($_GET['txtbusca'] ?? "");
    $termo = "%" . $busca . "%";
    $sql = "SELECT a.criado_em, a.acao, a.tabela, a.id_registro, a.detalhes, a.ip, u.nome AS usuario
            FROM tb_auditoria a LEFT JOIN tb_usuarios u ON u.id_usuario = a.id_usuario
            WHERE (a.acao LIKE ? OR a.detalhes LIKE ? OR u.nome LIKE ? OR a.tabela LIKE ?)
            ORDER BY $ordem, a.id_auditoria DESC
            LIMIT 300";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $termo, $termo, $termo, $termo);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    $titulo = "Auditoria";
    require_once("cabecalho.php");
?>
<h1>Log de auditoria</h1>
<p class="text-secondary">Entradas, cadastros, alterações e exclusões feitas no sistema (últimos 300 registros).</p>
<form action="" method="get" class="d-flex gap-2 mb-3">
    <label for="txtbusca" class="visually-hidden">Buscar</label>
    <input type="search" class="form-control" style="max-width: 360px" id="txtbusca" name="txtbusca" placeholder="Ação, usuário, tabela ou detalhe" value="<?php echo seguro($busca); ?>">
    <input type="submit" value="Buscar" class="btn btn-contorno">
</form>
<div class="tabela">
    <table class="table table-sm align-middle">
        <tr>
            <?php
                echo th_ordem("Data", "data");
                echo th_ordem("Usuário", "usuario");
                echo th_ordem("Ação", "acao");
                echo th_ordem("Tabela", "tabela");
            ?>
            <th>Detalhes</th>
            <?php echo th_ordem("IP", "ip"); ?>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='6' class='text-secondary'>Nenhum registro.</td></tr>";
            }
            foreach($dados as $linha){
                $registro = $linha['id_registro'] ? " #" . (int)$linha['id_registro'] : "";
                echo "<tr>
                        <td class='text-nowrap'>" . data_br($linha['criado_em'], true) . "</td>
                        <td>" . seguro($linha['usuario'] ?? "—") . "</td>
                        <td>" . seguro($linha['acao']) . "</td>
                        <td>" . seguro($linha['tabela']) . $registro . "</td>
                        <td>" . seguro($linha['detalhes']) . "</td>
                        <td>" . seguro($linha['ip']) . "</td>
                      </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
