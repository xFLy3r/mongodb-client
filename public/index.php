<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require '../vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");


$db = $client->selectDatabase('test');
$collection = $db->selectCollection('restaurants');



