<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/25/16
 * Time: 4:34 PM
 */

namespace Trumpet\TelegramBot\Commands\Sections;

use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class SupportSection extends SectionAbstract
{
    /**
     * @param InputMessageAbstract $message
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function start(InputMessageAbstract $message)
    {
        return Request::sendMessage([
            'chat_id' => $message->getUserId(),
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => false,
            'text' => _T('Support-Text')
        ]);
    }
}
