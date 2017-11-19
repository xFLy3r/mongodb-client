<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/setup.php';
require_once __DIR__ . '/../src/Service/Translator.php';

$translator = new Translator($client, $db);
echo "Hello. Available commands: select, use, db, show dbs, show collections \n";

$query = readline('Type script: ');
while ($query !== 'exit') {
    $result = $translator->setQuery($query)->getTranslate();

    if ($result === false) {
        echo "Error \n";
    } else {
        print_r($result);
    }

    $query = readline('Type script: ');
}

echo "Goodbye! \n";

