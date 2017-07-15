<?php
/*
 * Class return connection to database
 *
 * @author: http://phpdao.com
 * @date: 27.11.2007
 */
class ConnectionFactory{
	
	static public function getConnection(){
		$config=Config::getInstance();
		$conn = mysql_connect($config->get('db_host'), $config->get('db_user'), $config->get('db_password'));
		mysql_select_db($config->get('db_database'));
		if(!$conn){
			throw new Exception('could not connect to database');
		}
		return $conn;
	}

	static public function close($connection){
		mysql_close($connection);
	}
}