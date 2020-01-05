<?php

require_once("../src/GSXHandler.php");

$userEmail = "test@example.com";
$shipTo = "0000123456";
$activationToken = "dbacad16-61fe-4a6e-842c-a6dda53dec9x";

$gsx = new UPCC\GSXHandler($userEmail, $shipTo);
$gsx->setActivationToken($activationToken);
$gsx->testAuthentication();