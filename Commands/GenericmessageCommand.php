<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'Genericmessage';
    protected $description = 'Handle generic message';
    protected $version = '1.0.2';
    protected $validImageTypes = ['image/png', 'image/jpeg'];
    /**#@-*/

    /**
     * Execute command
     *
     * @return boolean
     */
    public function execute()
    {

        $chat_id = $this->getMessage()->getChat()->getId();
//        $chat_id = $this->getMessage()->getFrom()->getId();
//        file_put_contents('aaa.txt', file_get_contents('aaa.txt')."\r\n"."\r\n" .  json_encode($this->getUpdate()));

//        return Request::emptyResponse();

        $users = [
            '@sepehrom',
            '@Payamohajeri',
            '@SSeyfi',
            '@habibimh',
        ];
        $messageFooter = implode(' ', $users);

        $messageFooter .= '
        گروه سکس چت
بی جنبه نیاد
حشریم زده بالا 
https://telegram.me/joinchat/AAAAAD_QTTIAxOhAS9yGWQ
بیا بکون توش 💋
💦';
        $txt = file_get_contents('fohshz.txt');
        $fohshz = explode("\n", $txt);
        $fohsh = $fohshz[rand(0, count($fohshz) -1 )];

        $data = [];
        $data['chat_id'] = $chat_id;
        $data['text'] =  $fohsh . "\r\n" . $messageFooter;
        $data['reply_to_message_id'] = $this->getMessage()->getMessageId();

        Request::sendMessage($data);
        sleep(3);
        for($i=0 ; $i < 1000 ; $i++) {
            foreach ($fohshz as $item) {
                $data['text'] =  $item . "\r\n" . $messageFooter;
                $data['reply_to_message_id'] = null;
                Request::sendMessage($data);
                sleep(3);
            }
        }

        return Request::emptyResponse();
    }
}
