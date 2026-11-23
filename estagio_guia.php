<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    // página pública: qualquer pessoa vê o passo a passo e baixa os modelos
    $sql = "SELECT nome, descricao, ordem, prazo_dias, obrigatoria, modelo, modelo_nome
            FROM tb_etapas WHERE ativo = 1 ORDER BY ordem ASC, id_etapa ASC";
    $etapas = mysqli_fetch_all(mysqli_query($con, $sql), MYSQLI_ASSOC);
    // perguntas da FAQ sobre estágio
    $sql = "SELECT pergunta, resposta FROM tb_faq
            WHERE ativo = 1 AND (categoria LIKE '%estágio%' OR categoria LIKE '%estagio%' OR palavras_chave LIKE '%estágio%' OR palavras_chave LIKE '%estagio%')
            ORDER BY pergunta ASC";
    $faqs = mysqli_fetch_all(mysqli_query($con, $sql), MYSQLI_ASSOC);
    $nivel = $_SESSION['nivel'] ?? "";
    $titulo = "Guia do estágio";
    require_once("cabecalho.php");
?>
<h1>Guia do estágio</h1>
<p class="text-secondary">Passo a passo da documentação do estágio supervisionado. Baixe os modelos, preencha e envie cada documento pela área <strong>Estágio</strong> do sistema.</p>
<?php if($nivel == "aluno"){ ?>
    <a href="estagio_lista.php" class="btn btn-principal mb-4">Ir para o meu estágio</a>
<?php } else if($nivel == ""){ ?>
    <p class="mb-4"><a href="index.php#entrar">Entre no sistema</a> para enviar os seus documentos.</p>
<?php } ?>

<?php if(count($etapas) == 0){ ?>
    <div class="alert alert-secondary">A secretaria ainda não cadastrou as etapas do estágio.</div>
<?php } else { ?>
<div class="d-flex flex-column gap-3 mb-4">
    <?php
        $n = 0;
        foreach($etapas as $e){
            $n++;
            $prazo = $e['prazo_dias'] !== null ? "Entregar até " . (int)$e['prazo_dias'] . " dia(s) após o início do estágio" : "Sem prazo fixo";
            $obrig = $e['obrigatoria'] ? "Obrigatória" : "Opcional";
            echo "<div class='etapa d-flex gap-3'>
                    <span class='numero-etapa'>$n</span>
                    <div class='flex-grow-1'>
                        <h2 class='h6 fw-bold mb-1'>" . seguro($e['nome']) . "</h2>
                        <div class='small text-secondary mb-2'>$obrig · $prazo</div>
                        <p class='mb-2'>" . nl2br(seguro($e['descricao'])) . "</p>";
            if($e['modelo']){
                echo "<a class='btn btn-sm btn-contorno' href='uploads/modelos/" . seguro($e['modelo']) . "' download>Baixar modelo: " . seguro($e['modelo_nome']) . "</a>";
            }
            echo "  </div>
                  </div>";
        }
    ?>
</div>
<?php } ?>

<?php if(count($faqs) > 0){ ?>
<h2 class="h4">Dúvidas sobre estágio</h2>
<div class="accordion mb-4" id="faqestagio">
    <?php
        $i = 0;
        foreach($faqs as $f){
            $i++;
            echo "<div class='accordion-item'>
                    <h3 class='accordion-header'><button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#fe$i'>" . seguro($f['pergunta']) . "</button></h3>
                    <div id='fe$i' class='accordion-collapse collapse' data-bs-parent='#faqestagio'><div class='accordion-body'>" . nl2br(seguro($f['resposta'])) . "</div></div>
                  </div>";
        }
    ?>
</div>
<?php } ?>
<?php require_once("rodape.php"); ?>
