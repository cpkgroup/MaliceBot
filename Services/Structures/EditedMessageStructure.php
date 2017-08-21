<?php

/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/31/16
 * Time: 2:26 PM
 */
namespace Trumpet\TelegramBot\Services\Structures;

use Longman\TelegramBot\Commands\Command;

class EditedMessageStructure extends InputMessageAbstract
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
        return $this->command->getUpdate()->getEditedMessage()->getText();
    }

    public function getUserId()
    {
        return $this->command->getUpdate()->getEditedMessage()->getChat()->getId();
    }

    public function getMessageId()
    {
        return $this->command->getUpdate()->getEditedMessage()->getMessageId();
    }

    public function getCallbackQueryId()
    {
        return null;
    }

    public function getCallbackQueryData()
    {
        return [];
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
