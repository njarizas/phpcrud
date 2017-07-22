<?php
/*
 * Class return connection to database
 *
 * @author: Nelson Ariza
 * @date: 17.07.2017
 */
require_once('Config.class.php');
class ConnectionFactoryMySqlPDO{
	
	private static $cont = null;

    private function __construct() {
         die('Init function is not allowed');
    }

    //Conexion PDO
    public static function getConnection() {
		$config=Config::getInstance();
        if (is_null(self::$cont)) {
            try {
                self::$cont = new PDO("mysql:host=" . $config->get('db_host') . ";dbname=" . $config->get('db_database') . ";charset=utf8mb4", $config->get('db_user'), $config->get('db_password'));
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }
        return self::$cont;
    }

    //Desconexion PDO
    public static function disconnect() {
        self::$cont = null;
    }

}