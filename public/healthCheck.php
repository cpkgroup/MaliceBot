<?php
// Load composer
require '../vendor/autoload.php';
mb_internal_encoding("UTF-8");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once "../SharedServices.php";

/** @var \Trumpet\TelegramBot\Services\HealthService $health */
$health = $di->health;
$healthStatus = true;
if (!$health->checkRedis()) {
    if ($healthStatus) {
        header('HTTP/1.1 400 Bad Request', true, 400);
    }
    echo 'Redis failed! please check redis';
    echo "\n\n";
    $healthStatus = false;
}

if (!$health->checkApi()) {
    if ($healthStatus) {
        header('HTTP/1.1 400 Bad Request', true, 400);
    }
    echo 'Api failed! please check Sheypoor API!';
    echo "\n\n";
    $healthStatus = false;
}

if (!$health->checkMysql()) {
    if ($healthStatus) {
        header('HTTP/1.1 400 Bad Request', true, 400);
    }
    echo 'Mysql failed! please check Mysql connection and tables!';
    echo "\n\n";
    $healthStatus = false;
}

if ($healthStatus) {
    echo 'ok';
}
