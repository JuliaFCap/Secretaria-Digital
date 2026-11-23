<?php
    // Lê e valida os campos do formulário de usuário (cadastro e edição)
    // Preenche as variáveis $nome, $login, $email... e $erro (vazio = tudo certo)
    if(!function_exists("limpa") || !isset($_POST['txtnome'])){
        die("Acesso inválido");
    }
    $nome = limpa($_POST['txtnome']);
    $login = strtolower(limpa($_POST['txtlogin'] ?? ""));
    $senha_nova = $_POST['txtsenha'] ?? "";
    $email = limpa($_POST['txtemail'] ?? "");
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
    if($nome == "" || $login == "" || $email == ""){
        $erro = "Preencha nome, login e e-mail.";
    }
    else if(!preg_match("/^[a-z0-9._-]+$/", $login)){
        $erro = "O login só pode ter letras, números, ponto, hífen ou sublinhado.";
    }
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $erro = "E-mail inválido.";
    }
    else if(!in_array($nivel, array("aluno", "secretaria", "coordenacao", "admin"))){
        $erro = "Nível inválido.";
    }
    else if(($nivel == "aluno" || $nivel == "coordenacao") && $curso < 1){
        $erro = "Aluno e coordenação precisam de um curso.";
    }
    else if($nivel == "aluno" && $ra == ""){
        $erro = "Informe o RA do aluno.";
    }
    else if($senha_nova != "" && strlen($senha_nova) < 6){
        $erro = "A senha precisa ter pelo menos 6 caracteres.";
    }
    // campos que não se aplicam ao nível ficam nulos no banco
    $curso_banco = ($curso > 0 && ($nivel == "aluno" || $nivel == "coordenacao")) ? $curso : null;
    $ra_banco = ($nivel == "aluno" && $ra != "") ? $ra : null;
    $semestre_banco = ($nivel == "aluno" && $semestre > 0) ? $semestre : null;
?>
