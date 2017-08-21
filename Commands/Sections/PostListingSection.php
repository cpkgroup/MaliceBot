<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/25/16
 * Time: 4:34 PM
 */

namespace Trumpet\TelegramBot\Commands\Sections;

use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboardMarkup;
use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Commands\Constants\CommandList;
use Trumpet\TelegramBot\Commands\Constants\StatusList;
use Trumpet\TelegramBot\Services\ApiService;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

class PostListingSection extends SectionAbstract
{
    private $validImageTypes = ['image/png', 'image/jpeg'];

    /**
     * @param InputMessageAbstract $message
     * @return mixed
     */
    public function start(InputMessageAbstract $message)
    {
        return $this->getContactInfo($message);
    }

    public function getContactInfo(InputMessageAbstract $message)
    {
        $userId = $message->getUserId();
        $status = StatusList::POST_LISTING_GET_CONTACT;
        $this->status->setStatus($userId, $status);
        $userInfo = $this->messageHelper->getUserInfo($userId);
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
        } else {
            $this->status->removeStatus($userId, $userInfo, $status);
            $message->setText(null);
            return $this->auth($message);
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
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
        $status = StatusList::POST_LISTING_AUTH;
        $auth = $this->auth->auth($message, $status, $loggedInStatus, $notifyMessage);
        if ($loggedInStatus) {
            $this->status->removeStatus($userId, $userInfo, $status);
            $message->setText(null);
            return $this->getName($message, $notifyMessage);
        } else {
            return $auth;
        }
    }

    public function getName(InputMessageAbstract $message, $notifyMessage = null)
    {
        $text = $message->getText();
        $userId = $message->getUserId();
        $userInfo = $this->messageHelper->getUserInfo($userId);

        // check last step
        if (!isset($userInfo['contact']['mobile'])) {
            return $this->getContactInfo($message);
        }

        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }

        switch ($message->getText()) {
            case _C('POST_LISTING'):
            case CommandList::POST_LISTING:
                $text = null;
                break;
        }
        $this->status->setStatus($userId, StatusList::POST_LISTING_GET_NAME);
        $listingData = [];
        $this->setPostListingData($userId, $listingData);
        $messageData = [
            'chat_id' => $userId,
            'text' => '',
            'parse_mode' => 'Markdown',
            'reply_markup' => new ReplyKeyboardHide(
                [
                    'remove_keyboard' => true
                ]
            )
        ];
        if ($text) {
            $text = $this->messageHelper->filterText($text);
            $countLength = mb_strlen($text);
            if ($countLength < 10 || $countLength > 60) {
                $messageData['text'] = _T('Name of listing is invalid', [
                    ':min' => $this->numberFormat->persianNumber(10),
                    ':max' => $this->numberFormat->persianNumber(60)
                ]);
            } else {
                $listingData['name'] = $text;
                $this->setPostListingData($userId, $listingData);
                $notifyMessage = _T('Name of listing submitted successfully', [':name' => $listingData['name']]);
                $message->setText(null);
                return $this->getDescription($message, $notifyMessage);
            }
        } else {
            // check user token is valid
            $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);
            $ticket = $userInfoDb['token'];
            $isLoggedIn = $this->api->isLoggedIn($ticket);

            // token is expired!
            if (!$isLoggedIn) {
                $this->messageHelper->resetDBToken($userId);
                return $this->auth($message);
            }
            $messageData['text'] = _T('Please enter listing name');
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getDescription(InputMessageAbstract $message, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $this->status->setStatus($userId, StatusList::POST_LISTING_GET_DESCRIPTION);
        $listingData = $this->getPostListingData($userId);

        // check last step
        if (!isset($listingData['name']) || !$listingData['name']) {
            return $this->getName($message);
        }

        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'text' => _T('Please enter listing description'),
            'reply_markup' => new ReplyKeyboardHide(
                [
                    'remove_keyboard' => true
                ]
            )
        ];

        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }

        if ($text) {
            $text = $this->messageHelper->filterText($text);
            $countLength = mb_strlen($text);
            if ($countLength < 20 || $countLength > 4000) {
                $messageData['text'] = _T('Description of listing is invalid', [
                    ':min' => $this->numberFormat->persianNumber(20),
                    ':max' => $this->numberFormat->persianNumber(4000)
                ]);
            } else {
                $listingData['description'] = $text;
                $this->setPostListingData($userId, $listingData);
                $notifyMessage = _T('Description of listing submitted successfully', [':description' => $listingData['description']]);
                $message->setText(null);
                return $this->getCategory($message, null, $notifyMessage);
            }
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getCategory(InputMessageAbstract $message, $categoryId = null, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $listingData = $this->getPostListingData($userId);

        // check last step
        if (!isset($listingData['description']) || !$listingData['description']) {
            return $this->getDescription($message);
        }

        $selectedCategoryId = null;
        $this->category->getCategory($message, $categoryId, $notifyMessage, $selectedCategoryId, StatusList::POST_LISTING_GET_CATEGORY);
        if ($selectedCategoryId) {
            $categoryName = $this->category->loadFullCategory($selectedCategoryId);
            $notifyMessage = _T('Category of listing submitted successfully', [':category' => $categoryName]);
            $listingData['categoryId'] = $selectedCategoryId;
            $this->setPostListingData($userId, $listingData);
            $message->setText(null);
            return $this->getPrice($message, $notifyMessage);
        }
    }


    public function getPrice(InputMessageAbstract $message, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $listingData = $this->getPostListingData($userId);

        // check last step
        if (!isset($listingData['categoryId']) || !$listingData['categoryId']) {
            return $this->getCategory($message);
        }

        // first step
        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
            $listingData['optimizedAttributes'] = [];
            $listingData['attributes'] = $this->api->getAttributes($listingData['categoryId']);
        } else {
            $listingData['attributes'] = $listingData['attributes'] ? $listingData['attributes'] : $this->api->getAttributes($listingData['categoryId']);
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

        if ($listingData['attributes']['hasPrice']) {
            $messageData['text'] .= _T('Please enter price, for negotiable price enter 1', [
                ':priceLabel' => $listingData['attributes']['priceLabel']
            ]);

            if ($text !== null) {
                $text = $this->messageHelper->filterText($text);
                $price = $this->numberFormat->englishNumber($text);

                if ($price > 99000000000 || preg_match('/[^0-9]/', $price)) {
                    $messageData['text'] = _T('Price is invalid, Please enter price again');
                } else {
                    if ($price < 100) {
                        $price = -1;
                    }
                    $listingData['price'] = $price;
                    $notifyMessage = _T('Price submitted successfully', [
                        ':priceLabel' => $listingData['attributes']['priceLabel'],
                        ':price' => $price > 0 ? $this->numberFormat->priceFormat($price) : _T('Negotiable')
                    ]);
                    $this->setPostListingData($userId, $listingData);
                    $message->setText(null);
                    return $this->getAttr($message, null, $notifyMessage);
                }
            }
        } else {
            $message->setText(null);
            return $this->getAttr($message, null, $notifyMessage);
        }
        $this->status->setStatus($userId, StatusList::POST_LISTING_GET_PRICE);
        $this->setPostListingData($userId, $listingData);
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getAttr(InputMessageAbstract $message, $attrId = null, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $text = $message->getText();
        $listingData = $this->getPostListingData($userId);

        // check last step
        if (!isset($listingData['categoryId']) || !$listingData['categoryId']) {
            return $this->getCategory($message);
        }

        // first step
        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
            $listingData['optimizedAttributes'] = [];
            $listingData['attributes'] = $this->api->getAttributes($listingData['categoryId']);
            $listingData['attributes']['currentAttr'] = null;
        }

        if (!isset($listingData['attributes']['attributes'][0])) {
            return $this->getPhotos($message, $notifyMessage);
        }

        $flatAttributes = [];
        foreach ($listingData['attributes']['attributes'] as $key => $attribute) {
            $attribute['attribute_key'] = $key;
            $flatAttributes[$attribute['attribute_id']] = $attribute;
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

        $listingData['attributes']['currentAttr'] = $attrId;
        $attrId = isset($listingData['attributes']['currentAttr']) && $listingData['attributes']['currentAttr'] ? $listingData['attributes']['currentAttr'] : $attrId;
        $currentAttribute = null;
        $nextAttribute = null;
        if (!$attrId && !isset($listingData['attributes']['currentAttr'])) {
            $currentAttribute = $listingData['attributes']['attributes'][0];
            $nextAttribute = isset($listingData['attributes']['attributes'][1]) ? $listingData['attributes']['attributes'][1] : null;
            $attrId = $currentAttribute['attribute_id'];
        } else if ($attrId) {
            $currentAttribute = $flatAttributes[$attrId];
            $nextAttribute = isset($listingData['attributes']['attributes'][$currentAttribute['attribute_key'] + 1]) ? $listingData['attributes']['attributes'][$currentAttribute['attribute_key'] + 1] : null;
        }

        if ($currentAttribute) {
            $labelText = $text;
            // get attribute
            if ($text !== null) {
                $text = $this->messageHelper->filterText($text);
                $validAttr = false;
                if ($currentAttribute['type'] == 'number' || $currentAttribute['custom_validate_regex']) {
                    $text = $this->numberFormat->englishNumber($text);
                    $labelText = $text;
                    if ($currentAttribute['type'] == 'number' && !$currentAttribute['custom_validate_regex']) {
                        $labelText = $this->numberFormat->priceFormat($text);
                    }
                    if ($currentAttribute['type'] == 'number' && preg_match('/[^0-9]/', $text)) {
                        $messageData['text'] = _T('Please enter numeric value', [
                                ':label' => $currentAttribute['label']
                            ]) . "\n\n";
                    } else if ($currentAttribute['custom_validate_regex'] && !preg_match('/' . $currentAttribute['custom_validate_regex'] . '/', $text)) {
                        $messageData['text'] = $currentAttribute['custom_validate_regex_message'] . "\n\n";
                    } else if ($text < 1 && $currentAttribute['non_zero'] == true) {
                        $messageData['text'] = _T('Please enter numeric value greater than zero', [
                                ':label' => $currentAttribute['label']
                            ]) . "\n\n";
                    } else {
                        $validAttr = true;
                    }
                    $listingData['optimizedAttributes'][$currentAttribute['attribute_id']] = [
                        'attributeID' => $currentAttribute['attribute_id'],
                        'attributeValue' => $text
                    ];
                } else if ($currentAttribute['options']) {
                    foreach ($currentAttribute['options'] as $option) {
                        if ($option['label'] == $text) {
                            $listingData['optimizedAttributes'][$currentAttribute['attribute_id']] = [
                                'attributeID' => $currentAttribute['attribute_id'],
                                'attributeValue' => $option['option_id']
                            ];
                            $validAttr = true;
                        }
                    }
                    if (!$validAttr) {
                        $messageData['text'] = _T('Invalid value, please do not use keyboard and only use buttons', [
                                ':label' => $currentAttribute['label']
                            ]) . "\n\n";
                    }
                } else {
                    $listingData['optimizedAttributes'][$currentAttribute['attribute_id']] = [
                        'attributeID' => $currentAttribute['attribute_id'],
                        'attributeValue' => $text
                    ];
                }

                if ($validAttr) {
                    $messageData['text'] = _T('Value successfully submit', [
                        ':label' => $currentAttribute['label'],
                        ':value' => $labelText
                    ]);
                    $this->messageHelper->sendMessage($userId, $messageData['text']);
                    $messageData['text'] = '';
                    if ($nextAttribute) {
                        $listingData['attributes']['currentAttr'] = $nextAttribute['attribute_id'];
                        $currentAttribute = $nextAttribute;
                        $attrId = $nextAttribute['attribute_id'];
                    } else {
                        $listingData['attributes']['currentAttr'] = false;
                        $attrId = null;
                    }
                }
            }

            if (!$attrId) {
                $this->setPostListingData($userId, $listingData);
                return $this->getPhotos($message, $messageData['text']);
            }

            $inputText = $currentAttribute['options'] ? _T('Choose') : _T('Enter');
            $messageData['text'] .= _T('Please-Submit-Value-For-Attribute', [
                ':submit' => $inputText,
                ':label' => $currentAttribute['label'],
            ]);
            if ($currentAttribute['options']) {
                $keyboard = [];
                $keyboard[] = [_C('BACK'), _C('MAIN_MENU')];
                foreach ($currentAttribute['options'] as $option) {
                    $keyboard[] = [$option['label']];
                }
                $messageData['reply_markup'] = new ReplyKeyboardMarkup(
                    [
                        'keyboard' => $keyboard,
                        'resize_keyboard' => true,
                        'one_time_keyboard' => false,
                        'selective' => true
                    ]
                );
            }
        }

        $this->setPostListingData($userId, $listingData);
        if ($attrId) {
            $this->status->setStatus($userId, StatusList::POST_LISTING_GET_ATTR . ':' . $attrId);
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getPhotos(InputMessageAbstract $message, $notifyMessage = null)
    {
        $config = $this->getConfig();
        $userId = $message->getUserId();
        $text = $message->getText();
        $this->status->setStatus($userId, StatusList::POST_LISTING_GET_PHOTOS);
        $listingData = $this->getPostListingData($userId);

        // check last step
        if (!isset($listingData['categoryId']) || !$listingData['categoryId']) {
            return $this->getCategory($message);
        }

        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }

        $listingData['images'] = isset($listingData['images']) ? $listingData['images'] : [];
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

        if ($text == CommandList::NEXT_STEP || $text == _C('NEXT_STEP')) {
            $notifyMessage = null;
            if (count($listingData['images']) > 0) {
                $notifyMessage = _T('Picture(s) uploaded successfully', [
                    ':count' => count($listingData['images']),
                ]);
            }
            $message->setText(null);
            return $this->getLocation($message, $notifyMessage);
        }

        if ($message->getPhoto()) {
            /** @var \Longman\TelegramBot\Entities\PhotoSize $file_id */
            $file_id = $message->getPhoto()[count($message->getPhoto()) - 1]->getFileId();

            // TODO validation photo
            // $file_id->getWidth();
            // $file_id->getHeight();
            $ServerResponse = Request::getFile(['file_id' => $file_id]);

            /** @var \Longman\TelegramBot\Entities\File $file */
            $file = $ServerResponse->getResult();
            $filePath = $file->getFilePath();

            if ($ServerResponse->isOk()) {
                Request::downloadFile($ServerResponse->getResult());
                $result = $this->api->uploadImage($config['telegram']['downloadPath'] . '/' . $filePath);
                if ($result) {
                    unlink(realpath($config['telegram']['downloadPath'] . '/' . $filePath));
                    $listingData['images'][] = [
                        'imageKey' => $result['imageKey'],
                        'isPrimary' => count($listingData['images']) == 0
                    ];
                    $messageData['text'] .= _T('Picture uploaded successfully') . "\n\n";
                    $messageData['text'] .= _T('Please upload next picture') . "\n";
                    $messageData['text'] .= _T('Or click skip');
                } else {
                    $messageData['text'] .= _T('Error while uploading image, please try again') . "\n\n";
                }
            }
        } else if ($message->getDocument()) {
            $file_id = $message->getDocument()->getFileId();
            $mimeType = $message->getDocument()->getMimeType();

            if (in_array($mimeType, $this->validImageTypes)) {
                $ServerResponse = Request::getFile(['file_id' => $file_id]);

                /** @var \Longman\TelegramBot\Entities\File $file */
                $file = $ServerResponse->getResult();
                $filePath = $file->getFilePath();
                if ($ServerResponse->isOk()) {
                    Request::downloadFile($ServerResponse->getResult());
                    $result = $this->api->uploadImage($config['telegram']['downloadPath'] . '/' . $filePath);
                    if ($result) {
                        unlink(realpath($config['telegram']['downloadPath'] . '/' . $filePath));
                        $listingData['images'][] = [
                            'imageKey' => $result['imageKey'],
                            'isPrimary' => count($listingData['images']) == 0
                        ];
                        $messageData['text'] .= _T('Picture uploaded successfully') . "\n\n";
                        $messageData['text'] .= _T('Please upload next picture') . "\n";
                        $messageData['text'] .= _T('Or click skip');
                    } else {
                        $messageData['text'] .= _T('Error while uploading image, please try again') . "\n\n";
                    }
                }
            } else {
                $messageData['text'] .= _T('Invalid image type') . "\n\n";
                $messageData['text'] .= _T('Please upload another picture');
            }
        } else {
            if (count($listingData['images']) > 0) {
                $messageData['text'] .= _T('Please upload next picture');
            } else {
                $messageData['text'] .= _T('Please upload picture');
            }
            $messageData['text'] .= "\n" . _T('Or click skip');
        }
        $buttons = [];
        $buttons[] = [
            'command' => CommandList::NEXT_STEP,
            'text' => _C('NEXT_STEP')
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
        $this->setPostListingData($userId, $listingData);
        $messageData = $this->messageHelper->setNavigationButton($messageData, $buttons);
        return Request::sendMessage($messageData);
    }

    public function getLocation(InputMessageAbstract $message, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $listingData = $this->getPostListingData($userId);
        $userInfo = $this->messageHelper->getUserInfo($userId);

        $lastLocation = null;
        if (
            (
                isset($userInfo['location']['data']['neighbourhoodName']) &&
                $userInfo['location']['data']['neighbourhoodName']
            )
            ||
            (
                isset($userInfo['location']['latitude']) &&
                $userInfo['location']['latitude']
            )
        ) {
            $lastLocation = $userInfo['location']['data']['name'];
            if (isset($userInfo['location']['data']['cityName'])) {
                $lastLocation .= _T('Comma') . ' ' . $userInfo['location']['data']['cityName'];
            }
        }
        // check last step
        if (!isset($listingData['categoryId']) || !$listingData['categoryId']) {
            return $this->getCategory($message);
        }

        switch ($message->getText()) {
            case CommandList::LAST_LOCATION:
            case _T('Use-Old-Location', [':location' => $lastLocation]):
                $text = null;
                if ($lastLocation) {
                    return $this->finalStep($message, _T('Old location submitted successfully'));
                }
                break;
            case CommandList::SEND_LOCATION:
            case _C('SEND_LOCATION'):
                $text = null;
                break;
            case CommandList::SEND_REGION:
            case _C('SEND_REGION'):
                return $this->getRegion($message);
        }

        if ($notifyMessage) {
            $this->messageHelper->sendMessage($userId, $notifyMessage);
        }
        $this->status->setStatus($userId, StatusList::POST_LISTING_GET_LOCATION);
        $messageData = [
            'chat_id' => $userId,
            'parse_mode' => 'Markdown',
            'text' => ''
        ];

        $messageData['text'] .= _T('Please send region and city by buttons');

        if (!$message->getLocation()) {
            $keyboard = [];

            if ($lastLocation) {
                $keyboard[] = [
                    [
                        'text' => _T('Use-Old-Location', [':location' => $lastLocation])
                    ]
                ];
            }
            $keyboard[] = [
                [
                    'text' => _C('SEND_LOCATION'),
                    'request_location' => true
                ]
            ];
            $keyboard[] = [
                [
                    'text' => _C('SEND_REGION'),
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
            return $this->finalStep($message);
        }
        $messageData = $this->messageHelper->setNavigationButton($messageData);
        return Request::sendMessage($messageData);
    }

    public function getRegion(InputMessageAbstract $message)
    {
        $selectedRegionId = null;
        $this->location->getRegion($message, $selectedRegionId, StatusList::POST_LISTING_GET_REGION);
        if ($selectedRegionId) {
            $region = $this->location->loadRegion($selectedRegionId);
            $notifyMessage = _T('Region submitted successfully', [':region' => $region['Title']]);
            $message->setText(null);
            return $this->getCity($message, $notifyMessage);
        }
    }

    public function getCity(InputMessageAbstract $message, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $text = $message->getText();
        $redisKey = 'currentCities';

        $number = 0;
        $page = 1;
        switch ($text) {
            case CommandList::NEXT:
            case _C('NEXT'):
                $number = 1;
                break;
            case CommandList::PREVIOUS:
            case _C('PREVIOUS'):
                $number = -1;
                break;
        }
        if ($number) {
            $page = isset($userInfo[$redisKey]['page']) ? $userInfo[$redisKey]['page'] + $number : 1;
            $message->setText(null);
        }

        // check last step
        if (!isset($userInfo['location']['data']['id']) || !$userInfo['location']['data']['id']) {
            return $this->getRegion($message);
        }
        $regionId = $userInfo['location']['data']['id'];

        $selectedCityId = null;
        $this->location->getCity($message, $page, $regionId, $notifyMessage, $selectedCityId, $redisKey, StatusList::POST_LISTING_GET_CITY);
        if ($selectedCityId) {
            $city = $this->location->loadCity($regionId, $selectedCityId);
            $notifyMessage = _T('City submitted successfully', [':city' => $city['Title']]);
            $message->setText(null);
            return $this->getNeighbourhood($message, $notifyMessage);
        }
    }

    public function getNeighbourhood(InputMessageAbstract $message, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $text = $message->getText();
        $redisKey = 'currentNeighbourhoods';

        $number = 0;
        $page = 1;
        switch ($text) {
            case CommandList::NEXT:
            case _C('NEXT'):
                $number = 1;
                break;
            case CommandList::PREVIOUS:
            case _C('PREVIOUS'):
                $number = -1;
                break;
        }
        if ($number) {
            $page = isset($userInfo[$redisKey]['page']) ? $userInfo[$redisKey]['page'] + $number : 1;
            $message->setText(null);
        }

        // check last step
        if (!isset($userInfo['location']['data']['cityId']) || !$userInfo['location']['data']['cityId']) {
            return $this->getCity($message);
        }
        $cityId = $userInfo['location']['data']['cityId'];

        $selectedNeighbourhoodId = null;
        $selectedNeighbourhoodName = null;
        $this->location->getNeighbourhood($message, $page, $cityId, $notifyMessage, $selectedNeighbourhoodId, $selectedNeighbourhoodName, $redisKey, StatusList::POST_LISTING_GET_NEIGHBOURHOOD);
        if ($selectedNeighbourhoodName) {
            $notifyMessage = _T('Neighbourhood submitted successfully', [':neighbourhood' => $selectedNeighbourhoodName]);
            $message->setText(null);
            return $this->finalStep($message, $notifyMessage);
        }
    }

    public function finalStep(InputMessageAbstract $message, $notifyMessage = null)
    {
        $userId = $message->getUserId();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $listingData = $this->getPostListingData($userId);

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

        $jsonData = [];

        // listing info
        $jsonData['title'] = $listingData['name'];
        $jsonData['description'] = $listingData['description'];
        $jsonData['categoryID'] = $listingData['categoryId'];
        $jsonData['price'] = isset($listingData['price']) ? $listingData['price'] : -1;
        $jsonData['attributes'] = array_values($listingData['optimizedAttributes']);
        $jsonData['images'] = $listingData['images'];

        // user info
        $jsonData['mobileNumber'] = preg_replace('/^\+?98/', '0', $userInfo['contact']['mobile']);
        $jsonData['email'] = $jsonData['mobileNumber'] . '@' . ApiService::SHEYPOOR_URL_SHORT;

        $cityId = isset($userInfo['location']['data']['cityId']) ? $userInfo['location']['data']['cityId'] : null;
        $regionId = $userInfo['location']['data']['id'];

        if (!$cityId) {
            // guess city id
            switch ($userInfo['location']['data']['id']) {
                case 8:
                    $cityId = 301;
                    break;
                case 4:
                    $cityId = 127;
                    break;
                case 14:
                    $cityId = 536;
                    break;
                default:
                    $result = $this->api->getCities($userInfo['location']['data']['id']);
                    $cityId = $result ? $result[0]['Id'] : 301;
            }
        }

        $jsonData['location'] = [
            "latitude" => isset($userInfo['location']['latitude']) ? $userInfo['location']['latitude'] : 0,
            "longitude" => isset($userInfo['location']['longitude']) ? $userInfo['location']['longitude'] : 0,
            "cityID" => $cityId,
            "provinceID" => $regionId,
            "districtID" => isset($userInfo['location']['data']['neighbourhoodId']) ? $userInfo['location']['data']['neighbourhoodId'] : ''
        ];

        $jsonData['districtName'] = isset($userInfo['location']['data']['neighbourhoodName']) ? $userInfo['location']['data']['neighbourhoodName'] : '';

        $validate = $this->validation($message, $jsonData);
        if ($validate === true) {
            $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);
            $result = $this->api->addNewListing($jsonData, $userInfoDb['token']);
            if (isset($result['id'])) {
                $userInfoDb = $this->messageHelper->getUserInfoFromDB($userId);
                $token = sha1($result['id'] . $result['id'] . md5($result['id'] . $userInfoDb['token'] . $result['id']));
                $activateUrl = urldecode($this->messageHelper->activateListingUrl($token, $result['id']));
                $messageData['text'] .= _T('Listing-Submit-Successfully', [
                    ':code' => $result['id'],
                    ':link' => $this->messageHelper->listingUrl($result['id']),
                    ':linkActivateIP' => str_replace('_', '\_', $activateUrl)
                ]);
                $messageData['disable_web_page_preview'] = true;

                //Final submit
                $inline_keyboard = [];
                $inline_keyboard[] = new InlineKeyboardButton([
                    'text' => _T('Final submit'),
                    'url' => $activateUrl
                ]);
                $messageData['reply_markup'] = new InlineKeyboardMarkup([
                    'inline_keyboard' => [$inline_keyboard]
                ]);
            } else {
                $messageData['text'] .= _T('Listing-Submit-Failed', [
                    ':error' => isset($result['error']['errorMessage']) ? implode("\n", $result['error']['errorMessage']) . "\n" : ''
                ]);
            }
            $buttons = [];
            $buttons[] = [
                'command' => CommandList::MY_LISTING,
                'text' => _C('MY_LISTING_LTR')
            ];
            $buttons[] = [
                'command' => CommandList::SHOW_LISTING,
                'text' => _C('SHOW_LISTING_LTR')
            ];
            $buttons[] = [
                'command' => CommandList::POST_LISTING,
                'text' => _C('POST_LISTING_LTR')
            ];
            $buttons[] = [];
            $buttons[] = [
                'command' => CommandList::MAIN_MENU,
                'text' => _C('MAIN_MENU')
            ];
            $messageData = $this->messageHelper->setNavigationButton($messageData, $buttons);
            $this->status->setStatus($userId, null, [], true);
            return Request::sendMessage($messageData);
        } else {
            return $validate;
        }
    }

    private function validation($message, $listingData)
    {
        if (!isset($listingData['title']) || !$listingData['title']) {
            return $this->getName($message);
        }
        if (!isset($listingData['description']) || !$listingData['description']) {
            return $this->getDescription($message);
        }
        if (!isset($listingData['categoryID']) || !$listingData['categoryID']) {
            return $this->getCategory($message);
        }
        return true;
    }

    private function getPostListingData($userId)
    {
        $listingData = $this->session->get(RedisConstant::CURRENT_POST_LISTING_DATA . $userId);
        $listingData = $listingData ? json_decode($listingData, true) : [];
        return $listingData;
    }

    private function setPostListingData($userId, $listingData)
    {
        $this->session->set(RedisConstant::CURRENT_POST_LISTING_DATA . $userId, json_encode($listingData));
        return true;
    }
}
