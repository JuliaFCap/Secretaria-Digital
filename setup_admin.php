<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");

    if(existe_administrador($con)){
        header("location: index.php");
        exit;
    }

    if(!isset($_SESSION['setup_token'])){
        $_SESSION['setup_token'] = bin2hex(random_bytes(32));
    }

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $token = $_POST['csrf_token'] ?? "";
        $nome = $_POST['txtnome'] ?? "";
        $nome = is_string($nome) ? limpa($nome) : "";
        $login = $_POST['txtlogin'] ?? "";
        $login = is_string($login) ? strtolower(limpa($login)) : "";
        $senha = $_POST['txtsenha'] ?? "";
        $confirma = $_POST['txtconfirma'] ?? "";
        $erro = "";

        if(!is_string($token) || !hash_equals($_SESSION['setup_token'], $token)){
            $erro = "A sessão expirou. Atualize a página e tente novamente.";
        }
        else if(!is_string($senha) || !is_string($confirma)){
            $erro = "Informe uma senha válida.";
        }
        else if($nome == "" || strlen($nome) > 120){
            $erro = "Informe seu nome completo.";
        }
        else if(!preg_match("/^[a-z]+\.[a-z]+$/", $login) || strlen($login) > 50){
            $erro = "Use o padrão primeiro.sobrenome no login, somente com letras minúsculas.";
        }
        else if(strlen($senha) < 6 || strlen($senha) > 72){
            $erro = "A senha deve ter entre 6 e 72 bytes.";
        }
        else if($senha !== $confirma){
            $erro = "A confirmação não confere com a senha.";
        }

        if($erro != ""){
            $_SESSION['erro'] = $erro;
            header("location: setup_admin.php");
            exit;
        }

        $lockResult = mysqli_query($con, "SELECT GET_LOCK('secretaria_digital_setup_admin', 10) AS bloqueado");
        if($lockResult === false){
            $_SESSION['erro'] = "Não foi possível iniciar a configuração do administrador.";
        }
        else{
            $lock = mysqli_fetch_assoc($lockResult);
            if((int)$lock['bloqueado'] !== 1){
                $_SESSION['erro'] = "A configuração já está em andamento. Tente novamente.";
            }
            else{
                $criado = false;
                if(!mysqli_begin_transaction($con)){
                    $_SESSION['erro'] = "Não foi possível iniciar a configuração do administrador.";
                }
                else if(existe_administrador($con)){
                    mysqli_rollback($con);
                    $_SESSION['erro'] = "O administrador já foi configurado.";
                }
                else{
                    $hash = gera_hash($senha);
                    $email = $login . "@secretariadigital.local";
                    $nivel = "admin";
                    $sql = "INSERT INTO tb_usuarios (nome, login, senha, email, nivel)
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_stmt_init($con);
                    $inserido = mysqli_stmt_prepare($stmt, $sql) &&
                        mysqli_stmt_bind_param($stmt, "sssss", $nome, $login, $hash, $email, $nivel) &&
                        mysqli_stmt_execute($stmt) &&
                        mysqli_stmt_affected_rows($stmt) == 1;

                    if(!$inserido){
                        mysqli_rollback($con);
                        $_SESSION['erro'] = "Não foi possível criar o administrador. Verifique se o login já está cadastrado.";
                    }
                    else{
                        $id = mysqli_insert_id($con);
                        $acao = "configuracao_inicial";
                        $tabela = "tb_usuarios";
                        $detalhes = "Administrador criado no primeiro acesso";
                        $ip = $_SERVER['REMOTE_ADDR'] ?? "";
                        $sql = "INSERT INTO tb_auditoria (id_usuario, acao, tabela, id_registro, detalhes, ip)
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_stmt_init($con);
                        $auditado = mysqli_stmt_prepare($stmt, $sql) &&
                            mysqli_stmt_bind_param($stmt, "ississ", $id, $acao, $tabela, $id, $detalhes, $ip) &&
                            mysqli_stmt_execute($stmt) &&
                            mysqli_stmt_affected_rows($stmt) == 1;
                        if(!$auditado || !mysqli_commit($con)){
                            mysqli_rollback($con);
                            $_SESSION['erro'] = "Não foi possível concluir a configuração do administrador.";
                        }
                        else{
                            $criado = true;
                        }
                    }
                }
                mysqli_query($con, "SELECT RELEASE_LOCK('secretaria_digital_setup_admin')");
                if($criado){
                    session_regenerate_id(true);
                    $_SESSION['id_usuario'] = $id;
                    $_SESSION['nome'] = $nome;
                    $_SESSION['login'] = $login;
                    $_SESSION['nivel'] = "admin";
                    $_SESSION['id_curso'] = null;
                    unset($_SESSION['setup_token']);
                    header("location: painel.php");
                    exit;
                }
            }
        }
        header("location: setup_admin.php");
        exit;
    }

    $titulo = "Configuração inicial";
    require_once("cabecalho.php");
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="cartao">
            <h1 class="h3 mb-3">Configurar administrador</h1>
            <p class="text-secondary">Crie a primeira conta administrativa para começar a usar o sistema. O acesso será bloqueado depois desta configuração.</p>
            <form action="setup_admin.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo seguro($_SESSION['setup_token']); ?>">
                <div class="mb-3">
                    <label for="txtnome" class="form-label">Nome completo</label>
                    <input type="text" class="form-control" id="txtnome" name="txtnome" required maxlength="120" autocomplete="name">
                </div>
                <div class="mb-3">
                    <label for="txtlogin" class="form-label">Login</label>
                    <input type="text" class="form-control" id="txtlogin" name="txtlogin" required maxlength="50" pattern="[a-z]+\.[a-z]+" autocomplete="username">
                    <div class="form-text">Use primeiro.sobrenome em letras minúsculas. O e-mail será gerado como login@secretariadigital.local.</div>
                </div>
                <div class="mb-3">
                    <label for="txtsenha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="txtsenha" name="txtsenha" required minlength="6" maxlength="72" autocomplete="new-password">
                    <div class="form-text">Use de 6 a 72 bytes.</div>
                </div>
                <div class="mb-3">
                    <label for="txtconfirma" class="form-label">Confirme a senha</label>
                    <input type="password" class="form-control" id="txtconfirma" name="txtconfirma" required minlength="6" maxlength="72" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-principal">Criar administrador</button>
            </form>
        </div>
    </div>
</div>
<?php require_once("rodape.php"); ?>
