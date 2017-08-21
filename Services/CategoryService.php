<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/3/16
 * Time: 1:39 PM
 */

namespace Trumpet\TelegramBot\Services;

use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Engine\Injectable;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class CategoryService extends Injectable
{
    private $separator;

    public function __construct()
    {
        $this->separator = _T('Category-Separator') . ' ';
    }

    public function loadCategory($categoryId)
    {
        $categories = $this->loadFlatCategories();
        return $categories[$categoryId];
    }

    public function loadFullCategory($categoryId)
    {
        $allCategories = [];
        $categoryIdTmp = $categoryId;
        do {
            $category = $this->loadCategory($categoryIdTmp);
            $allCategories[] = $category['Title'];
        } while ($categoryIdTmp = $category['ParentId']);
        $allCategories = array_reverse($allCategories);
        return implode(' > ', $allCategories);
    }

    public function getCategory(InputMessageAbstract $message, $categoryId = null, $notifyMessage = null, &$selectedCategoryId = null, $statusName = StatusList::POST_LISTING_GET_CATEGORY, $showAllIcon = false)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $categories = $this->loadAllCategories();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'caption' => '',
        ];

        // @TODO: remove logs
//        if ($message->getPhoto()) {
//            $file_id = $message->getPhoto()[count($message->getPhoto()) - 1]->getFileId();
//            $this->messageHelper->log($userId, $file_id);
//        }

        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }

        if (isset($userInfo['currentCategories'][$text])) {
            $categoryId = $userInfo['currentCategories'][$text];
            $message->setText(null);
        } else if ($text) {
            $messageData['caption'] .= _T('Invalid category selection') . "\n\n";
        }
        $userInfo['currentCategories'] = [];
        if ($categoryId) {
            $categories = $this->getSubcategories($categories, $categoryId);
            if (!$categories || ($showAllIcon && ($text == CommandList::ALL_CATEGORIES || $text == _C('ALL_CATEGORIES')))) {
                $message->setText(null);
                $selectedCategoryId = $categoryId;
                $this->messageHelper->setUserInfo($userId, $userInfo);
                return false;
            }
        }
        $messageData = $this->createCategoryKeyboard($categories, $categoryId, $messageData, $showAllIcon, $userInfo);
        $this->messageHelper->setUserInfo($userId, $userInfo);
        $this->status->setStatus($userId, $statusName . ($categoryId ? ':' . $categoryId : ''));
        $messageData = $this->messageHelper->setNavigationButton($messageData, [], 'caption');
        $messageData['photo'] = $this->culture->photoId('select_category');
        return Request::sendPhoto($messageData);
    }

    private function getSubcategories($categories, $categoryId)
    {
        foreach ($categories as $category) {
            if ($category['Id'] == $categoryId) {
                return $category['Children'];
            }
        }
        foreach ($categories as $category) {
            if ($category['Children']) {
                $subCategories = $this->getSubcategories($category['Children'], $categoryId);
                if ($subCategories) {
                    return $subCategories;
                }
            }
        }
        return [];
    }

    public function loadAllCategories()
    {
        if ($this->session->get(RedisConstant::ALL_CATEGORIES)) {
            $categories = json_decode($this->session->get(RedisConstant::ALL_CATEGORIES), true);
        } else {
            $categories = $this->api->getCategories();
            if ($categories) {
                $categoriesJSON = json_encode($categories);
                $this->session->set(RedisConstant::ALL_CATEGORIES, $categoriesJSON);
                $flatCategories = [];
                $this->recursiveFlatCategories($categories, $flatCategories);
                $this->session->set(RedisConstant::FLAT_CATEGORIES, json_encode($flatCategories));
            } else {
                $categories = [];
            }
        }
        return $categories;
    }

    private function createCategoryKeyboard($categories, $categoryId, $messageData, $showAllIcon = false, &$searchData)
    {
        $keyboards = [];
        $keyboards[] = [_C('BACK'), _C('MAIN_MENU')];
        if ($categoryId && $showAllIcon) {
            $keyboards[] = [_C('ALL_CATEGORIES')];
        }

        $messageData['caption'] = isset($messageData['caption']) ? $messageData['caption'] : '';
        if ($categoryId) {
            $currentCategory = $this->loadCategory($categoryId);
            $messageData['caption'] .= _T('Please choose sub category', [':parentCategory' => $currentCategory['Title']]);
        } else {
            $messageData['caption'] .= _T('Please choose category');
        }

        $searchData['currentCategories'] = [];
        $categoryPerRow = 2;
        $countCategories = count($categories);
        for ($i = 0; $i < $countCategories; $i += $categoryPerRow) {
            $keyboard = [];
            for ($j = 0; $j < $categoryPerRow && isset($categories[$i + $j]); $j++) {
                $category = $categories[$i + $j];
                $categoryTitle = $this->separator . $category['Title'];
                $keyboard[] = $categoryTitle;
                $searchData['currentCategories'][$categoryTitle] = $category['Id'];
            }
            $keyboards[] = $keyboard;
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

    private function loadFlatCategories()
    {
        if (!$categoryData = $this->session->get(RedisConstant::FLAT_CATEGORIES)) {
            $this->loadAllCategories();
        }
        return json_decode($categoryData, true);
    }

    private function recursiveFlatCategories($categories, &$flatCategories)
    {
        foreach ($categories as $category) {
            $flatCategories[$category['Id']] = [
                'Id' => $category['Id'],
                'ParentId' => $category['ParentId'],
                'Title' => $category['Title'],
                'HasChildren' => $category['HasChildren']
            ];
            if ($category['Children']) {
                $this->recursiveFlatCategories($category['Children'], $flatCategories);
            }
        }
    }
}
