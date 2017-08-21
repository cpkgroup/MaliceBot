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
use Longman\TelegramBot\Entities\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputTextMessageContent;
use Longman\TelegramBot\Request;
use Trumpet\TelegramBot\Engine\InjectableTrait;
use Trumpet\TelegramBot\Services\ApiService;

/**
 * Inline query command
 */
class InlinequeryCommand extends SystemCommand
{
    use InjectableTrait;
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'inlinequery';
    protected $description = 'Reply to inline query';
    protected $version = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $update = $this->getUpdate();
        $inline_query = $update->getInlineQuery();
        $keyword = $inline_query->getQuery();
        if (!$keyword) {
            return Request::emptyResponse();
        }

        $userId = $inline_query->getFrom()->getId();
        $userInfo = $this->messageHelper->getUserInfo($userId);
        $regionId = isset($userInfo['location']['data']['id']) ? $userInfo['location']['data']['id'] : null;

        $searchQuery = [];
        if ($regionId) {
            $searchQuery['regionId'] = $regionId;
        }
        if ($keyword) {
            $searchQuery['q'] = $keyword;
        }
        $listings = $this->api->searchListing($searchQuery, 20);
        $articles = [];
        if (count($listings['items']) > 0) {
            foreach ($listings['items'] as $listing) {
                $articles[] = [
                    'id' => $listing['id'],
                    'title' => $listing['name'],
                    'thumb_url' => $listing['images'],
                    'description' => $listing['locationName'] . '، ' . $listing['date'],
                    'input_message_content' => new InputTextMessageContent([
                        'message_text' => $this->messageHelper->listingUrl($listing['id'])
                    ]),
                ];
            }
        } else {
            return Request::emptyResponse();
        }

        $data['inline_query_id'] = $inline_query->getId();
        $array_article = [];
        foreach ($articles as $article) {
            $array_article[] = new InlineQueryResultArticle($article);
        }
        $data['results'] = '[' . implode(',', $array_article) . ']';

        return Request::answerInlineQuery($data);
    }
}
