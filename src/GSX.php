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
	
	public static function validateUuid($guid) {
		return (bool) preg_match("/[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}/", $guid);
	}
	
	public static function isValidProductIdentifier($id) {
		return preg_match("/^[0-9a-zA-Z]{1,18}$/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidRepairIdentifier($id) {
		return preg_match("/[0-9A-Z]{1,15}/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidDiagnosticSuiteIdentifier($id) {
		return preg_match("/[0-9a-zA-Z]{1,40}/", $id);
		#this is the regex provided by Apple
	}
	
	public static function isValidComponentCode($componentCode) {
		return preg_match("/.{1,7}/", $componentCode);
		#Apple does not provide Regex, just a character limit
	}
	
	public static function isValidIssueCode($issueCode) {
		return preg_match("/.{1,6}/", $issueCode);
		#Apple does not provide Regex, just a character limit
	}
	
	public static function isValidReportedBy($reportedBy) {
		return ($reportedBy == "TECH" or $reportedBy == "CUST");
		#ENUM {"TECH", "CUSTOMER"}
	}
	
	public static function isValidConsignmentDeliveryCode($code) {
		switch ($code) {
			case GSX::CONSIGNMENT_DELIVERY_CODE_OPEN:
			case GSX::CONSIGNMENT_DELIVERY_CODE_ALL:
			case GSX::CONSIGNMENT_DELIVERY_CODE_CLOSED:
				return true;
		}
		return false;
	}
	
	public static function isValidArticleType($type) {
		$type = trim($type);
		switch ($type) {
			case GSX::ARTICLE_PRODUCT_HELP:
			case GSX::ARTICLE_SERVICE_NEWS:
			case GSX::ARTICLE_OPERATIONAL_PROCEDURE:
			case GSX::ARTICLE_SERVICE_REPAIR_PROCESS:
			case GSX::ARTICLE_SERVICE_DISK_IMAGE:
			case GSX::ARTICLE_MANUALS:
			case GSX::ARTICLE_RETAIL_PROCEDURE:
			case GSX::ARTICLE_SPECIFICATIONS:
			case GSX::ARTICLE_SERVICE_VIDEOS:
			case GSX::ARTICLE_TECHNICAL_PROCEDURE:
			case GSX::ARTICLE_SERVICE_MANUALS:
			case GSX::ARTICLE_DOWNLOADS:
			case GSX::ARTICLE_HOWTO:
				return true;
		}
		return false;
	}
}