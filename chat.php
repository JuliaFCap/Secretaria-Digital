<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["secretaria", "coordenacao", "admin"]);
    $eu = $_SESSION['id_usuario'];
    $com = (int)($_GET['com'] ?? 0);//0 = canal geral da equipe
    // pessoas da equipe (menos eu) com a quantidade de mensagens não lidas de cada uma
    $sql = "SELECT u.id_usuario, u.nome, u.nivel, cu.sigla,
                   (SELECT COUNT(*) FROM tb_chat c WHERE c.id_remetente = u.id_usuario AND c.id_destinatario = ? AND c.lida = 0) AS nao_lidas
            FROM tb_usuarios u LEFT JOIN tb_cursos cu ON cu.id_curso = u.id_curso
            WHERE u.nivel IN ('secretaria','coordenacao','admin') AND u.ativo = 1 AND u.id_usuario <> ?
            ORDER BY nao_lidas DESC, u.nome ASC";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $eu, $eu);
    mysqli_stmt_execute($stmt);
    $equipe = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    // a pessoa escolhida precisa ser da equipe
    $nome_conversa = "Geral da equipe";
    if($com > 0){
        $achou = false;
        foreach($equipe as $p){
            if($p['id_usuario'] == $com){
                $achou = true;
                $nome_conversa = $p['nome'] . " · " . nome_nivel($p['nivel']) . ($p['sigla'] ? " " . $p['sigla'] : "");
            }
        }
        if(!$achou){
            $_SESSION['erro'] = "Conversa não encontrada.";
            header("location: chat.php");
            exit;
        }
    }
    // não lidas do canal geral
    $sql = "SELECT COUNT(*) AS qtd FROM tb_chat WHERE id_destinatario IS NULL AND id_remetente <> ?
            AND id_chat > COALESCE((SELECT ultimo_id FROM tb_chat_geral_lido WHERE id_usuario = ?), 0)";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $eu, $eu);
    mysqli_stmt_execute($stmt);
    $geral_nao_lidas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['qtd'];

    $mensagens = chat_mensagens($con, $eu, $com, 0);
    chat_marca_lida($con, $eu, $com);
    if($com == 0){
        $geral_nao_lidas = 0;
    }
    $ultimo_id = count($mensagens) > 0 ? end($mensagens)['id_chat'] : 0;
    $titulo = "Chat da equipe";
    require_once("cabecalho.php");
?>
<h1>Chat da equipe</h1>
<p class="text-secondary">Conversa interna entre secretaria, coordenação e administração. Os alunos não veem. Escreva <code>#15</code> para criar um link para o chamado 15.</p>
<div class="chat">
    <nav class="chat-contatos" aria-label="Conversas">
        <a href="chat.php" class="<?php echo $com == 0 ? 'ativo' : ''; ?>">
            <span><strong>Geral da equipe</strong><small>Todos da equipe</small></span>
            <?php if($geral_nao_lidas > 0){ echo "<span class='badge-menu'>$geral_nao_lidas</span>"; } ?>
        </a>
        <?php
            foreach($equipe as $p){
                $ativo = ($com == $p['id_usuario']) ? "ativo" : "";
                $badge = ($p['nao_lidas'] > 0 && $com != $p['id_usuario']) ? "<span class='badge-menu'>" . $p['nao_lidas'] . "</span>" : "";
                echo "<a href='chat.php?com=" . $p['id_usuario'] . "' class='$ativo'>
                        <span><strong>" . seguro($p['nome']) . "</strong><small>" . nome_nivel($p['nivel']) . ($p['sigla'] ? " · " . seguro($p['sigla']) : "") . "</small></span>$badge
                      </a>";
            }
            if(count($equipe) == 0){
                echo "<p class='small text-secondary p-3 mb-0'>Ninguém mais da equipe cadastrado.</p>";
            }
        ?>
    </nav>
    <section class="chat-conversa" aria-label="Mensagens">
        <div class="chat-topo"><?php echo seguro($nome_conversa); ?></div>
        <div class="chat-mensagens" id="mensagens" aria-live="polite">
            <?php
                if(count($mensagens) == 0){
                    echo "<p class='text-secondary small text-center my-auto' id='vazio'>Nenhuma mensagem ainda. Comece a conversa!</p>";
                }
                foreach($mensagens as $m){
                    $meu = ($m['id_remetente'] == $eu) ? " meu" : "";
                    echo "<div class='balao$meu'><small>" . seguro($m['nome']) . " · " . data_br($m['criado_em'], true) . "</small>" . chat_formata($m['mensagem']) . "</div>";
                }
            ?>
        </div>
        <form action="chat_envia2.php" method="post" class="chat-form">
            <label for="txtmensagem" class="visually-hidden">Mensagem</label>
            <textarea class="form-control" id="txtmensagem" name="txtmensagem" rows="1" maxlength="1000" required placeholder="Escreva uma mensagem… (Enter envia, Shift+Enter quebra linha)"></textarea>
            <input type="hidden" name="hidcom" value="<?php echo $com; ?>">
            <input type="submit" name="btn" value="Enviar" class="btn btn-principal">
        </form>
    </section>
</div>
<script>
    // rola para a última mensagem
    var caixa = document.getElementById("mensagens");
    caixa.scrollTop = caixa.scrollHeight;
    // Enter envia, Shift+Enter quebra linha
    var campo = document.getElementById("txtmensagem");
    campo.focus();
    campo.addEventListener("keydown", function(e){
        if(e.key == "Enter" && !e.shiftKey){
            e.preventDefault();
            if(campo.value.trim() != ""){ campo.form.submit(); }
        }
    });
    // busca mensagens novas a cada 4 segundos, sem recarregar a página
    var ultimo = <?php echo (int)$ultimo_id; ?>;
    var com = <?php echo (int)$com; ?>;
    function adicionaMensagem(m){
        var vazio = document.getElementById("vazio");
        if(vazio){ vazio.remove(); }
        var div = document.createElement("div");
        div.className = "balao" + (m.meu ? " meu" : "");
        var info = document.createElement("small");
        info.textContent = m.nome + " · " + m.data;
        div.appendChild(info);
        // texto montado com textContent (seguro contra XSS); "#15" vira link para o chamado 15
        m.mensagem.split(/(#\d{1,9})\b/).forEach(function(parte){
            if(/^#\d+$/.test(parte)){
                var a = document.createElement("a");
                a.href = "chamado_ver.php?id=" + parte.substring(1);
                a.textContent = parte;
                div.appendChild(a);
            }
            else{
                parte.split("\n").forEach(function(linha, i){
                    if(i > 0){ div.appendChild(document.createElement("br")); }
                    div.appendChild(document.createTextNode(linha));
                });
            }
        });
        caixa.appendChild(div);
        caixa.scrollTop = caixa.scrollHeight;
    }
    setInterval(function(){
        fetch("chat_novas.php?com=" + com + "&depois=" + ultimo)
            .then(function(r){ return r.json(); })
            .then(function(lista){
                lista.forEach(function(m){ adicionaMensagem(m); ultimo = m.id; });
            })
            .catch(function(){});
    }, 4000);
</script>
<?php require_once("rodape.php"); ?>
