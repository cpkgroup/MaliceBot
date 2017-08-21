<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/26/16
 * Time: 2:34 PM
 */

namespace Trumpet\TelegramBot\Commands\Sections;

use Longman\TelegramBot\Botan;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class StartSection extends SectionAbstract
{
    /**
     * @param InputMessageAbstract $message
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function start(InputMessageAbstract $message)
    {
        $txt = $message->getText();

        if (preg_match('/#abjo\s*(\d+)/', $txt, $matches)) {
            $countAll = 24;
            $userName = $message->getFrom() . ' #';
            $beers = file_get_contents('test.txt');
            $beersArray = explode("\r\n", $beers);
            $isNew = true;
            foreach ($beersArray as $key => $item) {
                if (strstr($item, $userName)) {
                    $isNew = false;
                    $beersArray[$key] = $userName . $matches[1];
                } else if (preg_match('/.+#(\d+)/', $item, $matche2)) {
                    $countAll -= $matche2[1];
                }
            }
            if ($isNew) {
                $beersArray[] = $userName . $matches[1];
            }

            foreach ($beersArray as $key => $item) {
                if (strstr($item, 'Remained beer:')) {
                    $beersArray[$key] = 'Remained beer: ' . ($countAll - $matches[1]);
                }
            }

            if ($countAll - $matches[1] < 0) {
                $this->messageHelper->sendMessage($message->getUserId(), 'Out of stock!');
                return $this->messageHelper->sendMessage($message->getUserId(), file_get_contents('test.txt'));
            }

            file_put_contents('test.txt', implode("\r\n", $beersArray));
            return $this->messageHelper->sendMessage($message->getUserId(), file_get_contents('test.txt'));

        } else if ($txt == '#abjo') {

            return $this->messageHelper->sendMessage($message->getUserId(), file_get_contents('test.txt'));

        } else {
            return Request::emptyResponse();
        }


        $userId = $message->getUserId();
        $this->status->setStatus($userId, null, [], true);

        $data = [];
        $data['chat_id'] = $userId;
        $data['parse_mode'] = 'Markdown';
        $data['text'] = _T('Welcome to sheypoor');

        $keyboard = [];
        $keyboard[] = [_C('POST_LISTING')];
        $keyboard[] = [_C('MY_LISTING'), _C('SHOW_LISTING')];
        $keyboard[] = [_C('DOWNLOAD_APP'), _C('SUPPORT')];

        $buttons = [];
        $buttons[] = [
            'command' => CommandList::SHOW_LISTING,
            'text' => _C('SHOW_LISTING_LTR')
        ];
        $buttons[] = [
            'command' => CommandList::POST_LISTING,
            'text' => _C('POST_LISTING_LTR')
        ];
        $buttons[] = [
            'command' => CommandList::MY_LISTING,
            'text' => _C('MY_LISTING_LTR')
        ];
        $buttons[] = [];
        $buttons[] = [
            'command' => CommandList::MAIN_MENU,
            'text' => _C('MAIN_MENU')
        ];
        $data = $this->messageHelper->setNavigationButton($data, $buttons);

//        $res = Botan::shortenUrl('https://github.com/akalongman/php-telegram-bot', $userId);
//        TelegramLog::error(var_export($res, true));

        $data['reply_markup'] = new ReplyKeyboardMarkup(
            [
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
                'selective' => true
            ]
        );
        return Request::sendMessage($data);
    }
}
