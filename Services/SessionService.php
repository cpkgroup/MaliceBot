<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 3/12/17
 * Time: 4:41 PM
 */

namespace Trumpet\TelegramBot\Services;

class SessionService extends \Redis
{
    /**
     * Set the string value in argument as value of the key.
     *
     * @param   string $key
     * @param   string $value
     * @param   int $timeout [optional] Calling setex() is preferred if you want a timeout.
     * @return  bool    TRUE if the command is successful.
     * @link    http://redis.io/commands/set
     * @example $redis->set('key', 'value');
     */
    public function set($key, $value, $timeout = 86400)
    {
        return parent::set($key, $value, $timeout);
    }
}