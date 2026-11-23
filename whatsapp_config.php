<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $ativo = busca_config($con, "whatsapp_ativo");
    $url = busca_config($con, "evolution_url");
    $instancia = busca_config($con, "evolution_instancia");
    $tem_apikey = busca_config($con, "evolution_apikey") != "";
    $chave = busca_config($con, "whatsapp_webhook_chave");
    $ultimo_erro = busca_config($con, "whatsapp_ultimo_erro");
    // situação da instância na Evolution (open = conectada)
    $estado = ($url != "" && $instancia != "" && $tem_apikey) ? estado_evolution($con) : "";
    // últimos envios por WhatsApp
    $sql = "SELECT n.criado_em, n.mensagem, n.enviada, u.nome
            FROM tb_notificacoes n INNER JOIN tb_usuarios u ON u.id_usuario = n.id_usuario
            WHERE n.canal = 'whatsapp' ORDER BY n.id_notificacao DESC LIMIT 15";
    $envios = mysqli_fetch_all(mysqli_query($con, $sql), MYSQLI_ASSOC);
    $res = mysqli_query($con, "SELECT COUNT(*) AS qtd FROM tb_usuarios WHERE aviso_whatsapp = 1 AND ativo = 1 AND telefone <> ''");
    $qtd_usuarios = mysqli_fetch_assoc($res)['qtd'];
    // endereço do webhook (para cadastrar na instância da Evolution)
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    $webhook = $protocolo . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . "/whatsapp_webhook.php?chave=" . $chave;
    $titulo = "Integração WhatsApp";
    require_once("cabecalho.php");
?>
<h1>Integração com o WhatsApp</h1>
<p class="text-secondary">O sistema envia as mensagens pela <strong>Evolution API</strong>, usando a instância conectada ao WhatsApp da secretaria. Com a integração ligada, toda notificação do sistema também vai por WhatsApp para quem ativou essa opção em <strong>Meus dados</strong>. Hoje <?php echo (int)$qtd_usuarios; ?> usuário(s) ativaram.</p>

<div class="row g-4">
    <div class="col-lg-7">
        <form action="whatsapp_config2.php" method="post" class="cartao">
            <h2 class="h5">Configuração</h2>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="ckativo" name="ckativo" value="1" <?php if($ativo == "1"){ echo "checked"; } ?>>
                <label class="form-check-label fw-semibold" for="ckativo">Integração ligada</label>
            </div>
            <div class="mb-3">
                <label for="txturl" class="form-label">Endereço da Evolution API</label>
                <input type="url" class="form-control" id="txturl" name="txturl" maxlength="150" value="<?php echo seguro($url); ?>" placeholder="Ex.: https://evolution.seudominio.com">
            </div>
            <div class="mb-3">
                <label for="txtinstancia" class="form-label">Nome da instância</label>
                <input type="text" class="form-control" id="txtinstancia" name="txtinstancia" maxlength="80" value="<?php echo seguro($instancia); ?>" placeholder="Ex.: secretaria-fatec">
            </div>
            <div class="mb-3">
                <label for="txtapikey" class="form-label">API key</label>
                <input type="password" class="form-control" id="txtapikey" name="txtapikey" autocomplete="off" placeholder="<?php echo $tem_apikey ? "Chave salva. Deixe em branco para manter." : "Cole a apikey da instância ou a global"; ?>">
            </div>
            <input type="submit" name="btn" value="Salvar configuração" class="btn btn-principal">
        </form>

        <form action="whatsapp_teste2.php" method="post" class="cartao mt-3">
            <h2 class="h5">Enviar mensagem de teste</h2>
            <div class="d-flex flex-wrap gap-2">
                <label for="txtnumero" class="visually-hidden">Celular</label>
                <input type="text" class="form-control" style="max-width: 260px" id="txtnumero" name="txtnumero" required placeholder="(16) 99999-9999">
                <input type="submit" name="btn" value="Enviar teste" class="btn btn-contorno">
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="cartao mb-3">
            <h2 class="h5">Situação</h2>
            <p class="mb-1">Integração: <?php echo $ativo == "1" ? "<span class='status status-deferido'>Ligada</span>" : "<span class='status status-concluido'>Desligada</span>"; ?></p>
            <?php
                if($estado != ""){
                    $classe = ($estado == "open") ? "status-deferido" : "status-indeferido";
                    $texto = ($estado == "open") ? "Conectada ao WhatsApp" : "Não conectada (" . seguro($estado) . ")";
                    echo "<p class='mb-1'>Instância: <span class='status $classe'>$texto</span></p>";
                    if($estado != "open"){
                        echo "<p class='small text-secondary mb-0'>Abra o painel da Evolution e leia o QR Code da instância com o WhatsApp da secretaria.</p>";
                    }
                }
                if($ultimo_erro != ""){
                    echo "<div class='alert alert-warning small mt-2 mb-0'><strong>Último erro:</strong><br>" . seguro($ultimo_erro) . "</div>";
                }
            ?>
        </div>
        <div class="cartao mb-3">
            <h2 class="h5">Consulta pelo WhatsApp</h2>
            <p class="small mb-2">O aluno manda <code>status 15</code> para o WhatsApp da secretaria e recebe o andamento do chamado 15. Só funciona para o celular cadastrado do próprio aluno.</p>
            <p class="small mb-1">Na instância da Evolution, ative o <strong>Webhook</strong> com este endereço e marque o evento <strong>MESSAGES_UPSERT</strong>:</p>
            <code class="small d-block text-break"><?php echo seguro($webhook); ?></code>
            <p class="small text-secondary mt-2 mb-0">A Evolution precisa conseguir acessar esse endereço. Se ela estiver em um servidor na internet e o sistema no seu computador, use um túnel como o ngrok.</p>
        </div>
        <div class="cartao">
            <h2 class="h5">Últimos envios</h2>
            <?php
                if(count($envios) == 0){
                    echo "<p class='small text-secondary mb-0'>Nenhum envio ainda.</p>";
                }
                foreach($envios as $e){
                    $sit = $e['enviada'] ? "<span class='text-success fw-semibold'>enviado</span>" : "<span class='text-danger fw-semibold'>falhou</span>";
                    echo "<div class='small border-bottom py-1'>" . data_br($e['criado_em'], true) . " · " . seguro($e['nome']) . " · $sit<br><span class='text-secondary'>" . seguro($e['mensagem']) . "</span></div>";
                }
            ?>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
