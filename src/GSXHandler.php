<?php
namespace UPCC;


class GSXHandler {
	private const INI_PATH = __DIR__ . "/../config/config.ini";
	
	private $REST_CERT_PATH;
	private $REST_CERT_PASS;
	private $REST_BASE_URL;
	private $REST_AUTH_PATH;
	private $REST_GSX_PATH;
	private $API_VERSION;
	
	private $SOLD_TO;
	private $ACCEPT_LANGUAGE;
	
	private $operatorEmail;
	private $shipTo;
	private $activationToken;
	private $isActivationTokenConsumed;
	private $authToken;
	private $authTokenCreatedTs;
	private $authTokenLastUsedTs;
	private $pdoHandler;
	
	private $lastRestResponseHeaders;
	
	private $SOAP_CERT_PATH;
	private $SOAP_CERT_PASS;
	private $SOAP_WSDL_URL;
	private $SOAP_ENVIRONMENT;
	private $SOAP_REGION;
	private $SOAP_TIMEZONE;
	private $SOAP_LANGUAGE;
	
	private $soapClient;
	private $soapSessionId;
	
	public function __construct($operatorEmail, $shipTo, $options=null) {
		$this->operatorEmail = $operatorEmail;
		$this->shipTo = $shipTo;
		
		if ($options == null or !is_array($options))
			$config = parse_ini_file(self::INI_PATH);
		else
			$config = $options;
		$this->pdoHandler = new PDOHandler($config["HOST"], $config["DB"], $config["USER"], $config["PASS"], $config["PORT"]);
		$this->SOLD_TO = $config["SOLD_TO"];

		$this->REST_CERT_PATH = $config["REST_CERT_PATH"];
		$this->REST_CERT_PASS = $config["REST_CERT_PASS"];
		$this->REST_BASE_URL = $config["REST_BASE_URL"];
		$this->REST_AUTH_PATH = $config["REST_AUTH_PATH"];
		$this->REST_GSX_PATH = $config["REST_GSX_PATH"];
		$this->ACCEPT_LANGUAGE = $config["ACCEPT_LANGUAGE"];
		$this->API_VERSION = $config["API_VERSION"];
		
		$this->SOAP_CERT_PATH = $config["SOAP_CERT_PATH"];
		$this->SOAP_CERT_PASS = $config["SOAP_CERT_PASS"];
		$this->SOAP_WSDL_URL = $config["SOAP_WSDL_URL"];
		$this->SOAP_ENVIRONMENT = $config["SOAP_ENVIRONMENT"];
		$this->SOAP_REGION = $config["SOAP_REGION"];
		$this->SOAP_TIMEZONE = $config["SOAP_TIMEZONE"];
		$this->SOAP_LANGUAGE = $config["SOAP_LANGUAGE"];
		//$this->initSoapClient();
		
		date_default_timezone_set($config["PHP_TZ"]);
		
		$this->testConfig();
		$this->loadFromDB();
	}
	
/*	private function initSoapClient() {
		if (!isset($this->soapClient)) {
			try {
				$this->soapClient = new \SoapClient($this->SOAP_WSDL_URL, [
					"trace" => true,
					"exceptions" => true,
					"local_cert" => $this->SOAP_CERT_PATH,
					"passphrase" => $this->SOAP_CERT_PASS
				]);
				$authenticateResponse = $this->soapClient->Authenticate([
					"AuthenticateRequest" => [
						"userId" => $this->operatorEmail,
						"serviceAccountNo" => $this->SOLD_TO,
						"languageCode" => $this->SOAP_LANGUAGE,
						"userTimeZone" => $this->SOAP_TIMEZONE
					]
				]);
				if (property_exists($authenticateResponse, "AuthenticateResponse")) {
					$this->soapSessionId = $authenticateResponse->AuthenticateResponse->userSessionId;
					return true;
				}
			}
			catch (\SoapFault $e) {
				error_log($e->__toString());
				return false;
			}
		}
		else
			return true;
	}*/
	
	private function testConfig() {
		if (!function_exists("curl_version"))
			throw new \Exception("cURL is not enabled in your php.ini, it is required.");
		if (!isset($this->REST_CERT_PATH) or !file_exists($this->REST_CERT_PATH))
			throw new \Exception("Invalid certificate path set in config.ini!");
		if (!isset($this->REST_CERT_PASS) or strlen($this->REST_CERT_PASS) === 0)
			throw new \Exception("No certificate password set in config.ini!");
		if (!isset($this->REST_BASE_URL) or strlen($this->REST_BASE_URL) == 0)
			throw new \Exception("Invalid REST Base URL set in config.ini!");
		if (!isset($this->REST_AUTH_PATH) or strlen($this->REST_AUTH_PATH) == 0)
			throw new \Exception("Invalid REST Auth API path set in config.ini!");
		if (!isset($this->REST_GSX_PATH) or strlen($this->REST_GSX_PATH) == 0)
			throw new \Exception("Invalid REST GSX API path set in config.ini!");
		if (!isset($this->SOLD_TO) or strlen($this->SOLD_TO) !== 10)
			throw new \Exception("Invalid GSX Sold-To account number specified in config.ini!");
		if (!isset($this->shipTo) or strlen($this->shipTo)  !== 10)
			throw new \Exception("Invalid GSX Ship-To number provided!");
		if (!isset($this->operatorEmail) or strlen($this->operatorEmail) === 0)
			throw new \Exception("Invalid GSX User Email provided!");
		if (!isset($this->ACCEPT_LANGUAGE) or strlen($this->ACCEPT_LANGUAGE) === 0 or !preg_match("/[a-z]{2}_[A-Z]{2}/", $this->ACCEPT_LANGUAGE))
			throw new \Exception("Invalid Accept-Language header specified in config.ini! (Default: en_US)");
	}
	
	private function isAuthTokenValid() {
		$lastUsedThreshold = "30 minute";
		$createdThreshold = "12 hour";
		
		if ($this->activationToken != null and $this->isActivationTokenConsumed == 0) return false;
		if ($this->authToken == null or $this->authTokenCreatedTs == null) return false;
		elseif ($this->authTokenCreatedTs != null and $this->authTokenLastUsedTs == null and strtotime("+$createdThreshold", strtotime($this->authTokenCreatedTs)) < time()) return false;
		elseif ($this->authTokenLastUsedTs != null and strtotime("+$lastUsedThreshold", strtotime($this->authTokenLastUsedTs)) < time()) return false;
		else return true;
	}
	
	private function fetchAuthToken() {
		if ($this->activationToken == null)
			throw new \Exception("Tried to retrieve Auth Token but user ($this->operatorEmail) does not have an Activation Token");
		elseif ($this->activationToken != null and $this->isActivationTokenConsumed and $this->authToken == null)
			throw new \Exception("Tried to retrieve Auth Token but user's ($this->operatorEmail) Activation Token has already been consumed and no Auth Token is stored.");
		
		$tokenToUse = (($this->authToken == null or !$this->isActivationTokenConsumed) ? $this->activationToken : $this->authToken);
		$response = $this->curlSend("POST", "/authenticate/token",
		["userAppleId"=>$this->operatorEmail,"authToken"=>$tokenToUse]);
		if (property_exists($response, "authToken")) {
			$this->setAuthToken($response->authToken);
			return;
		}
		else
			throw new \Exception("Tried to fetch Auth Token for user ($this->operatorEmail) but did not receive one from GSX\n" . var_export($response, true));
	}
	
	private function setAuthToken($authToken) {
		if (GSX::validateUuid($authToken)) {
			$this->authToken = $authToken;
			$this->isActivationTokenConsumed = true;
			$this->pdoHandler->storeAuthToken($this->operatorEmail, $authToken);
			$this->loadFromDB();
		}
		else
			throw new \Exception("Tried to store an invalidly-formatted Auth Token!");
	}
	
	private function setAuthTokenLastUsedTs() {
		$this->pdoHandler->storeAuthTokenLastUsedTs($this->operatorEmail, time());
		$this->loadFromDB();
	}
	
	private function loadFromDB() {
		$tokenDetails = $this->pdoHandler->fetchTokenDetails($this->operatorEmail);
		if ($tokenDetails) {
			$this->activationToken = $tokenDetails["activationToken"];
			$this->isActivationTokenConsumed = (bool) $tokenDetails["isActivationTokenConsumed"];
			$this->authToken = $tokenDetails["authToken"];
			$this->authTokenCreatedTs = $tokenDetails["authTokenCreatedTs"];
			$this->authTokenLastUsedTs = $tokenDetails["authTokenLastUsedTs"];
		}
		else {
			$this->activationToken = null;
			$this->isActivationTokenConsumed = false;
			$this->authToken = null;
			$this->authTokenCreatedTs = null;
			$this->authTokenLastUsedTs = null;
		}
	}
	
	public function setActivationToken($activationToken) {
		if (GSX::validateUuid($activationToken)) {
			$this->activationToken = $activationToken;
			$this->isActivationTokenConsumed = 0;
			$this->pdoHandler->storeActivationToken($this->operatorEmail, $activationToken);
			return true;
		}
		else
			throw new \Exception("Tried to store an invalidly-formatted Activation Token!");
	}
	
	public function soapSend($endpoint, $body, $requestName=null, $responseName=null) {		
		try {
			if (!isset($requestName))
				$requestName = $endpoint . "Request";
			if (!isset($responseName))
				$responseName = $endpoint . "Response";
			$body["userSession"] = ["userSessionId" => $this->soapSessionId];
			$response = $this->soapClient->$endpoint([$requestName => $body]);
			return $response->$responseName;
		}
		catch (\SoapFault $e) {
			error_log($e->__toString());
		}
		catch (\Exception $e) {
			error_log($e->__toString());
		}
	}
	
	private function curlSend($method, $endpoint, $body = null, $additionalHeaders = null) {
		if (strstr($endpoint, "authenticate"))
			$full_base_url = $this->REST_BASE_URL . $this->REST_AUTH_PATH;
		else
			$full_base_url = $this->REST_BASE_URL . $this->REST_GSX_PATH;

		//first, make sure the Auth Token is still valid. If not, request a new one
		if (!$this->isAuthTokenValid() and $endpoint != "/authenticate/token")
			$this->fetchAuthToken();
		
		//then, start by setting headers array
		$responseHeaders = array();
		$this->lastRestResponseHeaders = null;
		$headers = array(
			"X-Apple-SoldTo: " . $this->SOLD_TO,
			"X-Apple-ShipTo: " . $this->shipTo,
			"X-Operator-User-ID: " . $this->operatorEmail,
			"X-Apple-Service-Version: " . $this->API_VERSION,
			"Content-Type: application/json",
			"Accept: application/json",
			"Accept-Language: " . $this->ACCEPT_LANGUAGE
		);
		if (is_array($additionalHeaders) and count($additionalHeaders)) {
			//if the calling function specified a custom "Accept" header, unset the default "Accept: application/json" header
			foreach ($additionalHeaders as $additionalHeader) {
				if (strpos($additionalHeader, "Accept:") === 0)
					unset($headers[4]); 
			}
			$headers = array_merge($headers, $additionalHeaders);
		}
		if (is_array($body) and count($body))
			$headers[] = "Content-Length: " . strlen(json_encode($body));
		if ($this->authToken)
			$headers[] = "X-Apple-Auth-Token: " . $this->authToken;
		
		//done setting headers array, begin preparing curl
		if (strpos($endpoint, "/") !== 0)
			$endpoint = "/" . $endpoint;

		$default_charset = ini_get("default_charset"); #store current charset, because...
		ini_set('default_charset', NULL); #cURL tries to add boundaries which GSX isn't expecting
		$ch = curl_init($full_base_url . $endpoint);
		curl_setopt_array($ch, array(
			CURLINFO_HEADER_OUT => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSLCERT => $this->REST_CERT_PATH,
			CURLOPT_SSLCERTPASSWD => $this->REST_CERT_PASS
		));
		if (is_array($body) and count($body))
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		if ($method == "POST")
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
			$length = strlen($header);
			$header = explode(":", $header, 2);
			if (count($header) < 2)
				return $length;
			$header_variable = strtolower(trim($header[0]));
			$header_value = trim($header[1]);
			$responseHeaders[$header_variable] = $header_value;
			return $length;
		});
		
		//done building curl object, send it
		$response = curl_exec($ch);
		$this->lastRestResponseHeaders = $responseHeaders;
		ini_set("default_charset", $default_charset); #return this back to what it was
		$this->logCurlRequest($ch, $endpoint, $headers, $body, $responseHeaders, $response);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlErrorNo = curl_errno($ch);
		$curlError = curl_error($ch);
		switch ($httpCode) {
			case 200: #Success
				if ($endpoint != "/authenticate/check")
					$this->setAuthTokenLastUsedTs();
				if (json_decode($response))
					return json_decode($response);
				return $response;
				break;
			case 401: #Unauthorized
			case 403: #Forbidden
				if ($endpoint == "/authenticate/token")
					throw new \Exception("Tried retrieving new Auth Token for user ($this->operatorEmail), received HTTP $httpCode. User must manually retrieve a new Activation Token from GSX to continue.");
				else
					throw new \Exception("User ($this->operatorEmail) is not authorized. HTTP $httpCode");
				break;
			case false: #cURL error
				throw new \Exception("Error sending request. cURL error $curlErrorNo. Error: $curlError");
				break;
			default:
				throw new \Exception("Invalid response received from GSX. Expected HTTP200, received HTTP$httpCode");
		}
	}
	
	private function logCurlRequest($ch, $endpoint, $headers, $body, $responseHeaders, $response) {
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?? null;
		$curlErrorNo = curl_errno($ch);
		$curlError = curl_error($ch);
		$this->pdoHandler->storeLogEntry(
			$this->operatorEmail,
			$endpoint,
			$httpCode,
			$curlErrorNo,
			$curlError,
			json_encode($headers),
			((is_array($body) and count($body)) ? json_encode($body) : null),
			json_encode($responseHeaders),
			is_string($response) ? $response : json_encode($response)
		);
	}
	
	public function getLastRestResponseHeaders() {
		return $this->lastRestResponseHeaders;
	}
	
	public function testAuthentication() {
		if ($this->curlSend("GET", "/authenticate/check"))
			return true;
		return false;
	}
	
	public function endSession() {
		return $this->curlSend("POST", "/authenticate/end-session", [
			"userAppleId" => $this->operatorEmail,
			"authToken" => $this->authToken
		]);
	}
	
	public function ProductDetails($id) {
		$id = trim($id);
		$now = date(DATE_ATOM);
		
		if (!GSX::isValidDeviceIdentifier($id))
			return false;
		
		return $this->curlSend("POST", "/repair/product/details",
		[
			"unitReceivedDateTime" => $now,
			"device" => ["id" => $id]
		]);
	}
	
	public function RepairSummary($body) {
		return $this->curlSend("POST", "/repair/summary", $body);
	}
	
	public function RepairSummaryByIds($ids) {
		if (!is_array($ids))
			$ids = [$ids];
		$validIds = [];
		foreach ($ids as $id) {
			$id = trim($id);
			if (GSX::isValidRepairIdentifier($id))
				$validIds[] = $id;
		}
		if (count($validIds))
			return $this->RepairSummary(["repairIds" => $validIds]);
		return false;
	}
	
	public function RepairSummaryById($id) {
		return $this->RepairSummaryByIds([$id]);
	}
	
	public function RepairDetails($id) {
		$id = trim($id);
		if (GSX::isValidRepairIdentifier($id))
			return $this->curlSend("GET", "/repair/details?repairId=$id");
		return false;
	}
	
	public function RepairEligibility($body) {
		return $this->curlSend("POST", "/repair/eligibility", $body);
	}
	
	public function RepairEligibilityByDeviceId($id) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id))
			return $this->RepairEligibility(["device"=>["id"=>$id]]);
		return false;
	}
	
	public function RepairUpdate($body) {
		return $this->curlSend("POST", "/repair/update", $body);
	}
	
	public function RepairAudit($id) {
		$id = trim($id);
		if (GSX::isValidRepairIdentifier($id))
			return $this->curlSend("GET", "/repair/audit?repairId=$id");
		return false;
	}
	
	public function ProductSerializer($body) {
		return $this->curlSend("POST", "/repair/product/serializer", $body);
	}
	
	public function QuestionsLookup($body) {
		return $this->curlSend("POST", "/repair/questions", $body);
	}
	
	public function QuestionsLookupByComponentIssue($id, $componentCode, $issueCode, $reportedBy) {
		$id = trim($id);
		$componentCode = trim($componentCode);
		$issueCode = trim($issueCode);
		$reportedBy = trim($reportedBy);
		if (GSX::isValidDeviceIdentifier($id) 
			and GSX::isValidComponentCode($componentCode) 
			and GSX::isValidIssueCode($issueCode)
			and GSX::isValidReportedBy($reportedBy)) {
			return $this->QuestionsLookup([
				"device" => ["id" => $id],
				"componentIssues" => [
					[
						"priority" => 1,
						"order" => 1,
						"componentCode" => $componentCode,
						"issueCode" => $issueCode,
						"type" => $reportedBy
					]
				]
			]);
		}		
	}
	
	public function LoanerReturn($body) {
		return $this->curlSend("POST", "/repair/loaner/return", $body);
	}
	
	public function CreateRepair($body) {
		return $this->curlSend("POST", "/repair/create", $body);
	}
	
	public function ComponentIssueLookup($body) {
		return $this->curlSend("POST", "/repair/product/componentissue", $body);
	}
	
	public function ComponentIssueLookupByCode($code) {
		if (GSX::isValidComponentCode($code))
			return $this->ComponentIssueLookup(["componentCode" => $code]);
	}
	
	public function ComponentIssueLookupByCodeAndId($code, $id) {
		if (GSX::isValidComponentCode($code) and GSX::isValidDeviceIdentifier($id))
			return $this->ComponentIssueLookup(["componentCode" => $code, "device" => ["id"=>$id]]);
	}
	
	public function ComponentIssueLookupById($id) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id))
			return $this->ComponentIssueLookup(["device"=>["id"=>$id]]);
		return false;
	}
	
	public function ProductSerializerLookup($body) {
		return $this->curlSend("POST", "/repair/product/serializer/lookup", $body);
	}
	
	public function ProductSerializerLookupByCode($languageCode) {
		return $this->ProductSerializerLookup([
			"languageCode" => $languageCode
		]);
	}
	
	public function ProductSerializerLookupById($id, $languageCode) {
		if (GSX::isValidDeviceIdentifier($id))
			return $this->ProductSerializerLookup([
				"languageCode" => $languageCode,
				"device"=>["id"=>$id]]);
	}
	
	public function DiagnosticsSuites($id) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id))
			return $this->curlSend("GET", "/diagnostics/suites?deviceId=$id");
		return false;
	}
	
	public function RunDiagnosticTest($suiteId, $id) {
		$suiteId = trim($suiteId);
		$id = trim($id);
		
		if (GSX::isValidDiagnosticSuiteIdentifier($suiteId) and GSX::isValidDeviceIdentifier($id)) {
			return $this->curlSend("POST", "/diagnostics/initiate-test",
				[
					"diagnostics" => ["suiteId" => $suiteId],
					"device" => ["id" => $id]
				]);
		}
		return false;
	}

	public function DiagnosticsLookup($body) {
		return $this->curlSend("POST", "/diagnostics/lookup", $body);
	}
	
	public function DiagnosticsLookupByDeviceId($id, $maximumResults=null) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id)) {
			$body = ["device"=>["id"=>$id]];
			if (is_numeric($maximumResults))
				$body["maximumDiagsReturned"] = $maximumResults;
			return $this->DiagnosticsLookup($body);
		}
		return false;
	}
	
	public function DiagnosticsCustomerReportUrl($eventNumber) {
		if (GSX::isValidDiagnosticEventNumber($eventNumber))
			return $this->curlSend("GET", "/diagnostics/customer-report-url?eventNumber=$eventNumber");
	}
	
	public function DiagnosticsStatus($id) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id))
			return $this->curlSend("POST", "/diagnostics/status", ["device"=>["id"=>$id]]);
		return false;
	}
	
	public function ConsignmentValidate($body) {
		return $this->curlSend("POST", "/consignment/validate", $body);
	}
	
	public function AcknowledgeConsignmentDelivery($body) {
		return $this->curlSend("POST", "/consignment/delivery/acknowledge", $body);
	}
	
	public function ShipConsignmentDecreaseOrder($body) {
		return $this->curlSend("POST", "/consignment/order/shipment", $body);
	}
	
	public function ConsignmentOrderLookup($body, $pageSize=null, $pageNumber=null) {
		$endpoint = "/consignment/order/lookup?";
		if (isset($pageSize) and $pageSize > 0 and $pageSize <= 50)
			$endpoint .= "pageSize=$pageSize&";
		if (isset($pageNumber) and $pageNumber > 0)
			$endpoint .= "pageNumber=$pageNumber";
		return $this->curlSend("POST", $endpoint, $body);
	}
	
	public function ConsignmentDeliveryLookup($body, $pageNumber=0) {
		$endpoint = "/consignment/delivery/lookup";
		if ($pageNumber > 0)
			$endpoint .= "?pageNumber=$pageNumber";
		return $this->curlSend("POST", $endpoint, $body);
	}
	
	public function ConsignmentDeliveryLookupByStatus($code, $pageNumber=0) {
		$code = trim($code);
		if (GSX::isValidConsignmentDeliveryStatus($code))
			return $this->ConsignmentDeliveryLookup(["deliveryStatusGroupCode"=>$code], $pageNumber);
		return false;
	}
	
	public function ConsignmentDeliveryLookupByDate(\DateTime $startDate, \DateTime $endDate = null, $statusCode=GSX::CONSIGNMENT_DELIVERY_CODE_ALL, $pageNumber=0) {
		$startDateFormatted = $startDate->format(DATE_ATOM);
		if ($endDate)
			$endDateFormatted = $endDate->format(DATE_ATOM);
		else
			$endDateFormatted = $startDateFormatted;
		
		if ($startDateFormatted and $endDateFormatted and GSX::isValidConsignmentDeliveryStatus($statusCode)) {
			return $this->ConsignmentDeliveryLookup([
				"createdFromDate" => $startDateFormatted,
				"createdToDate" => $endDateFormatted,
				"deliveryStatusGroupCode" => GSX::CONSIGNMENT_DELIVERY_CODE_ALL
			], $pageNumber);
		}
		return false;
	}
	
	public function SubmitConsignmentDecreaseOrder($body) {
		return $this->curlSend("POST", "/consignment/order/submit", $body);
	}
	
	public function ArticleContentLookup($id) {
		$id = trim($id);
		if (is_string($id) and GSX::isValidArticleId($id))
			return $this->curlSend("GET", "/content/article?articleId=$id");
		return false;
	}
	
	public function ArticleIdLookup($body, $pageSize=null, $pageNumber=null) {
		$endpoint = "/content/article/lookup?";
		if (isset($pageSize) and $pageSize > 0 and $pageSize <= 100)
			$endpoint.= "pageSize=$pageSize&";
		if (isset($pageNumber) and $pageNumber > 0)
			$endpoint .= "pageNumber=$pageNumber";
		return $this->curlSend("POST", $endpoint, $body);
	}
	
	public function ArticleIdLookupByDeviceId($id, $pageSize=null, $pageNumber=null) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id))
			return $this->ArticleIdLookup(["device"=>["id"=>$id]], $pageSize, $pageNumber);
		return false;
	}

	public function ArticleLookupByType($articleType) {
		if (GSX::isValidArticleType($articleType))
			return $this->ArticleIdLookup(["articleType" => $articleType]);
	}
	/*
	** The following notice applies to all following "DownloadDocument" functions:
	** Returns the application/octet-stream of a PDF file
	** Recommended to set the Content-Type and Content-Disposition headers when
	** "displaying" (echoing) this stream so it will download as an
	** appropriately-named PDF
	*/
	public function DownloadDocumentPost($body, $documentType) {
		return $this->curlSend("POST", "/document-download?documentType=$documentType", 
		$body,
		["Accept: application/json,application/octet-stream"]);
	}
	
	public function DownloadConsignmentProforma($shipmentNumber, $shipTo) {
		if (GSX::isValidShipmentNumber($shipmentNumber) and GSX::isValidShipTo($shipTo))
			return $this->DownloadDocumentPost([
				"identifiers" => [
					"shipmentNumber" => $shipmentNumber,
					"shipTo" => $shipTo
				]],
				"consignmentProforma");
	}
	
	public function DownloadConsignmentPackingList($shipmentNumber, $shipTo) {
		if (GSX::isValidShipmentNumber($shipmentNumber) and GSX::isValidShipTo($shipTo))
			return $this->DownloadDocumentPost([
				"identifiers" => [
					"shipmentNumber" => $shipmentNumber,
					"shipTo" => $shipTo
				]],
				"consignmentPackingList");
	}
	
	public function DownloadDepotShipper($id) {
		if (GSX::isValidRepairIdentifier($id))
			return $this->DownloadDocumentPost([
				"identifiers" => [
					"repairId" => $id
				]],
				"depotShipper");
	}
	
	public function DownloadDocumentGet($documentType) {
		$documentType = urlencode($documentType);
		return $this->curlSend("GET", "/document-download?documentType=$documentType", null, ["Accept: application/json,application/octet-stream"]);
	}
	
	public function DownloadWarrantyClaim() {
		return $this->DownloadDocumentGet("submitWarrantyClaim");
	}
	
	public function AttachmentUploadAccess($body) {
		return $this->curlSend("POST", "/attachment/upload-access", $body);
	}
	
	public function AttachmentUploadAccessMultiple($id, $attachments) {
		$includedAttachments = [];
		foreach ($attachments as $attachment) {
			if ($attachment["sizeInBytes"] >= 1
				and $attachment["sizeInBytes"] <= 5242880
				and GSX::isValidAttachmentName($attachment["name"]))
				$includedAttachments[] = $attachment;
		}
		if (GSX::isValidDeviceIdentifier($id) and count($includedAttachments) > 0)
			return $this->AttachmentUploadAccess([
				"attachments" => $includedAttachments,
				"device" => ["id"=>$id]
			]);
	}
	
	public function AttachmentUploadAccessSingle($id, $sizeInBytes, $fileName) {
		return $this->AttachmentUploadAccessMultiple($id, [
			["sizeInBytes" => $sizeInBytes, "name" => $fileName]]);
	}
	
	public function PartsSummary($body) {
		return $this->curlSend("POST", "/parts/summary", $body);
	}
	
	public function PartsSummaryByDeviceId($id) {
		$id = trim($id);
		if (GSX::isValidDeviceIdentifier($id))
			return $this->PartsSummary(["devices"=>[["id" => $id]]]);
		return false;
	}
	
	public function PartsSummaryByComponentIssue($id, $componentCode, $issueCode) {
		$id = trim($id);
		$componentCode = trim($componentCode);
		$issueCode = trim($issueCode);
		if (GSX::isValidDeviceIdentifier($id) and GSX::isValidComponentCode($componentCode) and GSX::isValidIssueCode($issueCode)) {
			return $this->PartsSummary([
				"devices" => [["id" => $id]],
				"componentIssues" => [
					[
						"componentCode" => $componentCode,
						"issueCode" => $issueCode,
						"order" => 1,
						"priority" => 1,
						"type" => "TECH"
					]
				]
			]);
		}
	}
	
	public function TechnicianLookup($body) {
		return $this->curlSend("POST", "/technician/lookup", $body);
	}
	
	public function TechnicianLookupByName($firstName, $lastName, $shipTo=null) {
		$firstName = trim($firstName);
		$lastName = trim($lastName);
		$body = null;
		if (is_string($firstName) and is_string($lastName)) {
			$body = [
				[
					"condition" => "startsWith",
					"field" => "firstName",
					"value" => $firstName
				],
				[
					"condition" => "startsWith",
					"field" => "lastName",
					"value" => $lastName
				]
			];
			if (is_string($shipTo))
				$body[] = ["condition" => "equals", "field" => "shipTo", "value" => $shipTo];
		}
		
		if (is_array($body))
			return $this->TechnicianLookup($body);
		return false;
	}

	/* Functions below are for Legacy SOAP API */
	
	public function InvoiceLookup($body) {
		return $this->soapSend("InvoiceDetailsLookup", [
			"lookupRequestData" => $body
		]);
	}
	
	public function InvoiceLookupById($id) {
		return $this->InvoiceLookup(["invoiceID"=>$id]);
	}
	
	public function AcknowledgeCommunication($body) {
		return $this->soapSend("AcknowledgeCommunication", [
			"communicationRequest" => $body
		]);
	}
	
	/*
	** If an article ID is a valid SERVICE_NEWS article and the acknowledgement type is valid,
	** this SOAP function will work properly to set the acknowledgement status. 
	** May not be able to acknowledge SERVICE_NEWS articles for regions outside of your own,
	** although calls to the REST endpoint /content/article/lookup has no way to filter these
	** out upon request and the region of an article is not indicated anywhere in its response.
	*/
	public function AcknowledgeCommunicationById($articleId, $type) {
		if (GSX::isValidArticleId($articleId) and GSX::isValidArticleAcknowledgementType($type))
			return $this->AcknowledgeCommunication(["acknowledgement" => [[
				"articleID" => $articleId,
				"acknowledgeType" => $type
			]]]);
	}
	
}