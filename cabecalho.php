<?php
    // Cabeçalho único, usado em todas as páginas do sistema
    // Antes de importar, a página define $titulo
    if(!isset($titulo)){
        $titulo = "Secretaria Digital";
    }
    $nivel_menu = $_SESSION['nivel'] ?? "";
    $pagina_atual = basename($_SERVER['PHP_SELF']);
    $qtd_notif = 0;
    $qtd_chat = 0;
    if(isset($_SESSION['id_usuario']) && isset($con)){
        // notificações não lidas
        $sql_n = "SELECT COUNT(*) AS qtd FROM tb_notificacoes WHERE id_usuario = ? AND lida = 0 AND canal = 'sistema'";
        $stmt_n = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt_n, $sql_n);
        mysqli_stmt_bind_param($stmt_n, "i", $_SESSION['id_usuario']);
        mysqli_stmt_execute($stmt_n);
        $qtd_notif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_n))['qtd'];
        // mensagens do chat da equipe ainda não lidas
        if($nivel_menu != "aluno"){
            $qtd_chat = chat_nao_lidas($con, $_SESSION['id_usuario']);
        }
    }
    function item_menu($arquivo, $rotulo, $pagina_atual){
        $ativo = ($arquivo == $pagina_atual) ? " active" : "";
        return "<li class='nav-item'><a class='nav-link$ativo' href='$arquivo'>$rotulo</a></li>";
    }
    // menu suspenso: $itens = array("arquivo.php" => "Rótulo")
    function grupo_menu($rotulo, $itens, $pagina_atual){
        $ativo = array_key_exists($pagina_atual, $itens) ? " active" : "";
        $html = "<li class='nav-item dropdown'>
                    <a class='nav-link dropdown-toggle$ativo' href='#' role='button' data-bs-toggle='dropdown' aria-expanded='false'>$rotulo</a>
                    <ul class='dropdown-menu'>";
        foreach($itens as $arquivo => $nome){
            $html .= "<li><a class='dropdown-item' href='$arquivo'>$nome</a></li>";
        }
        return $html . "</ul></li>";
    }
    $rotulo_chat = "Chat" . ($qtd_chat > 0 ? "<span class='badge-menu' aria-label='$qtd_chat não lidas'>$qtd_chat</span>" : "");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo seguro($titulo); ?> | Secretaria Digital</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
<div class="faixa-topo">
    <div class="container">
        <span>Fatec Araraquara · Faculdade de Tecnologia</span>
        <span class="d-none d-md-inline"><a href="faq.php">Dúvidas frequentes</a> · <a href="validar.php">Validar documento</a></span>
    </div>
</div>
<header class="cabecalho">
    <nav class="navbar navbar-expand-xl">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $nivel_menu ? 'painel.php' : 'index.php'; ?>">
                <span class="logo">SD</span>
                <span class="marca"><small>Fatec Araraquara</small>Secretaria Digital</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav me-auto ms-xl-3">
                <?php
                    if($nivel_menu == ""){
                        echo item_menu("index.php", "Início", $pagina_atual);
                        echo item_menu("faq.php", "Dúvidas frequentes", $pagina_atual);
                        echo item_menu("estagio_guia.php", "Guia do estágio", $pagina_atual);
                        echo item_menu("validar.php", "Validar documento", $pagina_atual);
                    }
                    else if($nivel_menu == "aluno"){
                        echo item_menu("painel.php", "Início", $pagina_atual);
                        echo item_menu("chamado_add.php", "Nova solicitação", $pagina_atual);
                        echo item_menu("chamado_lista.php", "Meus chamados", $pagina_atual);
                        echo item_menu("estagio_lista.php", "Estágio", $pagina_atual);
                        echo item_menu("declaracao_lista.php", "Declarações", $pagina_atual);
                        echo item_menu("faq.php", "Dúvidas", $pagina_atual);
                    }
                    else if($nivel_menu == "secretaria"){
                        echo item_menu("painel.php", "Painel", $pagina_atual);
                        echo item_menu("kanban.php", "Kanban", $pagina_atual);
                        echo item_menu("chamado_lista.php", "Chamados", $pagina_atual);
                        echo item_menu("estagio_lista.php", "Estágios", $pagina_atual);
                        echo item_menu("usuario_lista.php", "Alunos", $pagina_atual);
                        echo item_menu("declaracao_lista.php", "Declarações", $pagina_atual);
                        echo item_menu("chat.php", $rotulo_chat, $pagina_atual);
                        echo grupo_menu("Mais", array(
                            "aviso_lista.php" => "Mural de avisos",
                            "faq_lista.php" => "FAQ",
                            "resposta_lista.php" => "Respostas prontas",
                            "etapa_lista.php" => "Etapas do estágio",
                            "relatorios.php" => "Relatórios"
                        ), $pagina_atual);
                    }
                    else if($nivel_menu == "coordenacao"){
                        echo item_menu("painel.php", "Pendências", $pagina_atual);
                        echo item_menu("chamado_lista.php", "Chamados do curso", $pagina_atual);
                        echo item_menu("estagio_lista.php", "Estágios", $pagina_atual);
                        echo item_menu("chat.php", $rotulo_chat, $pagina_atual);
                        echo item_menu("relatorios.php", "Relatórios", $pagina_atual);
                    }
                    else if($nivel_menu == "admin"){
                        echo item_menu("painel.php", "Painel", $pagina_atual);
                        echo item_menu("kanban.php", "Kanban", $pagina_atual);
                        echo item_menu("chamado_lista.php", "Chamados", $pagina_atual);
                        echo item_menu("estagio_lista.php", "Estágios", $pagina_atual);
                        echo item_menu("chat.php", $rotulo_chat, $pagina_atual);
                        echo grupo_menu("Cadastros", array(
                            "usuario_lista.php" => "Usuários",
                            "curso_lista.php" => "Cursos",
                            "tipo_lista.php" => "Tipos de requerimento",
                            "etapa_lista.php" => "Etapas do estágio",
                            "faq_lista.php" => "FAQ",
                            "resposta_lista.php" => "Respostas prontas",
                            "aviso_lista.php" => "Mural de avisos",
                            "whatsapp_config.php" => "Integração WhatsApp"
                        ), $pagina_atual);
                        echo item_menu("relatorios.php", "Relatórios", $pagina_atual);
                        echo item_menu("auditoria_lista.php", "Auditoria", $pagina_atual);
                    }
                ?>
                </ul>
                <?php if($nivel_menu != ""){ ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="painel.php#notificacoes" class="sino" aria-label="Notificações: <?php echo $qtd_notif; ?> não lidas">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>
                            <?php if($qtd_notif > 0){ echo "<span class='badge-notif'>$qtd_notif</span>"; } ?>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-usuario dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="text-start"><?php echo seguro($_SESSION['nome']); ?><small class="d-block"><?php echo nome_nivel($nivel_menu); ?></small></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="perfil_alt.php">Meus dados</a></li>
                                <li><a class="dropdown-item" href="senha_alt.php">Trocar senha</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                            </ul>
                        </div>
                    </div>
                <?php } else { ?>
                    <a href="index.php#entrar" class="btn btn-principal">Entrar</a>
                <?php } ?>
            </div>
        </div>
    </nav>
</header>
<main class="container py-4">
<?php exibe_msg(); ?>
