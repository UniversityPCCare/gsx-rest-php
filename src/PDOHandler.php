<?php

namespace UPCC;

class PDOHandler {
	private $dsn;
	private $pdo;
	
	public function __construct($host, $db, $user, $pass, $port=3306) {
		self::initializeDatabase($host, $db, $user, $pass, $port);
		$this->dsn = "mysql:host=$host;dbname=$db;port=$port";
		try {
			$this->pdo = new \PDO($this->dsn, $user, $pass);
		} catch (\PDOException $e) {
			throw new \PDOException($e->getMessage(), (int)$e->getCode());
		}
	}
	
	private static function initializeDatabase($host, $db, $user, $pass, $port) {
		$link = mysqli_connect($host, $user, $pass, "", $port);
		self::createDatabaseIfNotExists($link, $db);
		self::createTableIfNotExists($link, $db);
	}
	
	private static function createDatabaseIfNotExists($link, $db) {
		$dbExists = mysqli_select_db($link, $db);
		if (!$dbExists) {
			$dbCreated = mysqli_query($link, "CREATE DATABASE $db");
			if (!$dbCreated)
				throw new \Exception("Could not create database $db");
		}
	}
	
	private static function createTableIfNotExists($link, $db) {
		$tableExists = false !== mysqli_query($link, "SELECT 1 FROM token LIMIT 1");
		if (!$tableExists) {
			$sql = file_get_contents("../config/schema.sql");
			$tableCreated = mysqli_query($link, $sql);
			if (!$tableCreated)
				throw new \Exception("Could not create table");
		}
	}
}