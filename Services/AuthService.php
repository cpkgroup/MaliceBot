<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 11/13/16
 * Time: 3:38 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\Injectable;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class AuthService extends Injectable
{
    /**
     * @param InputMessageAbstract $message
     * @param string $status
     * @param bool $loggedInStatus
     * @param string $notifyMessage
     * @return bool|mixed
     * @throws \Exception
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function auth(InputMessageAbstract $message, $status = StatusList::MY_LISTING_AUTH, &$loggedInStatus = false, &$notifyMessage = '')
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $this->status->setStatus($userId, $status);
        $userInfo = $this->messageHelper->getUserInfo($userId);

        // check login token
        $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);
        if ($userInfoDb['token']) {
            $loggedInStatus = true;
            return true;
        }

        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'text' => '',
            'reply_markup' => new ReplyKeyboardHide(
                [
                    'remove_keyboard' => true
                ]
            )
        ];
        if (!isset($userInfo['contact']['mobile'])) {
            $keyboard = [];
            $keyboard[] = [
                [
                    'text' => _C('SEND_CONTACT'),
                    'request_contact' => true
                ]
            ];
            $messageData['text'] .= _T('Please send your contact information by below button');
            $messageData['reply_markup'] = new ReplyKeyboardMarkup(
                [
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false,
                    'selective' => true
                ]
            );
        } else if ($text && isset($userInfo['token'])) {
            $mobile = preg_replace('/^\+?98/', '0', $userInfo['contact']['mobile']);
            $text = $this->messageHelper->filterText($text);
            $text = $this->numberFormat->englishNumber($text);
            $verify = $this->api->verify($mobile, $text, $userInfo['token']);
            if ($verify) {
                unset($userInfo['token']);
                $this->messageHelper->setUserInfoDB($userId, [
                    'token' => $verify['ticket'],
                    'apiUserId' => $verify['user']['id'],
                    'email' => $verify['user']['email'],
                    'mobile' => $verify['user']['mobile']
                ]);

                $notifyMessage = _T('Your mobile number verified successfully');
                $loggedInStatus = true;
                return true;
            } else {
                $messageData['text'] .= _T('Activation code is wrong');
            }
        } else {
            $mobile = preg_replace('/^\+?98/', '0', $userInfo['contact']['mobile']);
            $token = $this->api->auth($mobile);
            if ($token) {
                $userInfo['token'] = $token;
                $this->messageHelper->setUserInfo($userId, $userInfo);
                $messageData['text'] .= _T('Activation code sent! please type activation numbers here');
            } else {
                $messageData['text'] .= _T('Something is wrong! please try again later');
            }
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }
}
