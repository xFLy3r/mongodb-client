<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require '../vendor/autoload.php';
require_once '../src/Service/Translator.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
//a:
//$line = readline('Type script: ');
//echo $line ."\n";
$db = $client->selectDatabase('test');

$sql1 = "select item, qty from test where item='pencil'";
echo $sql1;
$translator = new Translator();
$translate = $translator->getTranslate($sql1);
var_dump($translate);
$result = $db->selectCollection($translate['document'])
    ->find($translate['filter'], $translate['options'])
    ->toArray();
var_dump($result);
//goto a;


