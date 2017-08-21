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
        return self::$production;
    }

    private static $production = [
        'telegram' => [
            'APIKey' => '186776486:AAFcV8suIl_XxQB9cegTQnUmzlCvAg43RBk',
            'botName' => 'LooyanBot',
            'hookUrl' => 'https://yandexian.herokuapp.com/public/hookz.php',
            'certificatePath' => null,
            'certificatePath2' => '../certificate.key',
            'commandsPath' => '../Commands/',
            'logPath' => '../log',
            'downloadPath' => '../Download',
            'uploadPath' => '../Upload',
            'culturePath' => '../locale',
            'locale' => 'fa'
        ],
        'mysql' => ''
    ];

    private static $local = [
        'development' => true,
        'telegram' => [
            'APIKey' => '186776486:AAFcV8suIl_XxQB9cegTQnUmzlCvAg43RBk',
            'botName' => 'LooyanBot',
            'hookUrl' => 'https://yandexian.herokuapp.com/public/hookz.php',
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
