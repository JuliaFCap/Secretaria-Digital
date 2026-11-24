<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    if(!existe_administrador($con)){
        header("location: setup_admin.php");
        exit;
    }
    // usuário já logado vai direto para o painel
    if(isset($_SESSION['id_usuario'])){
        header("location: painel.php");
        exit;
    }
    // avisos publicados e dentro da validade
    $sql1 = "SELECT titulo, texto, imagem, criado_em FROM tb_avisos
             WHERE publicado = 1 AND (exibir_ate IS NULL OR exibir_ate >= CURDATE())
             ORDER BY criado_em DESC LIMIT 6";
    $res1 = mysqli_query($con, $sql1);
    $avisos = mysqli_fetch_all($res1, MYSQLI_ASSOC);
    // perguntas frequentes mais recentes
    $sql2 = "SELECT pergunta, resposta FROM tb_faq WHERE ativo = 1 ORDER BY atualizado_em DESC LIMIT 5";
    $res2 = mysqli_query($con, $sql2);
    $faqs = mysqli_fetch_all($res2, MYSQLI_ASSOC);
    $titulo = "Início";
    require_once("cabecalho.php");
?>
<div class="row g-4 align-items-stretch">
    <div class="col-lg-7">
        <div class="destaque h-100 d-flex flex-column justify-content-center">
            <span><span class="selo">Atendimento da Secretaria Acadêmica</span></span>
            <h1 class="mt-3">Secretaria Digital</h1>
            <p class="lead text-secondary mb-4">Abra requerimentos, acompanhe o andamento, envie os documentos do estágio, tire declarações na hora e consulte as dúvidas frequentes sem precisar ir até o balcão.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="faq.php" class="btn btn-contorno">Dúvidas frequentes</a>
                <a href="estagio_guia.php" class="btn btn-contorno">Guia do estágio</a>
                <a href="validar.php" class="btn btn-contorno">Validar declaração</a>
            </div>
        </div>
    </div>
    <div class="col-lg-5" id="entrar">
        <div class="cartao cartao-destaque">
            <h2 class="h4 mb-3">Entrar</h2>
            <form action="login.php" method="post">
                <div class="mb-3">
                    <label for="txtlogin" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="txtlogin" name="txtlogin" required maxlength="50" autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="txtsenha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="txtsenha" name="txtsenha" required autocomplete="current-password">
                </div>
                <input type="submit" name="btn" value="Entrar" class="btn btn-principal w-100">
            </form>
            <p class="small text-secondary mt-3 mb-0">Ainda não tem acesso? Procure a secretaria para fazer seu cadastro.</p>
        </div>
    </div>
</div>

<h2 class="h4 mt-5 mb-3">Mural de avisos</h2>
<?php if(count($avisos) == 0){ ?>
    <p class="text-secondary">Nenhum aviso publicado no momento.</p>
<?php } else { ?>
<div class="row g-3">
    <?php
        foreach($avisos as $aviso){
            echo "<div class='col-md-6 col-lg-4'><article class='aviso'>";
            if($aviso['imagem'] != ""){
                echo "<img src='uploads/avisos/" . seguro($aviso['imagem']) . "' alt=''>";
            }
            echo "<div class='corpo'>
                    <small class='text-secondary'>" . data_br($aviso['criado_em']) . "</small>
                    <h3 class='h6 fw-bold mt-1'>" . seguro($aviso['titulo']) . "</h3>
                    <p class='mb-0 small'>" . nl2br(seguro($aviso['texto'])) . "</p>
                  </div></article></div>";
        }
    ?>
</div>
<?php } ?>

<h2 class="h4 mt-5 mb-3">Dúvidas frequentes</h2>
<?php if(count($faqs) == 0){ ?>
    <p class="text-secondary">Nenhuma pergunta cadastrada ainda.</p>
<?php } else { ?>
<div class="accordion" id="faqinicio">
    <?php
        $i = 0;
        foreach($faqs as $faq){
            $i++;
            echo "<div class='accordion-item'>
                    <h3 class='accordion-header'>
                        <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#faq$i'>" . seguro($faq['pergunta']) . "</button>
                    </h3>
                    <div id='faq$i' class='accordion-collapse collapse' data-bs-parent='#faqinicio'>
                        <div class='accordion-body'>" . nl2br(seguro($faq['resposta'])) . "</div>
                    </div>
                  </div>";
        }
    ?>
</div>
<p class="mt-2"><a href="faq.php">Ver todas as perguntas</a></p>
<?php } ?>
<?php require_once("rodape.php"); ?>
