<?php

namespace UPCC\Request;
use UPCC\GSX;

class ConsignmentValidateRequest {

	private $deliveryNumber;
	private $parts;
	private $shipTo;
	
	public function __construct($deliveryNumber) {
		if (GSX::isValidConsignmentDeliveryNumber($deliveryNumber)) {
			$this->deliveryNumber = $deliveryNumber;
			$this->parts = array();
		}
		else
			throw new \Exception("Invalid formatting: deliveryNumber.");
	}
	
	private function addPart($partNumber, $quantity, $serial=null) {
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
	
	public function overrideShipTo($shipTo) {
		if (!GSX::isValidShipTo($shipTo))
			throw new \Exception("Invalid formatting: shipTo");
		$this->shipTo = $shipTo;
	}
	
	public function addUnserializedPart($partNumber, $quantity) {
		if (array_search($partNumber, array_column($this->parts, "partNumber")) !== false)
			throw new \Exception("Attempted to add a duplicate unserialized part.");
		$this->addPart($partNumber, $quantity);
	}
	
	public function addSerializedPart($partNumber, $serial) {
		if (!GSX::isValidProductIdentifier($serial))
			throw new \Exception("Invalid format: product serial");
		$this->addPart($partNumber, 1, $serial);
	}
	
	public function getRequest() {
		if (!isset($this->deliveryNumber))
			throw new \Exception("Error building request body: deliveryNumber not set.");
		if (count($this->parts) == 0)
			throw new \Exception("Error building request body: no parts added.");
		
		$requestBody = [
			"deliveryNumber" => $this->deliveryNumber,
			"parts" => $this->parts
		];
		if (isset($this->shipTo))
			$requestBody["shipTo"] = $this->shipTo;
		return $requestBody;
	}
}