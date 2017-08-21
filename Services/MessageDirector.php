<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/31/16
 * Time: 2:30 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\Commands\Command;
use Trumpet\TelegramBot\Services\Structures\CallbackMessageStructure;
use Trumpet\TelegramBot\Services\Structures\ContactMessageStructure;
use Trumpet\TelegramBot\Services\Structures\DocumentMessageStructure;
use Trumpet\TelegramBot\Services\Structures\EditedMessageStructure;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;
use Trumpet\TelegramBot\Services\Structures\LocationMessageStructure;
use Trumpet\TelegramBot\Services\Structures\MessageStructure;
use Trumpet\TelegramBot\Services\Structures\PhotoMessageStructure;

class MessageDirector
{
    /**
     * @param Command $command
     * @return InputMessageAbstract
     */
    public static function build(Command $command)
    {
        $message = $command->getMessage();
        $update = $command->getUpdate();

        if ($update->getEditedMessage()) {
            return new EditedMessageStructure($command);
        }

        if (!is_callable([$message, 'getChat'])) {
            return new CallbackMessageStructure($command);
        }

        if ($message->getLocation()) {
            return new LocationMessageStructure($command);
        }

        if ($message->getPhoto()) {
            return new PhotoMessageStructure($command);
        }

        if ($message->getDocument()) {
            return new DocumentMessageStructure($command);
        }

        if ($message->getContact()) {
            return new ContactMessageStructure($command);
        }

        return new MessageStructure($command);
    }
}