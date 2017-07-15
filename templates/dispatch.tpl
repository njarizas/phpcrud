<?php
/**
 * Dispatch for table '${table_name}'.
 * @author: Nelson Ariza
 * @date: ${date}
 */
require_once ('include.php');
require_once ('class/${controller_folder}/${plural_class_name}Controller.php');

$accion="init";
if (isset($_POST["accion"])){
    $accion = ($_POST["accion"]);
}
$controlador=new ${plural_class_name}Controller();
$controlador->$accion();
$controlador->showView();