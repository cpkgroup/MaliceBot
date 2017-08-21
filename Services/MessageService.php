<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/3/16
 * Time: 1:33 PM
 */
namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Commands\Sections\SectionInterface;
use Trumpet\TelegramBot\Config;
use Trumpet\TelegramBot\Engine\Injectable;
use Trumpet\TelegramBot\Services\Structures\ContactMessageStructure;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class MessageService extends Injectable
{
    private $allMessages = [];

    /**
     * @param $api
     * @param $messageData
     * @deprecated
     * @return mixed
     */
    public function sendPhoto($api, $messageData)
    {
        $ch = curl_init('https://api.telegram.org/bot' . $api . '/sendPhoto');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function setWebhook()
    {
        $config = Config::getConfig();
        $ch = curl_init('https://api.telegram.org/bot' . $config['telegram']['APIKey'] . '/setWebhook');

        $data = ['url' => $config['telegram']['hookUrl']];
        if ($config['telegram']['certificatePath']) {
            $data['certificate'] = curl_file_create($config['telegram']['certificatePath']);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function summarize($text, $length = 100)
    {
        $text = strip_tags($text);
        $text = str_replace(["\n", "\r"], ' ', $text);
        $text = preg_replace('/ +/', ' ', $text);

        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length);
            $text = mb_substr($text, 0, mb_strrpos($text, ' '));
            $etc = ' ...';
            $text = $text . $etc;
        }
        return $text;
    }

    public function setContact(ContactMessageStructure $message)
    {
        $userId = $message->getUserId();
        $userInfo = $this->getUserInfo($userId);
        if ($message->getContact()->getUserId() == $message->getUserId()) {
            $userInfo['contact'] = [
                'mobile' => $message->getContact()->getPhoneNumber(),
                'name' => $message->getContact()->getFirstName(),
                'family' => $message->getContact()->getLastName()
            ];
            $this->setUserInfo($userId, $userInfo);
        } else {
            return false;
        }
        return $userInfo;
    }

    public function getUserInfo($userId)
    {
        $userInfo = $this->session->get(RedisConstant::USER_INFO . $userId);
        $userInfo = $userInfo ? json_decode($userInfo, true) : [];
        return $userInfo;
    }

    public function getUserInfoFromDB($userId)
    {
        try {
            $pdo = $this->mysql->getPDO();
            $sth = $pdo->prepare('SELECT * FROM `user` WHERE `id` = :id ');
            $sth->bindParam(':id', $userId, \PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            TelegramLog::error($e->getMessage());
            throw $e;
        }
    }

    public function setUserInfo($userId, $userInfo)
    {
        $this->session->set(RedisConstant::USER_INFO . $userId, json_encode($userInfo));
    }

    public function setUserInfoDB($userId, $data)
    {
        try {
            $pdo = $this->mysql->getPDO();
            // insert user id in db if 1% it doesn't exist
            $query = 'INSERT IGNORE INTO `user` ( `id`) values ( :userId )';
            $sth = $pdo->prepare($query);
            $sth->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $sth->execute();
            // update token for user
            $sth = $pdo->prepare('UPDATE `user` SET 
              token = :token,
              email = :email,
              mobile = :mobile,
              apiUserId = :apiUserId
            WHERE `id` = :id ');
            $sth->bindParam(':token', $data['token'], \PDO::PARAM_STR);
            $sth->bindParam(':email', $data['email'], \PDO::PARAM_STR);
            $sth->bindParam(':mobile', $data['mobile'], \PDO::PARAM_STR);
            $sth->bindParam(':apiUserId', $data['apiUserId'], \PDO::PARAM_INT);
            $sth->bindParam(':id', $userId, \PDO::PARAM_INT);
            $sth->execute();
            return true;
        } catch (\Exception $e) {
            TelegramLog::error($e->getMessage());
            throw $e;
        }
    }

    public function resetDBToken($userId)
    {
        try {
            $pdo = $this->mysql->getPDO();
            // update token for user
            $sth = $pdo->prepare('UPDATE `user` SET token = null WHERE `id` = :id ');
            $sth->bindParam(':id', $userId, \PDO::PARAM_INT);
            $sth->execute();
            return true;
        } catch (\Exception $e) {
            TelegramLog::error($e->getMessage());
            throw $e;
        }
    }

    public function log($userId, $text)
    {
        $data = [];
        $data['chat_id'] = $userId;
        $data['text'] = $text;
        $data['parse_mode'] = 'HTML';

        return Request::sendMessage($data);
    }

    public function sendMessage($userId, $text)
    {
        $data = [];
        $data['chat_id'] = $userId;
        $data['text'] = $text;
        $data['parse_mode'] = 'HTML';
        $data['reply_markup'] = new ReplyKeyboardHide([
            'remove_keyboard' => true
        ]);
        if (!isset($this->allMessages[sha1($text)])) {
            $this->allMessages[sha1($text)] = true;
            return Request::sendMessage($data);
        }
    }

    public function filterText($text)
    {
        // strip html tags
        $text = strip_tags($text);
        // removing an special whitespace - don't change/copy/paste this line
        $text = str_replace(' ', '', $text);
        $text = str_replace(' ', ' ', $text);
        $text = str_replace('-->', '-- >', $text);

        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $text = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $text = preg_replace($regexSymbols, '', $text);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $text = preg_replace($regexTransport, '', $text);

        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $text = preg_replace($regexMisc, '', $text);

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $text = preg_replace($regexDingbats, '', $text);


        // x5c
        // Remove all invalid characters
        // $regexEmoticons = '/[^a-z0-9 آ-ی \-\_\"\\\'\p{L} \x20-\x5b \x5e-\x7E]/ui';
        // $text = preg_replace($regexEmoticons, '', $text);

        return $text;
    }

    public function setNavigationButton($messageData, $buttons = [], $key = 'text')
    {
        $buttons = $buttons ? $buttons : [
            ['command' => CommandList::BACK, $key => _C('BACK_LTR')],
            ['command' => CommandList::MAIN_MENU, $key => _C('MAIN_MENU')],
        ];
        $messageData[$key] .= "\n";
        foreach ($buttons as $button) {
            if (!isset($button['command'])) {
                $messageData[$key] .= "\n";
            } else {
                $messageData[$key] .= "\n" . $button['command'] . '    ' . $button[$key];
            }
        }
        return $messageData;
    }

    public function executeSection($section, InputMessageAbstract $message)
    {
        // prevent flood
        $throttleConfig = $this->getConfig()['throttle'];
        if ($this->throttle->isThrottled($message->getUserId(), $throttleConfig['timeLimit'], $throttleConfig['counterLimit'])) {
            return Request::emptyResponse();
        }
        $tmp = explode(':', $section);
        $commandClass = $tmp[0];
        $commandMethod = isset($tmp[1]) ? $tmp[1] : 'start';
        $commandParam = isset($tmp[2]) ? $tmp[2] : null;
        /** @var SectionInterface $sectionObj */
        $sectionObj = new $commandClass();
        return $sectionObj->$commandMethod($message, $commandParam);
    }

    public function createSearchKeyboard($items, $page, $messageData, &$searchData, $countPage, $itemsKey = 'details')
    {
        $keyboards = [];
        $keyboards[] = [_C('BACK'), _C('MAIN_MENU')];
        $searchData[$itemsKey] = [];
        foreach ($items as $listing) {
            $listingStr = trim($listing['name'] . '، ' . $listing['locationName']); // . '،' . $listing->date
            $searchData[$itemsKey][$listingStr] = $listing['id'];
            $keyboards[] = [$listingStr];
        }
        $searchData['page'] = $page;
        $navigatorKeyboard = [];
        if ($countPage > $page) {
            $navigatorKeyboard[] = _C('NEXT');
        }
        if ($page > 1) {
            $navigatorKeyboard[] = _C('PREVIOUS');
        }
        if ($navigatorKeyboard) {
            $keyboards[] = $navigatorKeyboard;
        }

        $messageData['reply_markup'] = new ReplyKeyboardMarkup(
            [
                'keyboard' => $keyboards,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
                'selective' => true
            ]
        );
        return $messageData;
    }

    public function listingUrl($listingId)
    {
        return ApiService::SHEYPOOR_URL_SHORT . '/t/' . $listingId;
    }

    public function activateListingUrl($token, $listingId)
    {
        $activateUrl = ApiService::SHEYPOOR_URL . '/session/activate/' . $token . '/' . $listingId;
        $activateUrl .= '?utm_source=Telegram&utm_campaign=Telegram-Postlisting&utm_medium=Bot-postlisting';
        return $activateUrl;
    }
}
