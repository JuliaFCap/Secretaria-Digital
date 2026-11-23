<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    $busca = limpa($_GET['txtbusca'] ?? "");
    if($busca != ""){
        $termo = "%" . $busca . "%";
        $sql = "SELECT pergunta, resposta, categoria FROM tb_faq
                WHERE ativo = 1 AND (pergunta LIKE ? OR resposta LIKE ? OR palavras_chave LIKE ?)
                ORDER BY categoria ASC, pergunta ASC";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $termo, $termo, $termo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
    }
    else{
        $sql = "SELECT pergunta, resposta, categoria FROM tb_faq WHERE ativo = 1 ORDER BY categoria ASC, pergunta ASC";
        $res = mysqli_query($con, $sql);
    }
    $faqs = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "Dúvidas frequentes";
    require_once("cabecalho.php");
?>
<h1>Dúvidas frequentes</h1>
<p class="text-secondary">Pesquise antes de abrir uma solicitação: muitas dúvidas já têm resposta aqui.</p>
<form action="" method="get" class="d-flex gap-2 mb-4" role="search">
    <label for="txtbusca" class="visually-hidden">Pesquisar dúvida</label>
    <input type="search" class="form-control" id="txtbusca" name="txtbusca" placeholder="Ex.: trancamento, rematrícula, estágio" value="<?php echo seguro($busca); ?>">
    <input type="submit" value="Buscar" class="btn btn-principal">
</form>
<?php
    if(count($faqs) == 0){
        echo "<p class='text-secondary'>Nenhuma pergunta encontrada.";
        if(isset($_SESSION['nivel']) && $_SESSION['nivel'] == "aluno"){
            echo " <a href='chamado_add.php'>Abra uma solicitação</a> para a secretaria.";
        }
        echo "</p>";
    }
    else{
        echo "<div class='accordion' id='listafaq'>";
        $i = 0;
        foreach($faqs as $faq){
            $i++;
            $categoria = $faq['categoria'] != "" ? "<span class='badge text-bg-light me-2'>" . seguro($faq['categoria']) . "</span>" : "";
            echo "<div class='accordion-item'>
                    <h2 class='accordion-header'>
                        <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#f$i'>$categoria" . seguro($faq['pergunta']) . "</button>
                    </h2>
                    <div id='f$i' class='accordion-collapse collapse' data-bs-parent='#listafaq'>
                        <div class='accordion-body'>" . nl2br(seguro($faq['resposta'])) . "</div>
                    </div>
                  </div>";
        }
        echo "</div>";
    }
    require_once("rodape.php");
?>
