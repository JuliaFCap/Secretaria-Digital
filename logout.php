<?php
    session_start();
    require_once("conecta.php");
    require_once("funcoes.php");
    if(isset($_SESSION['id_usuario'])){
        registra_auditoria($con, "logout", "tb_usuarios", $_SESSION['id_usuario'], "Saiu do sistema");
    }
    session_unset();//limpa os dados da superglobal
    session_destroy();//destrói a sessão no servidor
    session_start();
    $_SESSION['msg'] = "Você saiu do sistema.";
    header("location: index.php");
?>
