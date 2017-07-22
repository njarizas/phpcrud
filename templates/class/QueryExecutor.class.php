<?php
/**
 * Object executes sql queries
 *
 * @author: Nelson Ariza
 * @date: 17.07.2017
 */
class QueryExecutor{

    /**
    * Método que ejecuta consultas select y devuelve el resultset
    */
    public static function execute($sqlQuery){
        $conn = ConnectionFactoryMySqlPDO::getConnection();
        return $conn->query($sqlQuery)->fetchAll();
    }
	
}