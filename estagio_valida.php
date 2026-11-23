<?php
    // Lê e valida os campos do formulário de estágio (cadastro e edição)
    // Preenche $empresa, $cnpj... e $erro (vazio = tudo certo)
    if(!function_exists("limpa") || !isset($_POST['txtempresa'])){
        die("Acesso inválido");
    }
    $empresa = limpa($_POST['txtempresa']);
    $cnpj = limpa($_POST['txtcnpj'] ?? "");
    $supervisor = limpa($_POST['txtsupervisor'] ?? "");
    $email_sup = limpa($_POST['txtemailsup'] ?? "");
    $area = limpa($_POST['txtarea'] ?? "");
    $horas = (int)($_POST['numhoras'] ?? 0);
    $inicio = $_POST['dtinicio'] ?? "";
    $fim = $_POST['dtfim'] ?? "";
    $erro = "";
    $data_ok = "/^\d{4}-\d{2}-\d{2}$/";
    if($empresa == "" || $supervisor == ""){
        $erro = "Preencha a empresa e o(a) supervisor(a).";
    }
    else if($horas < 1 || $horas > 40){
        $erro = "Horas por semana inválidas.";
    }
    else if(!preg_match($data_ok, $inicio) || !preg_match($data_ok, $fim) || !strtotime($inicio) || !strtotime($fim)){
        $erro = "Informe as datas de início e término.";
    }
    else if(strtotime($fim) < strtotime($inicio)){
        $erro = "O término não pode ser antes do início.";
    }
    else if($email_sup != "" && !filter_var($email_sup, FILTER_VALIDATE_EMAIL)){
        $erro = "E-mail do(a) supervisor(a) inválido.";
    }
    $cnpj = ($cnpj != "") ? $cnpj : null;
    $email_sup = ($email_sup != "") ? $email_sup : null;
    $area = ($area != "") ? $area : null;
?>
