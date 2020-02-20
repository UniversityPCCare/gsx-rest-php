<?php

namespace UPCC\Request;
use UPCC\GSX;

class ConsignmentDeliveryAcknowledgeRequest extends AbstractConsignmentRequest {
	
	public function __construct($deliveryNumber) {
		parent::__construct();
		if (GSX::isValidConsignmentDeliveryNumber($deliveryNumber)) {
			$this->deliveryNumber = $deliveryNumber;
			$this->parts = array();
		}
		else
			throw new \Exception("Invalid formatting: deliveryNumber.");
	}
	
	public function overrideShipTo($shipTo) {
		if (!GSX::isValidShipTo($shipTo))
			throw new \Exception("Invalid formatting: shipTo");
		$this->shipTo = $shipTo;
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