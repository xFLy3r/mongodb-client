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

$sql1 = "select address from restaurants order by field asc skip 0 limit 5";
echo $sql1;
$translator = new Translator();
$translate = $translator->getTranslate($sql1);
if ($translate === false) {
    echo 'Syntax error';
} else {
    $result = $db->selectCollection($translate['document'])
        ->find($translate['filter'], $translate['options']);
    var_dump($result->toArray());
}

//goto a;


