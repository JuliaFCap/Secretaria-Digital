<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    // página pública: qualquer pessoa confere se uma declaração é autêntica
    $codigo = strtoupper(limpa($_GET['codigo'] ?? ""));
    $documento = null;
    if($codigo != ""){
        $sql = "SELECT d.tipo, d.emitido_em, d.valido_ate, d.ativo, u.nome, c.nome AS curso
                FROM tb_documentos d
                INNER JOIN tb_usuarios u ON u.id_usuario = d.id_aluno
                LEFT JOIN tb_cursos c ON c.id_curso = u.id_curso
                WHERE d.codigo = ? LIMIT 1";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $documento = mysqli_fetch_assoc($res);
    }
    // LGPD: mostra só o primeiro nome e a inicial do sobrenome
    function nome_parcial($nome){
        $partes = explode(" ", trim($nome));
        $parcial = $partes[0];
        if(count($partes) > 1){
            $parcial .= " " . mb_substr(end($partes), 0, 1) . "***";
        }
        return $parcial;
    }
    $titulo = "Validar documento";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1>Validar documento</h1>
        <p class="text-secondary">Digite o código que aparece no rodapé da declaração, ou escaneie o QR Code.</p>
        <form action="" method="get" class="cartao mb-4">
            <label for="codigo" class="form-label">Código do documento</label>
            <div class="d-flex gap-2">
                <input type="text" class="form-control" id="codigo" name="codigo" maxlength="16" required value="<?php echo seguro($codigo); ?>">
                <input type="submit" value="Validar" class="btn btn-principal">
            </div>
        </form>
        <?php
            if($codigo != "" && $documento == null){
                echo "<div class='alert alert-danger'><strong>Documento não encontrado.</strong> Confira o código digitado.</div>";
            }
            else if($documento != null){
                $valido = ($documento['ativo'] == 1 && strtotime($documento['valido_ate']) >= strtotime(date("Y-m-d")));
                $tipo = ($documento['tipo'] == "matricula") ? "Declaração de matrícula" : "Declaração de frequência";
                if($valido){
                    echo "<div class='alert alert-success'><strong>Documento autêntico e dentro da validade.</strong></div>";
                }
                else{
                    echo "<div class='alert alert-warning'><strong>Documento autêntico, mas vencido ou cancelado.</strong></div>";
                }
                echo "<div class='cartao'><dl class='row mb-0'>
                        <dt class='col-sm-4'>Tipo</dt><dd class='col-sm-8'>$tipo</dd>
                        <dt class='col-sm-4'>Aluno(a)</dt><dd class='col-sm-8'>" . seguro(nome_parcial($documento['nome'])) . "</dd>
                        <dt class='col-sm-4'>Curso</dt><dd class='col-sm-8'>" . seguro($documento['curso']) . "</dd>
                        <dt class='col-sm-4'>Emitido em</dt><dd class='col-sm-8'>" . data_br($documento['emitido_em'], true) . "</dd>
                        <dt class='col-sm-4'>Válido até</dt><dd class='col-sm-8'>" . data_br($documento['valido_ate']) . "</dd>
                      </dl></div>";
            }
        ?>
    </div>
</div>
<?php require_once("rodape.php"); ?>
