<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Service/Translator.php';

echo "Hello. Available commands: select \n";
a:
$line = readline('Type script: ');
//echo $line ."\n";
$translator = new Translator($line);
$result = $translator->getTranslate();
if ($result === false) {
    echo "Syntax error \n";
} else {
    print_r($result->toArray());
}

goto a;


