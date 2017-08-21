<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/13/16
 * Time: 11:56 AM
 */

namespace Trumpet\TelegramBot;

class Config
{
    public static function getConfig()
    {
        return self::$local;
    }

    private static $production = [
        'telegram' => [
            'APIKey' => '259642159:AAH51bRYioOfhbQDZELOm6dJeoB2VbAFVGg',
            'botName' => 'SheypoorBot',
            'hookUrl' => 'https://telegram.sheypoor.com/e952252db0e07b857256b4d90be0e3b12d065f27.php',
            'certificatePath' => '../certificate.key',
            'commandsPath' => '../Commands/',
            'logPath' => '../log',
            'downloadPath' => '../Download',
            'uploadPath' => '../Upload',
            'culturePath' => '../locale',
            'locale' => 'fa'
        ],
        'mysql' => [
            'host' => 'localhost',
            'user' => 'bot',
            'password' => '$4curepa$s',
            'database' => 'telegrambot'
        ],
        'redis' => [
            'host' => 'localhost',
            'port' => 6379,
            'timeout' => 0
        ],
        'throttle' => [
            'timeLimit' => 3,
            'counterLimit' => 3
        ],
        'botan' => [
            'apiKey' => 't1aHL31Xm2FeyBkID1H8FciY2Ez6dHYe'
        ]
    ];

    private static $local = [
        'development' => true,
        'telegram' => [
            'APIKey' => '186776486:AAFcV8suIl_XxQB9cegTQnUmzlCvAg43RBk',
            'botName' => 'LooyanBot',
            'hookUrl' => 'https://77af899a.ngrok.io/public/hookz.php',
            'certificatePath' => null,
            'commandsPath' => '../Commands/',
            'logPath' => '../log',
            'downloadPath' => '../Download',
            'uploadPath' => '../Upload',
            'culturePath' => '../locale',
            'locale' => 'fa'
        ],
        'mysql' => '',
        'mysql1' => [
            'host' => '127.0.0.1',
            'user' => 'root',
            'password' => '',
            'database' => 'telegram'
        ],
        'redis' => [
            'host' => 'db',
            'port' => 6379,
            'timeout' => 0
        ],
        'throttle' => [
            'timeLimit' => 3,
            'counterLimit' => 3
        ]
    ];
}
