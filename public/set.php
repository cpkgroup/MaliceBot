<?php
// Load composer
require '../vendor/autoload.php';

try {
    $config = \Trumpet\TelegramBot\Config::getConfig();
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['telegram']['APIKey'], $config['telegram']['botName']);


    // Set webhook
    if (isset($config['telegram']['certificatePath'])) {
        $result = $telegram->setWebHook($config['telegram']['hookUrl'],
            ['certificate' => $config['telegram']['certificatePath']]
        );
    } else {
        $result = $telegram->setWebhook($config['telegram']['hookUrl']);
    }
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
