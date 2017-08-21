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
            'APIKey' => '365544425:AAEfYIoTV4QK2coF8RXZTs7G21-68Y3CjuM',
            'botName' => 'dayooooooosBot',
            'hookUrl' => 'https://yandexian.herokuapp.com/public/hookz.php',
            'certificatePath' => null,
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
            'hookUrl' => 'https://400e49df.ngrok.io/public/hookz.php',
            'certificatePath' => null,
            'commandsPath' => '../Commands/',
            'logPath' => '../log',
            'downloadPath' => '../Download',
            'uploadPath' => '../Upload',
            'culturePath' => '../locale',
            'locale' => 'fa'
        ],
        'mysql' => ''
    ];
}
