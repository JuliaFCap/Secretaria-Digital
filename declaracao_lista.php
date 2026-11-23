<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno", "secretaria"]);
    $nivel = $_SESSION['nivel'];
    $ordem = ordem_sql(array(
        "id" => "d.id_documento",
        "aluno" => "u.nome",
        "tipo" => "d.tipo",
        "emitido" => "d.emitido_em",
        "validade" => "d.valido_ate"
    ), "emitido");
    if(!isset($_GET['ordem'])){
        $ordem = "d.emitido_em DESC";
    }
    // aluno vê só as próprias; secretaria vê todas
    $id_filtro = ($nivel == "aluno") ? $_SESSION['id_usuario'] : 0;
    $sql = "SELECT d.id_documento, d.codigo, d.tipo, d.emitido_em, d.valido_ate, u.nome AS aluno
            FROM tb_documentos d INNER JOIN tb_usuarios u ON u.id_usuario = d.id_aluno
            WHERE (? = 0 OR d.id_aluno = ?)
            ORDER BY $ordem";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_filtro, $id_filtro);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    $alunos = array();
    if($nivel == "secretaria"){
        $res = mysqli_query($con, "SELECT id_usuario, nome, ra FROM tb_usuarios WHERE nivel = 'aluno' AND ativo = 1 ORDER BY nome");
        $alunos = mysqli_fetch_all($res, MYSQLI_ASSOC);
    }
    $titulo = "Declarações";
    require_once("cabecalho.php");
?>
<h1>Declarações</h1>
<p class="text-secondary">A declaração sai na hora, com um código e um QR Code para qualquer pessoa conferir se é verdadeira. Validade de 90 dias.</p>
<form action="declaracao_add2.php" method="post" class="cartao mb-4">
    <div class="row g-3 align-items-end">
        <?php if($nivel == "secretaria"){ ?>
        <div class="col-md-5">
            <label for="selaluno" class="form-label">Aluno</label>
            <select name="selaluno" id="selaluno" class="form-select" required>
                <option value="">Selecione o aluno</option>
                <?php
                    foreach($alunos as $a){
                        echo "<option value='" . $a['id_usuario'] . "'>" . seguro($a['nome']) . " (RA " . seguro($a['ra']) . ")</option>";
                    }
                ?>
            </select>
        </div>
        <?php } ?>
        <div class="col-md-4">
            <label for="seltipo" class="form-label">Tipo de declaração</label>
            <select name="seltipo" id="seltipo" class="form-select" required>
                <option value="matricula">Declaração de matrícula</option>
                <option value="frequencia">Declaração de frequência</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="submit" name="btn" value="Emitir agora" class="btn btn-principal w-100">
        </div>
    </div>
</form>
<h2 class="h4">Emitidas</h2>
<div class="tabela">
    <table class="table align-middle">
        <tr>
            <?php
                echo th_ordem("ID", "id");
                if($nivel == "secretaria"){
                    echo th_ordem("Aluno", "aluno");
                }
                echo th_ordem("Tipo", "tipo");
                echo th_ordem("Emitida em", "emitido");
                echo th_ordem("Válida até", "validade");
            ?>
            <th>Código</th>
            <th></th>
        </tr>
        <?php
            if(count($dados) == 0){
                echo "<tr><td colspan='7' class='text-secondary'>Nenhuma declaração emitida.</td></tr>";
            }
            foreach($dados as $d){
                echo "<tr><td>" . $d['id_documento'] . "</td>";
                if($nivel == "secretaria"){
                    echo "<td>" . seguro($d['aluno']) . "</td>";
                }
                echo "<td>" . ($d['tipo'] == "matricula" ? "Matrícula" : "Frequência") . "</td>
                      <td>" . data_br($d['emitido_em'], true) . "</td>
                      <td>" . data_br($d['valido_ate']) . "</td>
                      <td><code>" . seguro($d['codigo']) . "</code></td>
                      <td><a href='declaracao_ver.php?id=" . $d['id_documento'] . "' class='btn btn-sm btn-contorno'>Abrir / imprimir</a></td>
                      </tr>";
            }
        ?>
    </table>
</div>
<?php require_once("rodape.php"); ?>
