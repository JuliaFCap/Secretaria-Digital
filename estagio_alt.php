<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno", "secretaria", "coordenacao"]);
    $id = (int)$_GET['id'];//prevenção de ataque
    if($id < 1){
        die("ID inválido");
    }
    $estagio = busca_estagio($con, $id);
    if(!pode_ver_estagio($estagio)){
        $_SESSION['erro'] = "Estágio não encontrado ou sem permissão de acesso.";
        header("location: estagio_lista.php");
        exit;
    }
    if($_SESSION['nivel'] == "aluno" && $estagio['situacao'] != "em_andamento"){
        $_SESSION['erro'] = "Estágio encerrado não pode mais ser editado pelo aluno.";
        header("location: estagio_ver.php?id=$id");
        exit;
    }
    $titulo = "Editar estágio";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="cartao">
            <h1 class="h3 mb-3">Editar estágio #<?php echo $id; ?></h1>
            <form action="estagio_alt2.php" method="post">
                <?php require("estagio_form.php"); ?>
                <input type="hidden" name="hidid" value="<?php echo $id; ?>">
                <div class="mt-4">
                    <input type="submit" name="btn" value="Salvar" class="btn btn-principal">
                    <a href="estagio_ver.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
