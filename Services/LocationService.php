<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/3/16
 * Time: 1:39 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\Injectable;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;
use Trumpet\TelegramBot\Services\Structures\LocationMessageStructure;

class LocationService extends Injectable
{
    private $separator;
    private $keyboardLimit = 40;
    public function __construct()
    {
        $this->separator = _T('Location-Separator') . ' ';
    }

    public function loadRegion($regionId)
    {
        $regions = $this->loadAllRegions();
        return $regions[$regionId];
    }

    public function loadCity($regionId, $cityId)
    {
        $cities = $this->loadAllCities($regionId);
        return $cities[$cityId];
    }

    public function getRegion(InputMessageAbstract $message, &$selectedRegionId = null, $statusName = StatusList::POST_LISTING_GET_REGION)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $regions = $this->loadAllRegions();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $redisKey = 'currentRegions';
        if (isset($userInfo[$redisKey][$text])) {
            $selectedRegionId = $userInfo[$redisKey][$text];
            $message->setText(null);
            // set user info
            $userInfo[$redisKey] = [];
            if (!isset($userInfo['location'])) {
                $userInfo['location'] = [];
            }
            $region = $this->loadRegion($selectedRegionId);
            $userInfo['location']['data']['id'] = $selectedRegionId;
            $userInfo['location']['data']['name'] = $region['Title'];
            $this->messageHelper->setUserInfo($userId, $userInfo);
            return false;
        }

        $userInfo[$redisKey] = [];
        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'text' => '',
        ];
        $notifyMessage = _T('Please choose region');
        $messageData = $this->createKeyboard($regions, $redisKey, $messageData, $notifyMessage, $userInfo);
        $this->messageHelper->setUserInfo($userId, $userInfo);
        if ($statusName) {
            $this->status->setStatus($userId, $statusName);
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getCity(InputMessageAbstract $message, $page, $regionId, $notifyMessage, &$selectedCityId = null, $redisKey = 'currentCities', $statusName = StatusList::POST_LISTING_GET_CITY)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $cities = $this->loadAllCities($regionId);
        $userInfo = $this->messageHelper->getUserInfo($userId);
        if (isset($userInfo[$redisKey][$text])) {
            $selectedCityId = $userInfo[$redisKey][$text];
            $message->setText(null);
            // set user info
            $city = $this->loadCity($regionId, $selectedCityId);
            $userInfo[$redisKey] = [];
            $userInfo['location']['data']['cityId'] = $selectedCityId;
            $userInfo['location']['data']['cityName'] = $city['Title'];
            $this->messageHelper->setUserInfo($userId, $userInfo);
            return false;
        }

        $userInfo[$redisKey] = [];
        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'text' => '',
        ];
        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }
        $notifyMessage = _T('Please choose city');
        $messageData = $this->createKeyboard($cities, $redisKey, $messageData, $notifyMessage, $userInfo, $page);
        $this->messageHelper->setUserInfo($userId, $userInfo);
        $this->status->setStatus($userId, $statusName);
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getNeighbourhood(InputMessageAbstract $message, $page, $cityId, $notifyMessage, &$selectedNeighbourhoodId = null, &$selectedNeighbourhoodName = null, $redisKey = 'currentNeighbourhoods', $statusName = StatusList::POST_LISTING_GET_NEIGHBOURHOOD)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $neighbourhoods = $this->loadAllNeighbourhoods($cityId);
        $userInfo = $this->messageHelper->getUserInfo($userId);

        if ($neighbourhoods && $text != 'page' && isset($userInfo[$redisKey][$text])) {
            $selectedNeighbourhoodId = $userInfo[$redisKey][$text];
            $selectedNeighbourhoodName = str_replace($this->separator, '', $text);;
        } else if ($text) {
            $selectedNeighbourhoodName = str_replace($this->separator, '', $text);
        }
        $userInfo[$redisKey] = [];
        if ($selectedNeighbourhoodName) {
            $message->setText(null);
            // set user info
            $userInfo['location']['data']['neighbourhoodId'] = $selectedNeighbourhoodId;
            $userInfo['location']['data']['neighbourhoodName'] = $selectedNeighbourhoodName;
            $this->messageHelper->setUserInfo($userId, $userInfo);
            return false;
        }
        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
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

        if ($neighbourhoods) {
            $notifyMessage = _T('Please enter neighbourhood');
            $messageData = $this->createKeyboard($neighbourhoods, $redisKey, $messageData, $notifyMessage, $userInfo, $page);
            $this->messageHelper->setUserInfo($userId, $userInfo);
        } else {
            $messageData['text'] .= _T('Please enter neighbourhood');
        }

        $this->status->setStatus($userId, $statusName);
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function setLocation(LocationMessageStructure $message)
    {
        $userId = $message->getUserId();
        $userInfo = $this->messageHelper->getUserInfo($userId);

        $latitude = $message->getLocation()->getLatitude();
        $longitude = $message->getLocation()->getLongitude();
        $userInfo['location'] = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'data' => $this->api->getGeoLocation($latitude, $longitude)
        ];

        $this->messageHelper->setUserInfo($userId, $userInfo);
        return $userInfo;
    }

    private function loadAllRegions()
    {
        if ($this->session->get(RedisConstant::ALL_REGIONS)) {
            $newRegions = json_decode($this->session->get(RedisConstant::ALL_REGIONS), true);
        } else {
            $regions = $this->api->getRegions();
            $newRegions = [];
            foreach ($regions as $region) {
                $newRegions[$region['Id']] = $region;
            }
            if ($newRegions) {
                $this->session->set(RedisConstant::ALL_REGIONS, json_encode($newRegions));
            }
        }
        return $newRegions;
    }

    private function loadAllCities($regionId)
    {
        if ($this->session->get(RedisConstant::ALL_CITIES . $regionId)) {
            $newCities = json_decode($this->session->get(RedisConstant::ALL_CITIES . $regionId), true);
        } else {
            $cities = $this->api->getCities($regionId);
            $newCities = [];
            foreach ($cities as $city) {
                $newCities[$city['Id']] = $city;
            }
            if ($newCities) {
                $this->session->set(RedisConstant::ALL_CITIES . $regionId, json_encode($newCities));
            }
        }
        return $newCities;
    }

    private function loadAllNeighbourhoods($cityId)
    {
        if ($this->session->get(RedisConstant::ALL_NEIGHBOURHOODS . $cityId)) {
            $newData = json_decode($this->session->get(RedisConstant::ALL_NEIGHBOURHOODS . $cityId), true);
        } else {
            $neighbourhoods = $this->api->getNeighbourhoods($cityId);
            $newData = [];
            foreach ($neighbourhoods as $neighbourhood) {
                $newData[$neighbourhood['Id']] = $neighbourhood;
            }
            if ($newData) {
                $this->session->set(RedisConstant::ALL_NEIGHBOURHOODS . $cityId, json_encode($newData));
            }
        }
        return $newData;
    }

    private function createKeyboard($items, $redisKey, $messageData, $notifyMessage, &$searchData, $page = 1)
    {
        $keyboards = [];
        $keyboards[] = [_C('BACK'), _C('MAIN_MENU')];
        $searchData[$redisKey] = [];
        $messageData['text'] = isset($messageData['text']) ? $messageData['text'] : '';
        $messageData['text'] .= $notifyMessage;
        $itemPerRow = 2;
        $newItems = array_values($items);
        $countCities = count($items);
        $start = ($page - 1) * $this->keyboardLimit;
        for ($i = $start; $i < $start + $this->keyboardLimit; $i += $itemPerRow) {
            $keyboard = [];
            for ($j = 0; $j < $itemPerRow && isset($newItems[$i + $j]); $j++) {
                $item = $newItems[$i + $j];
                $title = $this->separator . $item['Title'];
                $keyboard[] = $title;
                $searchData[$redisKey][$title] = $item['Id'];
            }
            $keyboards[] = $keyboard;
        }
        $searchData[$redisKey]['page'] = $page;
        $navigatorKeyboard = [];
        if ($countCities > $start + $this->keyboardLimit) {
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
}
