<?php
// Load composer
require '../vendor/autoload.php';
mb_internal_encoding("UTF-8");
try {
    $config = \Trumpet\TelegramBot\Config::getConfig();
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['telegram']['APIKey'], $config['telegram']['botName']);

    // Enable MySQL
    if ($config['mysql']) {
        $telegram->enableMySQL($config['mysql']);
    }

    //// Enable MySQL with table prefix
    //$telegram->enableMySQL($mysql_credentials, $BOT_NAME . '_');

    //// Add an additional commands path
    $telegram->addCommandsPath($config['telegram']['commandsPath']);

    //// Here you can enable admin interface for the channel you want to manage
    //$telegram->enableAdmins(['your_telegram_id']);
    //$telegram->setCommandConfig('sendtochannel', ['your_channel' => '@type_here_your_channel']);

    //// Here you can set some command specific parameters,
    //// for example, google geocode/timezone api key for date command:
    //$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);

    //// Logging
    //    \Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
    \Longman\TelegramBot\TelegramLog::initErrorLog($config['telegram']['logPath'] . '/' . $config['telegram']['botName'] . '_error.log');
    // \Longman\TelegramBot\TelegramLog::initDebugLog($path . '/' . $BOT_NAME . '_debug.log');
    // \Longman\TelegramBot\TelegramLog::initUpdateLog($path . '/' . $BOT_NAME . '_update.log');

    //// Set custom Upload and Download path
    $telegram->setDownloadPath($config['telegram']['downloadPath']);
    $telegram->setUploadPath($config['telegram']['uploadPath']);

    //// Botan.io integration
    if (isset($config['botan']['apiKey'])) {
        $telegram->enableBotan($config['botan']['apiKey']);
    }

    /**
     * register shared services
     */
    include_once "../SharedServices.php";

    // Handle telegram webhook request
    $update = $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
//    throw $e;
    \Longman\TelegramBot\TelegramLog::error($e->getMessage());
}
