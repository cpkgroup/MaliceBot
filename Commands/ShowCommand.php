<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\InjectableTrait;
use Trumpet\TelegramBot\Services\MessageDirector;

/**
 * User "/show" command
 */
class ShowCommand extends UserCommand
{
    use InjectableTrait;
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'show';
    protected $description = 'Show listing in Sheypoor';
    protected $usage = '/show <listing id>';
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
        $text = (int)$this->numberFormat->englishNumber($text);
        $message = MessageDirector::build($this);
        if (!$text) {
            $message->setText(null);
            return $this->messageHelper->executeSection(StatusList::SHOW_LISTING_START, $message);
        }
        $message->setText(null);
        return $this->messageHelper->executeSection(StatusList::SHOW_LISTING_DETAIL . ':' . $text, $message);
    }
}
