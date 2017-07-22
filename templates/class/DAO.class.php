<?php
abstract class DAO{
    
    protected $conn;

    function __construct() {
        $this->conn = ConnectionFactoryMySqlPDO::getConnection();
    }
    
    public abstract function actualizar($row);
    
    public abstract function buscarFila($id);
    
    public abstract function buscarObjeto($id);
    
    public abstract function contar();
    
    public abstract function eliminar($id);
    
    public abstract function existe($row);
    
    public abstract function existePorId($id);
    
	public abstract function insertar(&$row);
	
	public abstract function listarFilas();
    
    public abstract function listarObjetos();
    
}