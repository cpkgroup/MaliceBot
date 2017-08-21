<?php

namespace Trumpet\TelegramBot\Commands\Sections;

use Longman\TelegramBot\Commands\SystemCommands\StartCommand;
use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Services\ApiService;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class MyListingSection extends SectionAbstract
{
    /**
     * @param InputMessageAbstract $message
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function start(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        switch ($message->getText()) {
            case _C('POST_LISTING'):
                return $this->messageHelper->executeSection(StatusList::POST_LISTING, $message);
        }
        $this->status->setStatus($userId, StatusList::MY_LISTING);
        $userInfo = $this->messageHelper->getUserInfoFromDB($userId);
        if (!$userInfo['token']) {
            return $this->auth($message);
        } else {
            return $this->myListing($message);
        }
    }

    /**
     * @param InputMessageAbstract $message
     * @param string $notifyMessage
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function myListing(InputMessageAbstract $message, $notifyMessage = '')
    {
        $userId = $message->getUserId();
        $text = $message->getText();

        $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);
        $userInfo = $this->messageHelper->getUserInfo($userId);
        if (!$userInfoDb['token']) {
            $message->setText(null);
            return $this->auth($message);
        }
        $searchData = $this->getMyListingData($userId);

        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }

        $number = 0;
        $page = 1;
        switch ($text) {
            case CommandList::NEXT:
            case _C('NEXT'):
                $number = 1;
                $text = null;
                break;
            case CommandList::PREVIOUS:
            case _C('PREVIOUS'):
                $number = -1;
                $text = null;
                break;
        }

        if (strstr($text, CommandList::UP_TO_DATE)) {
            if ($listingId = explode('_', $text)[1]) {
                return $this->upToDate($message, $listingId);
            }
        }

        if (strstr($text, CommandList::REMOVE)) {
            if ($listingId = explode('_', $text)[1]) {
                $userInfo['currentListingId'] = $listingId;
                $this->messageHelper->setUserInfo($userId, $userInfo);
                return $this->remove($message);
            }
        }


        if ($number) {
            $page = isset($searchData['page']) ? $searchData['page'] + $number : 1;
        }

        if (isset($searchData['myListing'][$text])) {
            // show edit here
            return $this->detail($message, $searchData['myListing'][$text], $userInfoDb);
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

        $skip = ($page - 1) * $this->limit;
        $messageData['text'] .= '*' . _T('My listing') . "*\n\n";
        $ticket = $userInfoDb['token'];
        $listings = $this->api->getUserListing($ticket);

        // token is expired!
        if (!$listings) {
            $this->messageHelper->resetDBToken($userId);
            return $this->auth($message);
        }

        if (count($listings['items']) <= 0) {
            $messageData['text'] .= _T('Result not found, please add new listing now');
            $keyboards = [];
            $keyboards[] = [_C('BACK'), _C('MAIN_MENU')];
            $keyboards[] = [_C('POST_LISTING')];
            $messageData['reply_markup'] = new ReplyKeyboardMarkup(
                [
                    'keyboard' => $keyboards,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false,
                    'selective' => true
                ]
            );
            $buttons = [];
            $buttons[] = [
                'command' => CommandList::POST_LISTING,
                'text' => _C('POST_LISTING_LTR')
            ];
            $buttons[] = [];
            $buttons[] = [
                'command' => CommandList::BACK,
                'text' => _C('BACK_LTR')
            ];
            $buttons[] = [
                'command' => CommandList::MAIN_MENU,
                'text' => _C('MAIN_MENU')
            ];
            $messageData = $this->messageHelper->setNavigationButton($messageData, $buttons);

        } else {
            $listings['items'] = array_slice($listings['items'], $skip, $this->limit);
            $allPages = ceil($listings['totalCount'] / $this->limit);
            $messageData['text'] .= _T('Search-Result-Message', [
                ':totalCount' => $this->numberFormat->persianNumber($listings['totalCount']),
                ':page' => $this->numberFormat->persianNumber($page),
                ':allPages' => $this->numberFormat->persianNumber($allPages)
            ]);
            $messageData['text'] .= "\n" . _T('For show or change listing please use below buttons');
            $messageData = $this->messageHelper->createSearchKeyboard($listings['items'], $page, $messageData, $searchData, $allPages, 'myListing');
            $this->setMyListingData($userId, $searchData);
        }
        return Request::sendMessage($messageData);
    }

    /**
     * @param InputMessageAbstract $message
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function auth(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $status = StatusList::MY_LISTING_AUTH;
        $auth = $this->auth->auth($message, $status, $loggedInStatus, $notifyMessage);
        if ($loggedInStatus) {
            $this->status->removeStatus($userId, $userInfo, $status);
            $message->setText(null);
            return $this->myListing($message, $notifyMessage);
        } else {
            return $auth;
        }
    }

    private function detail(InputMessageAbstract $message, $listingId = null, $userInfoDb = [])
    {
        if (!$listingId) {
            return $this->start($message);
        }
        $userId = $message->getUserId();
        $listing = $this->api->listingDetails($listingId);
        $messageData = [];
        $messageData['chat_id'] = $userId;
        $messageData['parse_mode'] = 'HTML';
        $messageData['disable_web_page_preview'] = true;

        if (!$listing) {
            $messageData['text'] = _T('Listing-Details-Not-Found', [
                ':listingId' => $listingId
            ]);
            return Request::sendMessage($messageData);
        }
        $footerText = "\n\n" . ' <a href="http://' . $this->messageHelper->listingUrl($listingId) . '">' . _T('Show more details') . '</a>';
        $footerText .= "\n\n" . ' <a href="http://' . ApiService::SHEYPOOR_URL_SHORT . '/listing/edit/' . $listingId . '?xTicket=' . $userInfoDb['token'] . '">' . _T('Edit') . '</a>';
        $footerText .= "\n\n" . '/uptodate_' . $listingId . ' :' . _T('Up to date');
        $footerText .= "\n\n" . '/remove_' . $listingId . ' :' . _T('Remove');

        $titleText = $listing['ad']['name'] . "\n\n";
        if (isset($listing['ad']['price']) && $listing['ad']['price'] > 0) {
            $titleText .= $this->numberFormat->priceFormat($listing['ad']['price']) . ' ' . _T('Toman') . "\n\n";
        }
        $listingText = $listing['ad']['description'];
        $messageData['text'] = $titleText . $listingText . $footerText;
        return Request::sendMessage($messageData);
    }

    public function remove(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $status = StatusList::MY_LISTING_REMOVE;
        $this->status->setStatus($userId, $status);
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);

        if (!$userInfoDb['token']) {
            $message->setText(null);
            return $this->auth($message);
        }

        if ($text == _C('YES') || $text == _C('NO')) {
            $this->status->removeStatus($userId, $userInfo, $status);
            if ($text == _C('YES')) {
                $listingId = $userInfo['currentListingId'];
                unset($userInfo['currentListingId']);
                $this->messageHelper->setUserInfo($userId, $userInfo);
                // call delete api
                $delete = $this->api->delete($listingId, $userInfoDb['token']);
                $message->setText(null);
                return $this->myListing($message, ($delete[1] ? _T('Success-Message', [':msg' => $delete[1]]) : _T('Something is wrong! please try again later')));
            } else {
                $message->setText(null);
                return $this->myListing($message);
            }
        }

        $messageData = [];
        $messageData['chat_id'] = $userId;
        $messageData['text'] = _T('Are you sure?');
        $messageData['parse_mode'] = 'Markdown';
        $keyboards = [];
        $keyboards[] = [_C('YES'), _C('NO')];
        $messageData['reply_markup'] = new ReplyKeyboardMarkup(
            [
                'keyboard' => $keyboards,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
                'selective' => true
            ]
        );
        return Request::sendMessage($messageData);
    }

    private function upToDate(InputMessageAbstract $message, $listingId)
    {
        $userId = $message->getUserId();
        $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);

        $result = $this->api->upToDate($listingId, $userInfoDb['token']);
        $message->setText(null);
        return $this->myListing($message, ($result[1] ? _T('Success-Message', [':msg' => $result[1]]) : _T('Something is wrong! please try again later')));
    }

    private function setMyListingData($userId, $searchData)
    {
        $this->session->set(RedisConstant::CURRENT_SEARCH_DATA . $userId, json_encode($searchData));
        return true;
    }

    private function getMyListingData($userId)
    {
        $searchData = $this->session->get(RedisConstant::CURRENT_SEARCH_DATA . $userId);
        $searchData = $searchData ? json_decode($searchData, true) : [];
        return $searchData;
    }

}
