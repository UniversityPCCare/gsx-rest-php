<?php

require_once("../src/GSXHandler.php");
require_once("test_declarations.php");


$gsx = new UPCC\GSXHandler($userEmail, $shipTo);
$gsx->setActivationToken($activationToken);
$gsx->testAuthentication();
var_dump($gsx->ProductDetails($serial));