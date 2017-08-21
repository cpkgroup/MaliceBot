<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/25/16
 * Time: 4:34 PM
 */

namespace Trumpet\TelegramBot\Commands\Sections;

use Longman\TelegramBot\Commands\SystemCommands\StartCommand;
use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Services\ApiService;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class ShowListingSection extends SectionAbstract
{
    /**
     * @param InputMessageAbstract $message
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function start(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $this->status->setStatus($userId, StatusList::SHOW_LISTING_START);

        switch ($message->getText()) {
            case CommandList::SHOW_LISTING:
            case _C('SHOW_LISTING'):
                $text = null;
                break;
            case CommandList::SHOW_LISTING_BY_CATEGORY:
            case _C('SHOW_LISTING_BY_CATEGORY'):
                $message->setText(null);
                return $this->getCategory($message);
            case CommandList::SEND_LOCATION:
            case _C('SEND_LOCATION'):
                $message->setText(null);
                return $this->getLocation($message);
            case CommandList::SHOW_LISTING_SEARCH:
            case _C('SHOW_LISTING_SEARCH'):
                $message->setText(null);
                return $this->getKeyword($message);
        }
        $this->setShowListingData($userId, []);

        $data = [];
        $data['chat_id'] = $message->getUserId();
        $data['parse_mode'] = 'Markdown';
        $data['text'] = _T('Please choose one');
        $buttons[] = [
            'command' => CommandList::SHOW_LISTING_BY_CATEGORY,
            'text' => _C('SHOW_LISTING_BY_CATEGORY')
        ];
        $buttons[] = [
            'command' => CommandList::SHOW_LISTING_SEARCH,
            'text' => _C('SHOW_LISTING_SEARCH')
        ];

        $buttons[] = [];
        $userInfo = $this->messageHelper->getUserInfo($message->getUserId());
        if (!isset($userInfo['location']['data']['id'])) {
            $buttons[] = [
                'command' => CommandList::SEND_LOCATION,
                'text' => _C('SEND_LOCATION')
            ];
        } else {
            $data['text'] = _T('Please choose one by region', [':regionName' => $userInfo['location']['data']['name']]);
            $buttons[] = [
                'command' => CommandList::SEND_LOCATION,
                'text' => _T('Change-Region-From', [
                    ':region' => $userInfo['location']['data']['name']
                ])
            ];
        }
        $buttons[] = [
            'command' => CommandList::MAIN_MENU,
            'text' => _C('MAIN_MENU')
        ];
        $data = $this->messageHelper->setNavigationButton($data, $buttons);

        $keyboard = [];
        $keyboard[] = [_C('SHOW_LISTING_SEARCH'), _C('SHOW_LISTING_BY_CATEGORY')];
        $keyboard[] = [
            [
                'text' => _C('SEND_LOCATION')
            ]
        ];
        $keyboard[] = [_C('MAIN_MENU')];

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

    public function getKeyword(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $searchData = $this->getShowListingData($userId);
        $this->status->setStatus($userId, StatusList::SHOW_LISTING_GET_KEYWORD);
        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'reply_markup' => new ReplyKeyboardHide(
                [
                    'remove_keyboard' => true
                ]
            )
        ];
        $text = $this->messageHelper->filterText($text);
        if (!$text) {
            $messageData['text'] = _T('Please enter keyword for search');
            $messageData = $this->messageHelper->setNavigationButton($messageData);
            return Request::sendMessage($messageData);
        } else {
            $searchData['keyword'] = $text;
            $searchData['categoryText'] = null;
            $searchData['categoryId'] = null;
            $this->setShowListingData($userId, $searchData);
            return $this->search($message);
        }
    }

    public function getCategory(InputMessageAbstract $message, $categoryId = null)
    {
        $selectedCategoryId = null;
        $out = $this->category->getCategory($message, $categoryId, null, $selectedCategoryId, StatusList::SHOW_LISTING_GET_CATEGORY, true);
        if ($selectedCategoryId) {
            return $this->setCategory($message, $selectedCategoryId);
        }
        return $out;
    }

    public function setCategory(InputMessageAbstract $message, $categoryId = null)
    {
        $userId = $message->getUserId();
        $searchData = $this->getShowListingData($userId);
        if ($categoryId) {
            $category = $this->category->loadCategory($categoryId);
            $searchData['keyword'] = null;
            $searchData['categoryText'] = $category['Title'];
            $searchData['categoryId'] = $categoryId;
            $this->setShowListingData($userId, $searchData);
            $message->setText(null);
            return $this->search($message);
        }
        return $this->getCategory($message);
    }

    public function search(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $this->status->setStatus($userId, StatusList::SHOW_LISTING_SEARCH);
        $searchData = $this->getShowListingData($userId);

        $categoryId = isset($searchData['categoryId']) ? $searchData['categoryId'] : null;
        $categoryText = isset($searchData['categoryText']) ? $searchData['categoryText'] : null;
        $keyword = isset($searchData['keyword']) ? $searchData['keyword'] : null;
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
        if ($number) {
            $page = isset($searchData['page']) ? $searchData['page'] + $number : 1;
        }

        if (isset($searchData['details'][$text])) {
            return $this->detail($message, $searchData['details'][$text]);
        }

        if ($text !== null) {
            $searchData['keyword'] = $keyword = $text;
            $this->setShowListingData($userId, $searchData);
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
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $regionId = isset($userInfo['location']['data']['id']) ? $userInfo['location']['data']['id'] : null;

        $searchQuery = [];
        if ($keyword) {
            $searchQuery['q'] = $keyword;
            $messageData['text'] .= _T('Search result for word', [':word' => $keyword]);
        } else {
            $messageData['text'] = _T('Search result for') . ' ' . "\n";
        }

        if ($regionId) {
            $searchQuery['regionId'] = $regionId;
            if ($keyword) {
                $messageData['text'] .= ' ' . _T('In region', [':region' => $userInfo['location']['data']['name']]);
            } else {
                $messageData['text'] .= _T('Region') . ' *' . $userInfo['location']['data']['name'] . '*' . "\n";
            }
        }
        if ($categoryId) {
            $searchQuery['categoryId'] = $categoryId;
            if ($keyword) {
                $messageData['text'] .= ' ' . _T('In category', [':category' => $categoryText]);
            } else {
                $messageData['text'] .= _T('Category') . ' *' . $categoryText . '*' . "\n";
            }
        }

        $listings = $this->api->searchListing($searchQuery, $this->limit, $skip);
        if (count($listings['items']) <= 0) {
            if ($keyword) {
                $messageData['text'] .= "\n";
            }
            $messageData['text'] .= "\n" . _T('Result not found, please enter keyword again');
            $buttons = [];
            $buttons[] = [
                'command' => CommandList::SEND_LOCATION,
                'text' => _T('Change-Region-From', [
                    ':region' => $userInfo['location']['data']['name']
                ])
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
            $allPages = ceil($listings['totalCount'] / $this->limit);
            if ($keyword) {
                $messageData['text'] .= "\n\n" . _T('Search-Result-Message', [
                        ':totalCount' => $this->numberFormat->persianNumber($listings['totalCount']),
                        ':page' => $this->numberFormat->persianNumber($page),
                        ':allPages' => $this->numberFormat->persianNumber($allPages)
                    ]) . "\n";
            } else {
                $messageData['text'] .= "\n" . _T('For search here please enter keyword');
            }
            $messageData['text'] .= "\n" . _T('For show details of listing please use below buttons');
            $messageData = $this->messageHelper->createSearchKeyboard($listings['items'], $page, $messageData, $searchData, $allPages);
            $this->setShowListingData($userId, $searchData);
        }
        return Request::sendMessage($messageData);
    }

    public function detail(InputMessageAbstract $message, $listingId = null)
    {
        if (!$listingId) {
            return $this->start($message);
        }
        $userId = $message->getUserId();
        $listing = $this->api->listingDetails($listingId);
        $messageData = [];
        $messageData['chat_id'] = $userId;
        $messageData['parse_mode'] = 'Markdown';
        $messageData['disable_web_page_preview'] = true;

        if (!$listing) {
            $messageData['text'] = _T('Listing-Details-Not-Found', [
                ':listingId' => $listingId
            ]);
            return Request::sendMessage($messageData);
        } else {
            $imgUrl = $listing['ad']['images'][0]['UrlBig'];
            $imgUrl = (strstr($imgUrl, 'http://') || strstr($imgUrl, 'https://')) ? $imgUrl : ApiService::SHEYPOOR_URL . $imgUrl;
            $photo = $this->api->getImage($imgUrl);

            $filename = $message->getTelegram()->getUploadPath() . '/' . $listingId . '.' . pathinfo($imgUrl, PATHINFO_EXTENSION);
            file_put_contents($filename, $photo);
        }
        $footerText = "\n\n" . _T('Show details and contact');
        $footerText .= "\n" . $this->messageHelper->listingUrl($listingId);
        $footerText .= "\n\n" . _T('Bot-Icon') . ' @' . $message->getTelegram()->getBotName();
        $footerText .= "\n" . _T('Channel-Icon') . ' @' . _T('Sheypoor-Channel');
        $titleText = $listing['ad']['name'] . "\n\n";
        if (isset($listing['ad']['price']) && $listing['ad']['price'] > 0) {
            $titleText .= $this->numberFormat->priceFormat($listing['ad']['price']) . ' ' . _T('Toman') . "\n\n";
        }
        $listingText = $this->messageHelper->summarize($listing['ad']['description'], 200 - mb_strlen($titleText) - mb_strlen($footerText) - 4);
        $messageData['caption'] = $titleText . $listingText . $footerText;
        $result = Request::sendPhoto($messageData, realpath($filename));
        unlink($filename);
        return $result;
    }


    public function getLocation(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        switch ($message->getText()) {
            case CommandList::SEND_LOCATION:
            case _C('SEND_LOCATION'):
                $text = null;
                break;
            case CommandList::SEND_REGION:
            case _C('SEND_REGION'):
                return $this->getRegion($message);
        }

        $this->status->setStatus($userId, StatusList::SHOW_LISTING_GET_LOCATION);
        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'text' => ''
        ];

        $messageData['text'] .= _T('Please send region and city by buttons');

        if (!$message->getLocation()) {
            $keyboard = [];
            $keyboard[] = [
                [
                    'text' => _C('SEND_LOCATION'),
                    'request_location' => true
                ]
            ];
            $keyboard[] = [
                [
                    'text' => _C('SEND_REGION')
                ]
            ];
            $messageData['reply_markup'] = new ReplyKeyboardMarkup(
                [
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false,
                    'selective' => true
                ]
            );
        } else {
            return $this->start($message);
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getRegion(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $selectedRegionId = null;
        $this->location->getRegion($message, $selectedRegionId, StatusList::SHOW_LISTING_GET_REGION);
        if ($selectedRegionId) {
            $region = $this->location->loadRegion($selectedRegionId);
            $notifyMessage = _T('Region submitted successfully', [':region' => $region['Title']]);
            $messageData = [
                'chat_id' => $userId,
                'parse_mode' => 'Markdown',
                'text' => $notifyMessage,
                'reply_markup' => new ReplyKeyboardHide(
                    [
                        'remove_keyboard' => true
                    ]
                )
            ];
            Request::sendMessage($messageData);
            $message->setText(null);
            return $this->start($message);
        }
    }

    private function getShowListingData($userId)
    {
        $searchData = $this->session->get(RedisConstant::CURRENT_SEARCH_DATA . $userId);
        $searchData = $searchData ? json_decode($searchData, true) : [];
        return $searchData;
    }

    private function setShowListingData($userId, $searchData)
    {
        $this->session->set(RedisConstant::CURRENT_SEARCH_DATA . $userId, json_encode($searchData));
        return true;
    }

}
