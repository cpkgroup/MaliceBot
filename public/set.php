<?php
// Load composer
require '../vendor/autoload.php';

try {
    \Trumpet\TelegramBot\Services\MessageService::setWebhook();
    /*
    $config = \Trumpet\TelegramBot\Config::getConfig();
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['telegram']['APIKey'], $config['telegram']['botName']);

    // Set webhook
    $result = $telegram->setWebHook($config['telegram']['hookUrl'], $config['telegram']['certificatePath']);

    // Uncomment to use certificate
    //$result = $telegram->setWebHook($hook_url, $path_certificate);

    if ($result->isOk()) {
        echo $result->getDescription();
    }
    */
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
