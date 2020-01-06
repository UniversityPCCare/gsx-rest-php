<?php

require_once("../src/GSXHandler.php");
require_once("test_declarations.php");


$gsx = new UPCC\GSXHandler($userEmail, $shipTo);
$gsx->setActivationToken($activationToken);
$gsx->testAuthentication();

$product = $gsx->ProductDetails($serial);
$repair = $gsx->RepairSummary($repairNumber);
$article = $gsx->ArticleContentLookup($articleId);
$diagnostics = $gsx->DiagnosticsSuites($serial);

echo "<pre>";
echo "Product is a $product->configDescription\n";
echo "Retrieved $repair->totalNumberofRecords repairs\n";
echo "Article title is $article->title\n";
echo "Diagnostics Suite available: " . $diagnostics->suiteDetails[0]->suiteName . "\n";
echo "</pre>";