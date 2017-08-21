<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/26/16
 * Time: 3:40 PM
 */

// shared services
$di = \Trumpet\TelegramBot\Engine\DI::getDefault();
$config = $di->getConfig();

$di->set('status', '\Trumpet\TelegramBot\Services\StatusService');

$redisConfig = $config['redis'];
$di->set('session', function () use ($redisConfig) {
    $redis = new \Trumpet\TelegramBot\Services\SessionService();
    $redis->connect($redisConfig['host'], $redisConfig['port'], $redisConfig['timeout']);
    $redis->set('health', true);
    return $redis;
});

$mysqlConfig = $config['mysql'];
$di->set('mysql', function () use ($mysqlConfig) {
    $mysql = new \Trumpet\TelegramBot\Services\MysqlService($mysqlConfig);
    return $mysql;
});

$di->set('api', '\Trumpet\TelegramBot\Services\ApiService');

$di->set('category', '\Trumpet\TelegramBot\Services\CategoryService');

$di->set('location', '\Trumpet\TelegramBot\Services\LocationService');

$di->set('culture', '\Trumpet\TelegramBot\Services\CultureService');

$di->set('messageHelper', '\Trumpet\TelegramBot\Services\MessageService');

$di->set('numberFormat', '\Trumpet\TelegramBot\Services\NumberFormat');

$di->set('throttle', '\Trumpet\TelegramBot\Services\ThrottleService');

$di->set('health', '\Trumpet\TelegramBot\Services\HealthService');

$di->set('auth', '\Trumpet\TelegramBot\Services\AuthService');

function _T($str, $params = []) {
    global $di;
    return $di->culture->trans($str, $params);
}

function _C($str) {
    global $di;
    return $di->culture->command($str);
}
