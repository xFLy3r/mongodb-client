<?php
$host = "localhost";
$port = "27017";
$username = null;
$password = null;
$db = null;

$answer = readline("Hello. Do u want to setup settings of db connection? If not, will be left default settings. y/n \n");
settings:
switch (trim(strtolower($answer))) {
    case 'y':
        $host = readline('Host: ') ?: 'localhost';
        echo "$host \n";
        $port = readline('Port: ') ?: '27017';
        echo "$port \n";
        $username = readline('Username: ') ?: null;
        echo "$username \n";
        $password = readline('Password: ') ?: null;
        echo "$password \n";
        $db = readline('Database: ') ?: null;
        echo "$db \n";
        break;
    default:
        break;
}
$uri = "mongodb://";

if ($username && $password) {
    $uri .= trim($username) . ":" . trim($password) . "@";
    }

    elseif ($username && !$password) {
    $uri .= trim($username) . "@";
    }
    elseif (!$username && $password) {
        echo "Password can't be without username. Try again: \n";
        goto settings;
    }
$uri .= "$host:$port";
try {
    $client = new MongoDB\Client($uri);
    $client->listDatabases();
}
catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $exception) {
    echo $exception->getMessage() . "\n Try again\n";
    goto settings;
}
