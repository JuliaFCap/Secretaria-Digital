<?php
    mysqli_report(MYSQLI_REPORT_OFF);//erros tratados pelo próprio código (ex.: registro duplicado)
    $comp = "localhost";
    $user = "root";
    $senha = "";
    $db = "secretaria_digital";
    $con = mysqli_connect($comp, $user, $senha, $db);
    if(!$con){
        die("Erro na conexão: " . mysqli_connect_error());
    }
    mysqli_set_charset($con, "utf8mb4");
?>
