<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $sql = "SELECT * FROM tb_usuarios WHERE id_usuario = ? LIMIT 1";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_all($res, MYSQLI_ASSOC);
    if(count($dados) == 0){
        die("Usuário não encontrado");
    }
    $usuario = $dados[0];
    // secretaria só edita alunos
    if($_SESSION['nivel'] == "secretaria" && $usuario['nivel'] != "aluno"){
        $_SESSION['erro'] = "A secretaria só pode editar alunos.";
        header("location: usuario_lista.php");
        exit;
    }
    $sql = "SELECT id_curso, nome FROM tb_cursos WHERE ativo = 1 OR id_curso = ? ORDER BY nome ASC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "i", $usuario['id_curso']);
    mysqli_stmt_execute($stmt);
    $cursos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    $titulo = "Editar usuário";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar usuário</h1>
            <form action="usuario_alt2.php" method="post">
                <?php require("usuario_form.php"); ?>
                <input type="hidden" name="hidid" value="<?php echo $usuario['id_usuario']; ?>">
                <div class="mt-4">
                    <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                    <a href="usuario_lista.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
