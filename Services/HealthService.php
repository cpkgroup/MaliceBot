<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/25/16
 * Time: 2:52 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\TelegramLog;
use Trumpet\TelegramBot\Engine\Injectable;

class HealthService extends Injectable
{
    public function checkRedis()
    {
        return $this->session->get('health');
    }

    public function checkApi()
    {
        return $this->api->checkHealth();
    }

    public function checkMysql()
    {
        try {
            $pdo = $this->mysql->getPDO();
            $sth = $pdo->prepare('SELECT COUNT(*) FROM `user`');
            $sth->execute();
            if ($sth->fetchColumn() > 0) {
                return true;
            } else {
                TelegramLog::error('Users table was empty!');
            }
        } catch (\Exception $e) {
            TelegramLog::error($e->getMessage());
        }
        return false;
    }
}
