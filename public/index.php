<?php

#error_reporting(E_ALL);
#ini_set('display_errors', '1');

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Service/Translator.php';

echo "Hello. Available commands: select \n";

$line = readline('Type script: ');
$translator = new Translator();
while ($line !== 'exit') {
    $result = $translator->setQuery($line)->getTranslate();
    if ($result === false) {
        echo "Syntax error \n";
    } else {
        print_r($result);
    }

    $line = readline('Type script: ');
}

echo "Goodbye! \n";

