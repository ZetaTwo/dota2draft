<?php
namespace Dota2Draft;

require 'Settings.php';

class Database {	
	public static function Instance()
	{
		static $instance;
		if ($instance == null) {
			$instance = new \mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);;
			$instance->query('SET NAMES utf8');
		}
		return $instance;
	}
	
	private function __construct()
	{
	}
}
?>