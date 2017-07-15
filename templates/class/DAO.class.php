<?php
abstract class DAO{
    
    protected $conn;

    function __construct() {
        $this->conn = ConnectionFactoryMySqlPDO::getConnection();
    }
    
    public abstract function actualizar($row);
    
    public abstract function buscar($id);
    
    public abstract function contar();
    
    public abstract function eliminar($id);
    
    public abstract function existe($id);
    
	public abstract function insertar(&$row);
	
	public abstract function listar();
    
}