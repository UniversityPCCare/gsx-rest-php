<?php

require_once("../src/GSXHandler.php");

$gsx = new UPCC\GSXHandler("test2", "0000123456");
$gsx->setActivationToken("dbacad16-61fe-4a6e-842c-a6dda53dec9x");
$gsx->testAuthentication();