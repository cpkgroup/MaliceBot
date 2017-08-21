<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/25/16
 * Time: 3:26 PM
 */

namespace Trumpet\TelegramBot\Commands\Constants;


class StatusList
{
    /* START SECTION */
    const START = 'Trumpet\\TelegramBot\\Commands\\Sections\\StartSection:start';
    
    /* SUPPORT SECTION */
    const SUPPORT = 'Trumpet\\TelegramBot\\Commands\\Sections\\SupportSection:start';

    /* DOWNLOAD SECTION */
    const DOWNLOAD = 'Trumpet\\TelegramBot\\Commands\\Sections\\DownloadSection:start';

    /* MY LISTING SECTION */
    const MY_LISTING = 'Trumpet\\TelegramBot\\Commands\\Sections\\MyListingSection:start';
    const MY_LISTING_AUTH = 'Trumpet\\TelegramBot\\Commands\\Sections\\MyListingSection:auth';
    const MY_LISTING_REMOVE = 'Trumpet\\TelegramBot\\Commands\\Sections\\MyListingSection:remove';

    /* POST LISTING SECTION */
    const POST_LISTING = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:start';
    const POST_LISTING_GET_NAME = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getName';
    const POST_LISTING_GET_DESCRIPTION = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getDescription';
    const POST_LISTING_GET_CATEGORY = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getCategory';
    const POST_LISTING_GET_PRICE = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getPrice';
    const POST_LISTING_GET_ATTR = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getAttr';
    const POST_LISTING_GET_PHOTOS = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getPhotos';
    const POST_LISTING_GET_LOCATION = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getLocation';
    const POST_LISTING_GET_REGION = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getRegion';
    const POST_LISTING_GET_CITY = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getCity';
    const POST_LISTING_GET_NEIGHBOURHOOD = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getNeighbourhood';
    const POST_LISTING_GET_CONTACT = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:getContactInfo';
    const POST_LISTING_AUTH = 'Trumpet\\TelegramBot\\Commands\\Sections\\PostListingSection:auth';


    /* SHOW LISTING SECTION */
    const SHOW_LISTING_START = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:start';
    const SHOW_LISTING_GET_KEYWORD = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:getKeyword';
    const SHOW_LISTING_GET_CATEGORY = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:getCategory';
    const SHOW_LISTING_SET_CATEGORY = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:setCategory';
    const SHOW_LISTING_SEARCH = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:search';
    const SHOW_LISTING_DETAIL = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:detail';
    const SHOW_LISTING_GET_LOCATION = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:getLocation';
    const SHOW_LISTING_GET_REGION = 'Trumpet\\TelegramBot\\Commands\\Sections\\ShowListingSection:getRegion';
}