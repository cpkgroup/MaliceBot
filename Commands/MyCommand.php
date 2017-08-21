<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\InjectableTrait;
use Trumpet\TelegramBot\Services\MessageDirector;

/**
 * User "/my" command
 */
class MyCommand extends UserCommand
{
    use InjectableTrait;
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'my';
    protected $description = 'Show my listing in Sheypoor';
    protected $version = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = MessageDirector::build($this);
        $message->setText(null);
        return $this->messageHelper->executeSection(StatusList::MY_LISTING, $message);
    }
}
