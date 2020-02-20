<?php

namespace UPCC\Request;
use UPCC\GSX;

class ConsignmentValidateRequest extends AbstractConsignmentRequest {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function getRequest() {
		if (count($this->parts) == 0)
			throw new \Exception("Error building request body: no parts added.");
		
		$requestBody = [
			"parts" => $this->parts
		];
		return $requestBody;
	}
}