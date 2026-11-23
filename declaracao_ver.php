<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT d.*, u.nome, u.ra, u.semestre, c.nome AS curso
            FROM tb_documentos d
            INNER JOIN tb_usuarios u ON u.id_usuario = d.id_aluno
            LEFT JOIN tb_cursos c ON c.id_curso = u.id_curso
            WHERE d.id_documento = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $doc = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    // aluno só abre a própria declaração
    if($doc == null || ($_SESSION['nivel'] == "aluno" && $doc['id_aluno'] != $_SESSION['id_usuario'])){
        $_SESSION['erro'] = "Declaração não encontrada.";
        header("location: declaracao_lista.php");
        exit;
    }
    // endereço da página pública de validação, usado no QR Code
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    $pasta = rtrim(dirname($_SERVER['PHP_SELF']), "/\\");
    $link = $protocolo . "://" . $_SERVER['HTTP_HOST'] . $pasta . "/validar.php?codigo=" . $doc['codigo'];
    $meses = array("janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro", "dezembro");
    $data_extenso = date("d", strtotime($doc['emitido_em'])) . " de " . $meses[(int)date("m", strtotime($doc['emitido_em'])) - 1] . " de " . date("Y", strtotime($doc['emitido_em']));
    $titulo = "Declaração";
    require_once("cabecalho.php");
?>
<div class="nao-imprimir d-flex flex-wrap gap-2 mb-3">
    <a href="declaracao_lista.php" class="btn btn-outline-secondary">← Voltar</a>
    <button type="button" class="btn btn-principal" onclick="window.print()">Imprimir / Salvar como PDF</button>
</div>
<div class="declaracao">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-2">
            <span class="logo">SD</span>
            <div>
                <strong>Secretaria Digital</strong>
                <div class="small text-secondary">Faculdade de Tecnologia de Araraquara</div>
            </div>
        </div>
        <div class="small text-secondary">Documento nº <?php echo $doc['id_documento']; ?></div>
    </div>
    <h1 class="text-center h3 mb-4"><?php echo $doc['tipo'] == "matricula" ? "DECLARAÇÃO DE MATRÍCULA" : "DECLARAÇÃO DE FREQUÊNCIA"; ?></h1>
    <p style="text-align: justify; line-height: 1.9; font-size: 1.05rem;">
        Declaramos, para os devidos fins, que <strong><?php echo seguro($doc['nome']); ?></strong>,
        RA <strong><?php echo seguro($doc['ra']); ?></strong>,
        <?php if($doc['tipo'] == "matricula"){ ?>
            está regularmente matriculado(a) no curso de <strong><?php echo seguro($doc['curso']); ?></strong><?php echo $doc['semestre'] ? ", cursando o " . (int)$doc['semestre'] . "º semestre" : ""; ?>, nesta instituição.
        <?php } else { ?>
            frequenta regularmente as aulas do curso de <strong><?php echo seguro($doc['curso']); ?></strong><?php echo $doc['semestre'] ? ", no " . (int)$doc['semestre'] . "º semestre" : ""; ?>, nesta instituição.
        <?php } ?>
    </p>
    <p class="mt-4">Araraquara, <?php echo $data_extenso; ?>.</p>
    <div class="d-flex justify-content-between align-items-end mt-5 pt-3 border-top gap-3">
        <div class="small">
            <div>Código de validação: <strong><?php echo seguro($doc['codigo']); ?></strong></div>
            <div>Válido até <?php echo data_br($doc['valido_ate']); ?>.</div>
            <div>Confira a autenticidade em: <?php echo seguro($link); ?></div>
        </div>
        <div id="qrcode" aria-label="QR Code para validar o documento"></div>
    </div>
</div>
<script src="js/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), { text: <?php echo json_encode($link, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, width: 110, height: 110 });
</script>
<?php require_once("rodape.php"); ?>
