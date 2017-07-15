<!DOCTYPE html>
<html lang="es" style="height:100%;">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width" />
        <title>SGE</title>
        <link rel="stylesheet" href="class/${view_folder}/css/bootstrap.min.css">
        <link rel="stylesheet" href="class/${view_folder}/css/formValidation.min.css"> 
        <script src="class/${view_folder}/js/jquery-1.12.0.min.js"></script>
        <script src="class/${view_folder}/js/bootstrap.min.js"></script>
        <script src="class/${view_folder}/js/formValidation.min.js"></script>
        <script src="class/${view_folder}/js/bootstrap.js"></script> 
        <script src="class/${view_folder}/js/es_ES.js"></script>
        <script src="class/${view_folder}/js/sweetalert-dev.js"></script>
        <link rel="stylesheet" href="class/${view_folder}/css/sweetalert.css">
        <!--.......................-->
    </head>
    <body style="height:100%;">
        <div class="container" style="background-color:#EEE; min-height: 100%;">
            <div class="col-md-10 col-md-offset-1" style="background-color:#FFF; min-height: 95%;">
                <header> 
                    <img class="img-responsive pull-left" src="class/${view_folder}/images/logo.png" height="60" width="60">
                    <h3 class="text-center">SISTEMA DE INFORMACIÓN</h3>
                </header>
                <?php
                include('menu.php');
                ?>
