<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/25/16
 * Time: 2:54 PM
 */

namespace Trumpet\TelegramBot\Services;


use Longman\TelegramBot\Exception\TelegramException;

class MysqlService
{
    private $pdo;
    /**
     * mysql constructor.
     * @param $credentials
     * @param string $encoding
     * @throws TelegramException
     */
    public function __construct($credentials, $encoding = 'utf8mb4')
    {
        $dsn = 'mysql:host=' . $credentials['host'] . ';dbname=' . $credentials['database'];
        $options = [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $encoding];
        try {
            $pdo = new \PDO($dsn, $credentials['user'], $credentials['password'], $options);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        } catch (\PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        $this->pdo = $pdo;
    }

    public function getPDO()
    {
        return $this->pdo;
    }
}