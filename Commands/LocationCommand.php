<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\InjectableTrait;
use Trumpet\TelegramBot\Services\MessageDirector;

/**
 * User "/location" command
 */
class LocationCommand extends UserCommand
{
    use InjectableTrait;
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'location';
    protected $description = 'Get Location in Sheypoor';
    protected $version = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = MessageDirector::build($this);
        $message->setText(null);
        return $this->messageHelper->executeSection(StatusList::SHOW_LISTING_GET_LOCATION, $message);
    }
}
