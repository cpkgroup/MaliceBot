<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/26/16
 * Time: 10:31 AM
 */

namespace Trumpet\TelegramBot\Engine;

interface DIInterface
{
    public function set($name, $definition, $shared);
    public function get($name);
    public static function getDefault();
}
