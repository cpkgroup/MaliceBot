<?php

/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/31/16
 * Time: 2:26 PM
 */
namespace Trumpet\TelegramBot\Services\Structures;

use Longman\TelegramBot\Commands\Command;

class CallbackMessageStructure extends InputMessageAbstract
{

    /**
     * @var Command
     */
    private $command;

    /**
     * MessageStructure constructor.
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    protected function _getText()
    {
        if ($this->getUpdate()->getCallbackQuery()) {
            return $this->getUpdate()->getCallbackQuery()->getData();
        } else {
            return null;
        }
    }

    public function getUserId()
    {
        if ($this->getUpdate()->getCallbackQuery()) {
            return $this->getUpdate()->getCallbackQuery()->getFrom()->getId();
        } else {
            return null;
        }
    }

    public function getMessageId()
    {
        if ($this->getUpdate()->getCallbackQuery()) {
            return $this->getUpdate()->getCallbackQuery()->getMessage()->getMessageId();
        } else {
            return null;
        }
    }

    public function getCallbackQueryId()
    {
        if ($this->getUpdate()->getCallbackQuery()) {
            return $this->getUpdate()->getCallbackQuery()->getId();
        } else {
            return null;
        }
    }

    public function getCallbackQueryData()
    {
        if ($this->getUpdate()->getCallbackQuery() && $this->getUpdate()->getCallbackQuery()->getData()) {
            return json_decode($this->getUpdate()->getCallbackQuery()->getData(), true);
        } else {
            return [];
        }
    }

    public function getTelegram()
    {
        return $this->command->getTelegram();
    }

    public function getUpdate()
    {
        return $this->command->getUpdate();
    }

    public function getLocation()
    {
        return null;
    }

    public function getPhoto()
    {
        return null;
    }

    public function getDocument()
    {
        return null;
    }

    public function getContact()
    {
        return null;
    }
}
