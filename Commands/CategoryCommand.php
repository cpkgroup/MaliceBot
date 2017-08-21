<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\InjectableTrait;
use Trumpet\TelegramBot\Services\CategoryService;
use Trumpet\TelegramBot\Services\MessageDirector;

/**
 * User "/category" command
 */
class CategoryCommand extends UserCommand
{
    use InjectableTrait;
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'category';
    protected $description = 'Show category in Sheypoor';
    protected $usage = '/category <category id>';
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
        $categoryParam = '';
        if ($text) {
            $categoryService = new CategoryService();
            $category = $categoryService->loadCategory($text);
            if (isset($category['Id'])) {
                $categoryParam = ':' . $category['Id'];
            }
        }

        $message = MessageDirector::build($this);
        $message->setText(null);
        return $this->messageHelper->executeSection(StatusList::SHOW_LISTING_SET_CATEGORY . $categoryParam, $message);
    }
}
