<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_etapas WHERE id_etapa = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Etapa não encontrada");
    }
    $etapa = $dados[0];
    $titulo = "Editar etapa do estágio";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar etapa do estágio</h1>
            <form action="etapa_alt2.php" method="post" enctype="multipart/form-data">
                <?php require("etapa_form.php"); ?>
                <input type="hidden" name="hidid" value="<?php echo $etapa['id_etapa']; ?>">
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="etapa_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
