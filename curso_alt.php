<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_cursos WHERE id_curso = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Curso não encontrado");
    }
    $curso = $dados[0];
    $titulo = "Editar curso";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar curso</h1>
            <form action="curso_alt2.php" method="post">
                <div class="mb-3">
                    <label for="txtnome" class="form-label">Nome do curso</label>
                    <input type="text" class="form-control" id="txtnome" name="txtnome" required maxlength="100" value="<?php echo seguro($curso['nome']); ?>">
                </div>
                <div class="mb-3">
                    <label for="txtsigla" class="form-label">Sigla</label>
                    <input type="text" class="form-control" id="txtsigla" name="txtsigla" required maxlength="10" value="<?php echo seguro($curso['sigla']); ?>">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ckativo" name="ckativo" value="1" <?php if($curso['ativo']){ echo "checked"; } ?>>
                    <label class="form-check-label" for="ckativo">Curso ativo</label>
                </div>
                <input type="hidden" name="hidid" value="<?php echo $curso['id_curso']; ?>">
                <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                <a href="curso_lista.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
