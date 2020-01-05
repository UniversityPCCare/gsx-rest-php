<?php
namespace UPCC;
require_once("PDOHandler.php");

class GSXHandler {
	private const INI_PATH = "../config/config.ini";
	
	private $CERT_PATH;
	private $CERT_PASS;
	
	private $BASE_URL;
	private $SOLD_TO;
	
	private $gsxUserEmail;
	private $gsxShipTo;
	private $activationToken;
	private $authToken;
	private $authTokenCreatedTs;
	private $authTokenLastUsedTs;
	private $pdoHandler;
	
	public function __construct($gsxUserEmail, $gsxShipTo) {
		$this->gsxUserEmail = $gsxUserEmail;
		$this->gsxShipTo = $gsxShipTo;
		
		$config = parse_ini_file(self::INI_PATH);
		$this->pdoHandler = new PDOHandler($config["HOST"], $config["DB"], $config["USER"], $config["PASS"], $config["PORT"]);
		$this->CERT_PATH = $config["CERT_PATH"];
		$this->CERT_PASS = $config["CERT_PASS"];
		$this->BASE_URL = $config["BASE_URL"];
		$this->SOLD_TO = $config["SOLD_TO"];
		
		$this->testConfig();
	}
	
	private function testConfig() {
		if (!isset($this->CERT_PATH) or !file_exists($this->CERT_PATH))
			throw new \Exception("Invalid certificate path set in config.ini!");
		if (!isset($this->CERT_PASS) or strlen($this->CERT_PASS) === 0)
			throw new \Exception("No certificate password set in config.ini!");
		if (!isset($this->BASE_URL) or !preg_match("/https:\/\/partner-connect(?:-uat)?\.apple\.com\/gsx\/api/", $this->BASE_URL))
			throw new \Exception("Invalid Base URL set in config.ini!");
		if (!isset($this->SOLD_TO) or strlen($this->SOLD_TO) !== 10)
			throw new \Exception("Invalid GSX Sold-To account number specified in config.ini!");
		if (!isset($this->gsxShipTo) or strlen($this->gsxShipTo)  !== 10)
			throw new \Exception("Invalid GSX Ship-To number specified in config.ini!");
		if (!isset($this->gsxUserEmail) or strlen($this->gsxUserEmail) === 0)
			throw new \Exception("Invalid GSX User Email provided.");
	}
}