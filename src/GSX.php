<?php

namespace UPCC;

final class GSX {

	const CONSIGNMENT_DELIVERY_CODE_OPEN = "OPEN";
	const CONSIGNMENT_DELIVERY_CODE_ALL = "ALL";
	const CONSIGNMENT_DELIVERY_CODE_CLOSED = "CLOSED";
	
	const ARTICLE_PRODUCT_HELP = "PRODUCT_HELP";
	const ARTICLE_SERVICE_NEWS = "SERVICE_NEWS";
	const ARTICLE_OPERATIONAL_PROCEDURE = "OPERATIONAL_PROCEDURE";
	const ARTICLE_SERVICE_REPAIR_PROCESS = "SERVICE_REPAIR_PROCESS";
	const ARTICLE_SERVICE_DISK_IMAGE = "SERVICE_DISK_IMAGE";
	const ARTICLE_MANUALS = "MANUALS";
	const ARTICLE_RETAIL_PROCEDURE = "RETAIL_PROCEDURE";
	const ARTICLE_SPECIFICATIONS = "SPECIFICATIONS";
	const ARTICLE_SERVICE_VIDEOS = "SERVICE_VIDEOS";
	const ARTICLE_TECHNICAL_PROCEDURE = "TECHNICAL_PROCEDURE";
	const ARTICLE_SERVICE_MANUALS = "SERVICE_MANUALS";
	const ARTICLE_DOWNLOADS = "DOWNLOADS";
	const ARTICLE_HOWTO = "HOWTO_ARTICLES";
	
	const REPAIR_TYPE_SVNR = "SVNR"; #Service Non-Repair
	const REPAIR_TYPE_CRBR = "CRBR"; #Carry-in Return Before Replace
	const REPAIR_TYPE_DION = "DION"; #Onsite Service Direct
	const REPAIR_TYPE_MINS = "MINS"; #Mail-in Return to Service Location
	const REPAIR_TYPE_MINC = "MINC"; #Mail-in Return to Customer
	const REPAIR_TYPE_CINR = "CINR"; #Carry-in Non-Replenishment
	const REPAIR_TYPE_INON = "INON"; #Onsite Service Indirect
	const REPAIR_TYPE_CIN = "CIN"; #Carry-in
	const REPAIR_TYPE_WUMS = "WUMS"; #Whole Unit Mail-in Return to Service Location
	const REPAIR_TYPE_WUMC = "WUMC"; #Whole Unit Mail-in Return to Customer
	const REPAIR_TYPE_OSR = "OSR"; #Onsite Service Facilitated
	const REPAIR_TYPE_OSCR = "OSCR"; #Onsite Service Pickup
	
	const REPORTED_BY_TECH = "TECH";
	const REPORTED_BY_CUST = "CUST";
	
	const REPROD_A = "A"; #Not Applicable
	const REPROD_B = "B"; #Continuous
	const REPROD_C = "C"; #Intermittent
	const REPROD_D = "D"; #Fails After Warm Up
	const REPROD_E = "E"; #Environmental
	const REPROD_F = "F"; #Configuration: Peripheral
	const REPROD_G = "G"; #Damaged
	const REPROD_H = "H"; #Screening Request
	
	public static function validateUuid($guid) {
		return (bool) preg_match("/^[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}$/", $guid);
	}
	
	public static function isValidShipTo($shipTo) {
		return preg_match("/^[0-9A-Za-z]{1,10}$/", $shipTo);
	}
	
	public static function isValidDeviceIdentifier($id) {
		return preg_match("/^[0-9a-zA-Z]{1,18}$/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidProductIdentifier($id) {
		return preg_match("/^[a-z0-9A-Z]{1,10}$/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidRepairIdentifier($id) {
		return preg_match("/^[0-9A-Z]{1,15}$/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidDiagnosticSuiteIdentifier($id) {
		return preg_match("/^[0-9a-zA-Z]{1,40}$/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidComponentCode($componentCode) {
		return preg_match("/^.{1,7}$/", $componentCode);
		#Apple does not provide Regex, just a character limit
	}
	
	public static function isValidIssueCode($issueCode) {
		return preg_match("/^.{1,6}$/", $issueCode);
		#Apple does not provide Regex, just a character limit
	}
	
	public static function isValidPartNumber($partNumber) {
		return preg_match("/^[a-z0-9A-Z\-\/]{3,18}$/", $partNumber);
		#this is the regex provided by Apple
	}
	
	public static function isValidEeeCode($eeeCode) {
		return preg_match("/^[a-z0-9A-Z]{1,10}$/", $eeeCode);
		#this is the regex provided by Apple
	}
	
	public static function isValidReportedBy($reportedBy) {
		return ($reportedBy == self::REPORTED_BY_TECH or $reportedBy == self::REPORTED_BY_CUST);
		#ENUM {"TECH", "CUST"}
	}
	
	public static function isValidConsignmentDeliveryStatus($code) {
		switch ($code) {
			case self::CONSIGNMENT_DELIVERY_CODE_OPEN:
			case self::CONSIGNMENT_DELIVERY_CODE_ALL:
			case self::CONSIGNMENT_DELIVERY_CODE_CLOSED:
				return true;
		}
		return false;
	}
	
	public static function isValidConsignmentDeliveryNumber($deliveryNumber) {
		return preg_match("/^[0-9]{1,15}$/", $deliveryNumber);
	}
	
	public static function isValidArticleType($type) {
		$type = trim($type);
		switch ($type) {
			case self::ARTICLE_PRODUCT_HELP:
			case self::ARTICLE_SERVICE_NEWS:
			case self::ARTICLE_OPERATIONAL_PROCEDURE:
			case self::ARTICLE_SERVICE_REPAIR_PROCESS:
			case self::ARTICLE_SERVICE_DISK_IMAGE:
			case self::ARTICLE_MANUALS:
			case self::ARTICLE_RETAIL_PROCEDURE:
			case self::ARTICLE_SPECIFICATIONS:
			case self::ARTICLE_SERVICE_VIDEOS:
			case self::ARTICLE_TECHNICAL_PROCEDURE:
			case self::ARTICLE_SERVICE_MANUALS:
			case self::ARTICLE_DOWNLOADS:
			case self::ARTICLE_HOWTO:
				return true;
		}
		return false;
	}
	
	public static function isValidRepairType($repairType) {
		$repairType = trim($repairType);
		switch ($repairType) {
			case self::REPAIR_TYPE_SVNR:
			case self::REPAIR_TYPE_CRBR:
			case self::REPAIR_TYPE_DION:
			case self::REPAIR_TYPE_MINS:
			case self::REPAIR_TYPE_MINC:
			case self::REPAIR_TYPE_CINR:
			case self::REPAIR_TYPE_INON:
			case self::REPAIR_TYPE_CIN:
			case self::REPAIR_TYPE_WUMS:
			case self::REPAIR_TYPE_WUMC:
			case self::REPAIR_TYPE_OSR:
			case self::REPAIR_TYPE_OSCR:
				return true;
		}
		return false;
	}
	
	public static function isValidReproducibilityCode($code) {
		$code = trim($code);
		switch ($code) {
			case self::REPROD_A:
			case self::REPROD_B:
			case self::REPROD_C:
			case self::REPROD_D:
			case self::REPROD_E:
			case self::REPROD_F:
			case self::REPROD_G:
			case self::REPROD_H:
				return true;
		}
		return false;
	}
}