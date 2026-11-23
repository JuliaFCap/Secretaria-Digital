<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_tipos WHERE id_tipo = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Tipo não encontrado");
    }
    $tipo = $dados[0];
    $titulo = "Editar tipo de requerimento";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar tipo de requerimento</h1>
            <form action="tipo_alt2.php" method="post">
                <?php require("tipo_form.php"); ?>
                <input type="hidden" name="hidid" value="<?php echo $tipo['id_tipo']; ?>">
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="tipo_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
