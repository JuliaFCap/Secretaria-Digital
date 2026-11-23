<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    verifica_nivel(["admin"]);
    $numero = telefone_whatsapp($_POST['txtnumero'] ?? "");
    if($numero == ""){
        $_SESSION['erro'] = "Celular inválido. Use DDD + número, ex.: (16) 99999-9999.";
        header("location: whatsapp_config.php");
        exit;
    }
    if(busca_config($con, "whatsapp_ativo") != "1"){
        $_SESSION['erro'] = "Ligue a integração e salve antes de testar.";
        header("location: whatsapp_config.php");
        exit;
    }
    if(envia_whatsapp($con, $numero, "Secretaria Digital FATEC: mensagem de teste da integração.")){
        registra_auditoria($con, "whatsapp_teste", "tb_configuracoes", null, "Teste enviado para $numero");
        $_SESSION['msg'] = "Mensagem de teste aceita pela API para $numero.";
    }
    else{
        $_SESSION['erro'] = "A API recusou o envio. Veja o último erro no quadro Situação.";
    }
    header("location: whatsapp_config.php");
?>
