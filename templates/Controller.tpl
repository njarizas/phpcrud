<?php
/**
 * Class Controller for table '${table_name}'.
 * @author: Nelson Ariza
 * @date: ${date}
 */
class ${dao_clazz_name}Controller
{
    
    public $alerta;
    public $filas;
    public $modal;
    ${declara_variables}
    function __construct(){
        $this->alerta = "";
        $this->filas = "";
        $this->modal = "";
        ${limpiar_variables}
    }
 
    public function init(){
        if (isset($_POST["actualizar"])){
            $this->actualizar();
        }
        if (isset($_POST["eliminar"])){
            $this->eliminar();
        }
    }
    
    public function ingresar(){
        $this->modal="<script>$(\"#guardar\").modal(\"show\");</script>";
    }

    public function guardar(){
            ${obtener_parametros}
            ${constructor}
            ${asignar_atributos}
            if ($this->${pk}!=""){
                ${asignar_pk}
                if (${funcion_actualizar} > 0) {
                    $this->alerta = "<script>swal(\"Actualización exitósa\", \"El ${domain_name} fue actualizado exitósamente \", \"success\");</script>";
                } else {
                    $this->alerta = "<script>swal(\"Actualización fallida\", \"El ${domain_name} no pudo ser actualizado \", \"error\");</script>";
                } 
            } else {
                if (${funcion_insertar} > 0) {
                    $this->alerta = "<script>swal(\"Registro exitóso\", \"El ${domain_name} fue registrado exitósamente \", \"success\");</script>";
                } else {
                    $this->alerta = "<script>swal(\"Registro fallido\", \"El ${domain_name} no pudo ser registrado \", \"error\");</script>";
                }
            }
    }

    public function eliminar(){
        $this->${pk} = ($_POST['eliminar']);
        if (${funcion_eliminar}> 0) {
            $this->alerta = "<script>swal(\"Eliminación exitósa\", \"El ${domain_name} fue eliminado exitósamente \", \"success\");</script>";
        } else {
            $this->alerta = "<script>swal(\"Eliminación fallida\", \"El ${domain_name} no pudo ser eliminado exitósamente \", \"error\");</script>";
        }
    }
    
    public function actualizar(){
        $this->${pk} = ($_POST['actualizar']);
        ${buscar_objeto}
        ${asignar_variables}$this->modal="<script>$(\"#guardar\").modal(\"show\");</script>";
    }
    
    public function showView(){
        $this->filas=DAOFactory::get${dao_clazz_name}DAO()->listar();
		require_once ('class/${view_folder}/${view_file_name}-view.php');
    }
    
}