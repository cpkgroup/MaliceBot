<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/31/16
 * Time: 9:53 AM
 */

namespace Trumpet\TelegramBot\Services\Interfaces;


use Longman\TelegramBot\Telegram;

interface InputMessageInterface
{
    public function getText();
    public function getUserId();
    public function getMessageId();
    public function getCallbackQueryId();
    public function getCallbackQueryData();
    public function getLocation();
    public function getPhoto();
    public function getContact();
    public function getDocument();
    /**
     * @return Telegram
     */
    public function getTelegram();
    public function getUpdate();
}