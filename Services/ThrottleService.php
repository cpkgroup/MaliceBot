<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/25/16
 * Time: 2:59 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\TelegramLog;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Engine\Injectable;

class ThrottleService extends Injectable
{
    /**
     * This is a general throttle function. Each time it is called the user ID is
     * logged and if more than counterLimit calls is made during timeLimit seconds, the
     * user is throttled. The result is stored between sessions.
     *
     * @param int $userId
     * @param int $timeLimit
     * @param int $counterLimit
     * @param string $context
     * @return true if user is throttled, i.e. blocked.
     */
    public function isThrottled($userId, $timeLimit = 20, $counterLimit = 5, $context = 'default')
    {
        $currentTime = time();
        $key = RedisConstant::THROTTLE . $context . '_' . $userId;
        $data = $this->session->get($key) ? json_decode($this->session->get($key), true) : [];

        $data = array_filter($data, function ($v) use ($timeLimit, $currentTime) {
            if ($v + $timeLimit > $currentTime) {
                return true;
            }
        });

        $data[] = $currentTime;
        $this->session->set($key, json_encode($data));

        if (count($data) > $counterLimit) {
            TelegramLog::error('Throttled: ' . $userId);
            return true;
        }
        return false;
    }
}