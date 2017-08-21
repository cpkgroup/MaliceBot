<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/26/16
 * Time: 10:30 AM
 */

namespace Trumpet\TelegramBot\Engine;

/**
 * Class Injectable
 * @package Trumpet\TelegramBot\Engine
 */
abstract class Injectable implements InjectionAwareInterface
{
    use InjectableTrait;
}