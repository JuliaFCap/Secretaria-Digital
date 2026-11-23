<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin", "secretaria"]);
    $sql = "SELECT id_curso, nome FROM tb_cursos WHERE ativo = 1 ORDER BY nome ASC";
    $res = mysqli_query($con, $sql);
    $cursos = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $usuario = array();
    $titulo = "Novo usuário";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="cartao">
            <h1 class="h3 mb-3">Novo <?php echo ($_SESSION['nivel'] == "secretaria") ? "aluno" : "usuário"; ?></h1>
            <?php if(count($cursos) == 0){ echo "<div class='alert alert-warning'>Nenhum curso cadastrado ainda. Alunos e coordenadores precisam de um curso.</div>"; } ?>
            <form action="usuario_add2.php" method="post">
                <?php require("usuario_form.php"); ?>
                <div class="mt-4">
                    <input type="submit" name="btn" value="Cadastrar" class="btn btn-principal">
                    <a href="usuario_lista.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
