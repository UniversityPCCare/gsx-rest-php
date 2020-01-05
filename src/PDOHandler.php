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
		
	public static function formatTime($time) {
		return date("c", $time);
	}
	
	public function fetchTokenDetails($userEmail) {
		$sql = "SELECT * FROM token WHERE userEmail = ? LIMIT 1";
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$userEmail]);
		$tokenDetails = $statement->fetch();
		if (!$tokenDetails) {
			$this->createTokenDetails($userEmail);
			$statement->execute([$userEmail]);
			$tokenDetails = $statement->fetch();
		}
		return $tokenDetails;
	}
	
	public function createTokenDetails($userEmail) {
		$sql = "INSERT INTO token (userEmail, activationToken, isActivationTokenConsumed, authToken, authTokenCreatedTs, authTokenLastUsedTs) VALUES (?, NULL, '0', NULL, NULL, NULL)";
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$userEmail]);
	}
	
	public function storeActivationToken($userEmail, $activationToken) {
		$sql = "UPDATE token SET activationToken = ? WHERE userEmail = ?";
		$statement = $this->pdo->prepare($sql);
		return $statement->execute([$activationToken, $userEmail]);
	}
	
	public function storeAuthToken($userEmail, $authToken) {
		$sql = "UPDATE token SET isActivationTokenConsumed = 1, authToken = ?, authTokenCreatedTs = ? WHERE userEmail = ?";
		$statement = $this->pdo->prepare($sql);
		$ts = self::formatTime(time());
		return $statement->execute([$authToken, $ts, $userEmail]);
	}
	
	public function storeAuthTokenLastUsedTs($userEmail, $time) {
		$sql = "UPDATE token SET authTokenLastUsedTs = ? WHERE userEmail = ?";
		$time = self::formatTime($time);
		$statement = $this->pdo->prepare($sql);
		return $statement->execute([$time, $userEmail]);
	}
	
}