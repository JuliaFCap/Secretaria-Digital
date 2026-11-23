<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $ativo = isset($_POST['ckativo']) ? "1" : "0";
    $url = rtrim(trim($_POST['txturl'] ?? ""), "/");
    $instancia = trim($_POST['txtinstancia'] ?? "");
    $apikey = trim($_POST['txtapikey'] ?? "");
    if($url != "" && !filter_var($url, FILTER_VALIDATE_URL)){
        $_SESSION['erro'] = "Endereço da Evolution inválido. Use o formato https://servidor.com";
        header("location: whatsapp_config.php");
        exit;
    }
    if($instancia != "" && !preg_match("/^[A-Za-z0-9 _.-]+$/", $instancia)){
        $_SESSION['erro'] = "Nome da instância inválido.";
        header("location: whatsapp_config.php");
        exit;
    }
    if($ativo == "1" && ($url == "" || $instancia == "" || ($apikey == "" && busca_config($con, "evolution_apikey") == ""))){
        $_SESSION['erro'] = "Para ligar a integração, preencha o endereço, a instância e a API key.";
        header("location: whatsapp_config.php");
        exit;
    }
    salva_config($con, "whatsapp_ativo", $ativo);
    salva_config($con, "evolution_url", $url);
    salva_config($con, "evolution_instancia", $instancia);
    if($apikey != ""){
        salva_config($con, "evolution_apikey", $apikey);//só troca a chave se uma nova foi digitada
    }
    // chave secreta do webhook: criada uma vez, protege o endereço contra chamadas falsas
    if(busca_config($con, "whatsapp_webhook_chave") == ""){
        salva_config($con, "whatsapp_webhook_chave", bin2hex(random_bytes(16)));
    }
    salva_config($con, "whatsapp_ultimo_erro", "");
    registra_auditoria($con, "configuracao", "tb_configuracoes", null, "Integração WhatsApp (Evolution) " . ($ativo == "1" ? "ligada" : "desligada"));
    $_SESSION['msg'] = "Configuração do WhatsApp salva.";
    header("location: whatsapp_config.php");
?>
