<?php

namespace UPCC\Request;
use UPCC\GSX;

class PartsLookupRequest {

	private const MAX_DEVICES = 5;
	private const MAX_PRODUCTS = 5;
	private const MAX_SEARCH_TERMS = 5;
	private const MAX_COMPONENT_ISSUES = 6;
	private const MAX_EEE_CODES = 5;
	private const MAX_PART_NUMBERS = 5;
	
	private $devices;
	private $products;
	private $searchTerms;
	private $componentIssues;
	private $eeeCodes;
	private $partNumbers;
	
	private $repairType;
	
	public function __construct() {
		$this->devices = array();
		$this->products = array();
		$this->searchTerms = array();
		$this->componentIssues = array();
		$this->eeeCodes = array();
		$this->partNumbers = array();
	}
	
	public function setRepairType($repairType) {
		if (!GSX::isValidRepairType($repairType))
			throw new \Exception("Invalid formatting: repairType.");
		$this->repairType = $repairType;
	}
	
	public function addDeviceId($deviceId) {
		if (!GSX::isValidDeviceIdentifier($deviceId))
			throw new \Exception("Invalid formatting: deviceId.");
		if (count($this->devices) >= self::MAX_DEVICES)
			throw new \Exception("Cannot add more than " . self::MAX_DEVICES . " device IDs to a Parts Lookup request.");
		if (in_array($deviceId, $this->devices))
			throw new \Exception("Attempted to add Device ID twice!");
		$this->devices[] = ["id" => $deviceId];
	}
	
	public function addProductId($productId) {
		if (!GSX::isValidProductIdentifier($productId))
			throw new \Exception("Invalid formatting: productId.");
		if (count($this->products) >= self::MAX_PRODUCTS)
			throw new \Exception("Cannot add more than " . self::MAX_PRODUCTS . " product IDs to a Parts Lookup request.");
		if (in_array($productId, $this->products))
			throw new \Exception("Attempted to add Product ID twice!");
		$this->products[] = $productId;
	}
	
	public function addSearchTerm($searchTerm) {
		if (!preg_match("/^[a-z0-9A-Z \)\(\-,\.\/]{1,240}$/", $searchTerm))
			throw new \Exception("Invalid formatting: searchTerm.");
		if (count($this->searchTerms) >= self::MAX_SEARCH_TERMS)
			throw new \Exception("Cannot add more than " . self::MAX_SEARCH_TERMS . " search terms to a Parts Lookup request.");
		if (in_array($searchTerm, $this->searchTerms))
			throw new \Exception("Attempted to add the same search term twice!");
		$this->searchTerms[] = $searchTerm;
	}
	
	public function addComponentIssue($componentCode, $issueCode, $type, $priority, $order, $reproducibility=null) {
		if (!GSX::isValidComponentCode($componentCode))
			throw new \Exception();
		if (!GSX::isValidIssueCode($issueCode))
			throw new \Exception();
		if (!GSX::isValidReportedBy($type))
			throw new \Exception();
		if ($priority < 1 or $priority > 3)
			throw new \Exception();
		if ($order < 1 or $order > 6)
			throw new \Exception();
		if (isset($reproducibility) and !GSX::isValidReproducibilityCode($reproducibility))
			throw new \Exception("$reproducibility");
		if (count($this->componentIssues) >= self::MAX_COMPONENT_ISSUES)
			throw new \Exception("Cannot add more than " . self::MAX_COMPONENT_ISSUES . " component/issues to a Parts Lookup request.");
		$componentIssue = [
			"componentCode" => $componentCode,
			"issueCode" => $issueCode,
			"type" => $type,
			"priority" => $priority,
			"order" => $order
		];
		if (isset($reproducibility))
			$componentIssue["reproducibility"] = $reproducibility;
		$this->componentIssues[] = $componentIssue;
	}
	
	public function addEeeNumber($eeeCode) {
		if (!GSX::isValidEeeCode($eeeCode))
			throw new \Exception("Invalid formatting: eeeCode.");
		if (count($this->eeeCodes) > self::MAX_EEE_CODES)
			throw new \Exception("Cannot add more than " . self::MAX_EEE_CODES . " EEE Codes to a Parts Lookup request.");
		if (in_array($eeeCode, $this->eeeCodes))
			throw new \Exception("Attempted to add EEE Code twice!");
		$this->eeeCodes[] = $eeeCode;
	}
	
	public function addPartNumber($partNumber) {
		if (!GSX::isValidPartNumber($partNumber))
			throw new \Exception("Invalid formatting: partNumber.");
		if (count($this->partNumbers) > self::MAX_PART_NUMBERS)
			throw new \Exception("Cannot add more than " . self::MAX_PART_NUMBERS . " part numbers to a Parts Lookup request.");
		if (in_array($partNumber, $this->partNumbers))
			throw new \Exception("Attempted to add part number twice!");
		$this->partNumbers[] = $partNumber;
	}
	
	public function getRequest() {
		if (!(isset($this->devices) or 
			isset($this->products) or 
			isset($this->searchTerms) or 
			isset($this->componentIssues) or 
			isset($this->eeeCodes) or
			isset($this->partNumbers)))
			throw new \Exception("Error building request body: At least one search criteria required.");
		$requestBody = array();
		
		if (isset($this->devices) and count($this->devices) > 0)
			$requestBody["devices"] = $this->devices;
		if (isset($this->products) and count($this->products) > 0)
			$requestBody["products"] = $this->products;
		if (isset($this->searchTerms) and count($this->searchTerms) > 0)
			$requestBody["searchTerms"] = $this->searchTerms;
		if (isset($this->componentIssues) and count($this->componentIssues) > 0)
			$requestBody["componentIssues"] = $this->componentIssues;
		if (isset($this->eeeCodes) and count($this->eeeCodes) > 0)
			$requestBody["eeeCodes"] = $this->eeeCodes;
		if (isset($this->partNumbers) and count($this->partNumbers) > 0)
			$requestBody["partNumbers"] = $this->partNumbers;
		if (isset($this->repairType))
			$requestBody["repairType"] = $this->repairType;
		return $requestBody;
	}
}