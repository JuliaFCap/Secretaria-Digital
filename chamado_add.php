<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["aluno"]);
    $sql = "SELECT id_tipo, nome, descricao, documentos_exigidos, prazo_dias, precisa_coordenacao, exige_anexo
            FROM tb_tipos WHERE ativo = 1 ORDER BY nome ASC";
    $res = mysqli_query($con, $sql);
    $tipos = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $titulo = "Nova solicitação";
    require_once("cabecalho.php");
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="cartao">
            <h1 class="h3">Nova solicitação</h1>
            <p class="text-secondary">Escolha o tipo: o sistema mostra o prazo e os documentos que você precisa anexar.</p>
            <?php if(count($tipos) == 0){ ?>
                <div class="alert alert-warning">Nenhum tipo de requerimento disponível no momento.</div>
            <?php } else { ?>
            <form action="chamado_add2.php" method="post" enctype="multipart/form-data">
                <fieldset class="mb-3">
                    <legend class="form-label fs-6">1. Tipo de requerimento</legend>
                    <div class="row g-2">
                        <?php
                            foreach($tipos as $t){
                                $quem = $t['precisa_coordenacao'] ? "Passa pela coordenação" : "Secretaria";
                                $anexo = $t['exige_anexo'] ? "anexo obrigatório" : "anexo opcional";
                                echo "<div class='col-md-6'>
                                        <label class='cartao d-flex gap-2 p-3 h-100' style='cursor:pointer'>
                                            <input type='radio' name='rdtipo' value='" . $t['id_tipo'] . "' required class='form-check-input mt-1'
                                                data-prazo='" . (int)$t['prazo_dias'] . "'
                                                data-docs='" . seguro($t['documentos_exigidos']) . "'
                                                data-quem='$quem' data-anexo='" . $t['exige_anexo'] . "'>
                                            <span><strong>" . seguro($t['nome']) . "</strong>
                                            <span class='d-block small text-secondary'>$quem · $anexo</span>
                                            <span class='d-block small text-secondary'>" . seguro($t['descricao']) . "</span></span>
                                        </label>
                                      </div>";
                            }
                        ?>
                    </div>
                    <div id="infotipo" class="alert alert-primary mt-3 mb-0 d-none"></div>
                </fieldset>
                <div class="mb-3">
                    <label for="txtassunto" class="form-label">2. Assunto</label>
                    <input type="text" class="form-control" id="txtassunto" name="txtassunto" required maxlength="150" placeholder="Ex.: Aproveitamento de Algoritmos (cursei na ETEC)">
                </div>
                <div class="mb-3">
                    <label for="txtdescricao" class="form-label">3. Descreva o pedido</label>
                    <textarea class="form-control" id="txtdescricao" name="txtdescricao" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="uplanexo" class="form-label">4. Anexo <span id="obrigatorio" class="text-danger d-none">(obrigatório para este tipo)</span></label>
                    <input type="file" class="form-control" id="uplanexo" name="uplanexo" accept=".pdf,.png,.jpg,.jpeg">
                    <div class="form-text">PDF, PNG ou JPG de até 2 MB. Você pode mandar mais arquivos depois, dentro do chamado.</div>
                </div>
                <input type="submit" name="btn" value="Enviar solicitação" class="btn btn-principal">
                <a href="painel.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
            <?php } ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="cartao mb-3">
            <h2 class="h5">O que acontece depois</h2>
            <ol class="mb-0 small">
                <li>Você recebe o número do chamado.</li>
                <li>A secretaria confere os documentos.</li>
                <li>Se o tipo exigir, a coordenação dá o parecer.</li>
                <li>Você é avisado de cada mudança no painel.</li>
            </ol>
        </div>
        <div class="cartao">
            <h2 class="h5">Antes de enviar</h2>
            <p class="small mb-2">Sua dúvida pode já ter resposta.</p>
            <a href="faq.php" class="btn btn-sm btn-contorno">Ver dúvidas frequentes</a>
        </div>
    </div>
</div>
<script>
    // mostra prazo e documentos do tipo escolhido
    document.querySelectorAll("input[name='rdtipo']").forEach(function(radio){
        radio.addEventListener("change", function(){
            var info = document.getElementById("infotipo");
            info.classList.remove("d-none");
            info.textContent = "Prazo: até " + radio.dataset.prazo + " dia(s) · Quem analisa: " + radio.dataset.quem +
                (radio.dataset.docs ? " · Anexar: " + radio.dataset.docs : "");
            var anexo = document.getElementById("uplanexo");
            anexo.required = (radio.dataset.anexo == "1");
            document.getElementById("obrigatorio").classList.toggle("d-none", radio.dataset.anexo != "1");
        });
    });
</script>
<?php require_once("rodape.php"); ?>
