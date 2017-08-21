<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\InjectableTrait;
use Trumpet\TelegramBot\Services\MessageDirector;

/**
 * User "/search" command
 */
class SearchCommand extends UserCommand
{
    use InjectableTrait;
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'search';
    protected $description = 'Search in Sheypoor';
    protected $usage = '/search <keyword>';
    protected $version = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $text = trim($message->getText(true));
        $text = $this->messageHelper->filterText($text);
        $message = MessageDirector::build($this);
        if ($text) {
            $message->setText($text);
        } else {
            $message->setText(null);
        }
        return $this->messageHelper->executeSection(StatusList::SHOW_LISTING_GET_KEYWORD, $message);
    }
}
