<?php

namespace UPCC\Request;
use UPCC\GSX;

abstract class AbstractConsignmentRequest {

	protected $parts;
	
	public function __construct() {
		$this->parts = [];
	}
	
	protected function addPart($partNumber, $quantity, $serial=null) {
		if (!GSX::isValidPartNumber($partNumber))
			throw new \Exception("Invalid formatting: partNumber.");
		if (!is_numeric($quantity) or $quantity < 0 or $quantity > 100000)
			throw new \Exception("Invalid or out of range parts quantity.");
		if ($serial === null) 
			$this->parts[] = ["number" => $partNumber, "quantity" => $quantity];
		else {
			$this->parts[] = [
				"number" => $partNumber,
				"quantity" => 1,
				"device" => [
					"id" => $serial
				]
			];
		}
	}
	
	public function addUnserializedPart($partNumber, $quantity) {
		if (array_search($partNumber, array_column($this->parts, "partNumber")) !== false)
			throw new \Exception("Attempted to add a duplicate unserialized part.");
		$this->addPart($partNumber, $quantity);
	}
	
	public function addSerializedPart($partNumber, $serial) {
		if (!GSX::isValidDeviceIdentifier($serial))
			throw new \Exception("Invalid format: product serial");
		$this->addPart($partNumber, 1, $serial);
	}
	
	abstract public function getRequest();
}