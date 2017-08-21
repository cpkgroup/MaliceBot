<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/30/16
 * Time: 9:33 AM
 */

namespace Trumpet\TelegramBot\Services;


use Longman\TelegramBot\TelegramLog;

class ApiService
{
    const SHEYPOOR_URL = 'https://1831d30f.ngrok.io';
    const SHEYPOOR_URL_SHORT = '1831d30f.ngrok.io';

//    const SHEYPOOR_URL = 'trumpet:newsite@staging.mielse.com';
//    const SHEYPOOR_URL_SHORT = 'staging.mielse.com';

    /**
     * @param int $listingId
     * @return array
     */
    public function listingDetails($listingId)
    {
        $listing = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/offer/optimized/' . $listingId), true);
        if (isset($listing['ad'])) {
            return $listing;
        } else {
            TelegramLog::error('Api error on get listing, id: ' . $listingId);
            return [];
        }
    }

    /**
     * @param array $searchQuery
     * @param int $limit
     * @param int $skip
     * @return array
     */
    public function searchListing($searchQuery = [], $limit = 10, $skip = 0)
    {
        $query = '';
        foreach ($searchQuery as $key => $value) {
            $query .= '&' . $key . '=' . urlencode($value);
        }
        $url = self::SHEYPOOR_URL . '/api/v2/offer/optimizedlist?take=' . $limit . '&skip=' . $skip . $query;
        $listings = json_decode($this->cURL($url), true);
        if (isset($listings['items'])) {
            return $listings;
        } else {
            TelegramLog::error('Api error on get listings, url: ' . $url);
            return [];
        }
    }

    public function getCities($regionId)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/location/cities/' . $regionId), true);
        if (isset($result[0]['Id'])) {
            return $result;
        } else {
            TelegramLog::error('Api error on get cities, regionId: ' . $regionId);
            return [];
        }
    }

    public function getNeighbourhoods($cityId)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/location/neighbourhoods/' . $cityId), true);
        if (isset($result[0]['Id'])) {
            return $result;
        } else {
            return [];
        }
    }

    public function getRegions()
    {
        $result = json_decode($this->cURL(ApiService::SHEYPOOR_URL . '/api/location/regions'), true);
        if (isset($result[0]['Id'])) {
            return $result;
        } else {
            TelegramLog::error('Api error on get regions');
            return [];
        }
    }

    public function getGeoLocation($latitude, $longitude)
    {
        $location = json_decode($this->cURL(ApiService::SHEYPOOR_URL . '/api/geo?latitude=' . $latitude . '&longitude=' . $longitude), true);
        if (isset($location['success']) && $location['success']) {
            return $location['data'];
        } else {
            TelegramLog::error('Api error on get geo location ');
            return [];
        }
    }

    public function getIPInfo($ip)
    {
        $result = json_decode($this->cURL(ApiService::SHEYPOOR_URL . '/api/getIPInfo?ip=' . $ip), true);
        if (isset($result['isValid'])) {
            return $result['isValid'];
        } else {
            TelegramLog::error('Api error on get ip info');
            return true;
        }
    }

    public function uploadImage($filePath)
    {
        $filePath = realpath($filePath);
        $result = json_decode($this->uploadCURL(self::SHEYPOOR_URL . '/api/v3/listings/images', $filePath), true);
        if (isset($result['imageKey'])) {
            return $result;
        } else {
            TelegramLog::error('Api error on upload image, filePath: ' . $filePath);
            return [];
        }
    }

    public function addNewListing($data, $ticket = null)
    {
        $data['inactive'] = true;
        $result = json_decode($this->jsonCURL(self::SHEYPOOR_URL . '/api/v3/listings', $data, $ticket ? ['x-ticket: ' . $ticket] : []), true);
        if (!isset($result['id'])) {
            TelegramLog::error('Add new listing error: ' . json_encode($data) . json_encode($result));
        }
        return $result;
    }

    public function getAttributes($categoryId)
    {
        $attributes = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/getAttsByCat?id=' . $categoryId), true);
        if (isset($attributes['data'])) {
            return $attributes['data'];
        } else {
            TelegramLog::error('Get attr by cat error: ' . json_encode($categoryId) . '-' . json_encode($attributes));
            return [];
        }
    }

    public function getCategories()
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/category/categories'), true);
        if (isset($result[0]['Id'])) {
            return $result;
        } else {
            TelegramLog::error('Api error on get categories');
            return [];
        }
    }

    public function getImage($imgUrl)
    {
        return $this->cURL($imgUrl);
    }

    public function checkHealth()
    {
        return $this->getCategories() ? true : false;
    }

    public function auth($username)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/auth', 'POST', ['username' => $username], ['Phone-Base: true']), true);
        if (!$result || !isset($result['token']) || isset($result['errors'])) {
            TelegramLog::error('Auth error: ' . $username . ':' . json_encode($result));
            return false;
        }
        return $result['token'];
    }

    public function verify($username, $code, $token)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/auth/verify', 'POST', ['code' => $code, 'token' => $token], ['Phone-Base: true']), true);
        if (!$result || isset($result['errors'])) {
            TelegramLog::error('Auth error: ' . $username . ':' . json_encode($result));
            return false;
        } else if (isset($result['ticket'])) {
            return $result;
        }
        return false;
    }

    public function getUserListing($ticket)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/user/offer', 'GET', [], ['Phone-Base: true', 'x-ticket: ' . $ticket]), true);
        if (!$result || isset($result['error']) || !isset($result['items'])) {
            TelegramLog::error('Get my listing error: ' . json_encode($ticket) . json_encode($result));
            return false;
        }
        return $result;
    }


    public function delete($listingId, $ticket)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/user/offer?id=' . $listingId, 'DELETE', [], ['Phone-Base: true', 'x-ticket: ' . $ticket]), true);
        if (!$result || isset($result['error'])) {
            TelegramLog::error('Delete listing error: ' . json_encode($listingId) . json_encode($result));
            return [false, $result['error'][0]];
        }
        return [true, $result['message']];
    }

    public function upToDate($listingId, $ticket)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/user/offer/' . $listingId . '/up-to-date', 'GET', [], ['Phone-Base: true', 'x-ticket: ' . $ticket]), true);
        if (!$result || isset($result['error'])) {
            TelegramLog::error('Up to date listing error: ' . json_encode($listingId) . json_encode($result));
            return [false, $result['error'][0]];
        }
        return [true, $result['message']];
    }

    public function isLoggedIn($ticket)
    {
        $result = json_decode($this->cURL(self::SHEYPOOR_URL . '/api/v3/auth/is-logged-in', 'POST', [], ['x-ticket: ' . $ticket]), true);
        if (!$result) {
            TelegramLog::error('Get is logged error: ' . json_encode($ticket) . json_encode($result));
            return false;
        }
        if (isset($result['success']) && $result['success']) {
            return true;
        }
        return false;
    }

    private function cURL($url, $method = 'GET', $data = [], $headers = [])
    {
        $ch = curl_init();

        $data_string = '';
        if ($data) {
            foreach ($data as $key => $value) {
                $data_string .= $key . '=' . urlencode($value) . '&';
            }
            $data_string = trim($data_string, '&');
            $headers[] = 'Content-Length: ' . strlen($data_string);
        }

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // follow redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        } else if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }

        // set headers
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // $output contains the output string
        $output = curl_exec($ch);
        // close curl resource to free up system resources
        curl_close($ch);
        return $output;
    }

    private function jsonCURL($url, $data, $headers = [])
    {
        $data_string = json_encode($data);

        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($data_string);

        // set headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        return $output;
    }

    private function uploadCURL($url, $filePath)
    {
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        // send a file
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => '@' . $filePath]);

        //CURLOPT_SAFE_UPLOAD defaulted to true in 5.6.0
        //So next line is required as of php >= 5.6.0
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);

        // output the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);

        // close the session
        curl_close($ch);

        return $output;
    }

}