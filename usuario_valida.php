<?php
    // Lê e valida os campos do formulário de usuário (cadastro e edição)
    // Preenche as variáveis $nome, $login, $email... e $erro (vazio = tudo certo)
    if(!function_exists("limpa") || !isset($_POST['txtnome'])){
        die("Acesso inválido");
    }
    $nome = $_POST['txtnome'] ?? "";
    $nome = is_string($nome) ? limpa($nome) : "";
    $login = $_POST['txtlogin'] ?? "";
    $login = is_string($login) ? strtolower(limpa($login)) : "";
    $senha_nova = $_POST['txtsenha'] ?? "";
    $senha_valida = is_string($senha_nova);
    if(!$senha_valida){
        $senha_nova = "";
    }
    $email = $login . "@secretariadigital.local";
    $telefone = limpa($_POST['txttelefone'] ?? "");
    $curso = (int)($_POST['selcurso'] ?? 0);
    $ra = limpa($_POST['txtra'] ?? "");
    $semestre = (int)($_POST['numsemestre'] ?? 0);
    $whats = isset($_POST['ckwhats']) ? 1 : 0;
    // secretaria só cadastra aluno; admin escolhe o nível
    if($_SESSION['nivel'] == "admin"){
        $nivel = $_POST['selnivel'] ?? "";
    }
    else{
        $nivel = "aluno";
    }
    $erro = "";
    if(!$senha_valida){
        $erro = "Senha inválida.";
    }
    else if($nome == "" || $login == "" || $email == ""){
        $erro = "Preencha nome, login e e-mail.";
    }
    else if(!preg_match("/^[a-z]+\.[a-z]+$/", $login)){
        $erro = "Use o padrão primeiro.sobrenome no login, somente com letras minúsculas.";
    }
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $erro = "E-mail inválido.";
    }
    else if($telefone == "" || strlen($telefone) > 20){
        $erro = "Informe um telefone com até 20 caracteres.";
    }
    else if(!in_array($nivel, array("aluno", "secretaria", "coordenacao", "admin"))){
        $erro = "Nível inválido.";
    }
    else if(($nivel == "aluno" || $nivel == "coordenacao") && $curso < 1){
        $erro = "Aluno e coordenação precisam de um curso.";
    }
    else if($nivel == "aluno" && ($ra == "" || strlen($ra) > 20)){
        $erro = "Informe um RA com até 20 caracteres.";
    }
    else if($nivel == "aluno" && ($semestre < 1 || $semestre > 10)){
        $erro = "Informe um semestre entre 1 e 10.";
    }
    else if($senha_nova != "" && strlen($senha_nova) < 6){
        $erro = "A senha precisa ter pelo menos 6 caracteres.";
    }
    else if(strlen($senha_nova) > 72){
        $erro = "A senha pode ter no máximo 72 bytes.";
    }
    // campos que não se aplicam ao nível ficam nulos no banco
    $curso_banco = ($curso > 0 && ($nivel == "aluno" || $nivel == "coordenacao")) ? $curso : null;
    $ra_banco = ($nivel == "aluno" && $ra != "") ? $ra : null;
    $semestre_banco = ($nivel == "aluno" && $semestre > 0) ? $semestre : null;
?>
