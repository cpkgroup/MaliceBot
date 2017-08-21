<?php

namespace Trumpet\TelegramBot\Services;

use Symfony\Component\Yaml\Yaml;
use Trumpet\TelegramBot\Commands\Constants\RedisConstant;
use Trumpet\TelegramBot\Engine\Injectable;

/**
 * Class CultureService
 * @package Trumpet\TelegramBot\Services
 */
class CultureService extends Injectable
{
    public function trans($key, $params = [], $domain = 'messages', $locale = null, $useCache = true)
    {
        $locale = $locale ? $locale : $this->getConfig()['telegram']['locale'];
        $yml = $useCache ? $this->session->get(RedisConstant::CULTURE . $locale . '_' . $domain) : null;
        if (!$yml || isset($this->getConfig()['development'])) {
            $yml = self::loadCulture($domain, $locale);
        } else {
            $yml = json_decode($yml, true);
        }

        if (isset($yml[$key])) {
            if (is_string($yml[$key]) || is_numeric($yml[$key])) {
                return strtr(trim($yml[$key]), $params);
            } else {
                return json_encode($yml[$key]);
            }
        } else {
            // key not exists so reload redis
            if ($useCache) {
                self::loadCulture($domain, $locale);
            }
            return $key;
        }
    }

    public function command($command)
    {
        return self::trans($command, [], 'commands');
    }

    public function photoId($title)
    {
        $botName = strtolower($this->getConfig()['telegram']['botName']);
        return self::trans($title, [], 'photos.' . $botName, 'en');
    }

    private function loadCulture($domain, $locale)
    {
        $culturePath = $this->getConfig()['telegram']['culturePath'];
        $yml = Yaml::parse(file_get_contents($culturePath . '/' . $locale . '/' . $domain . '.yml'));
        $this->session->set(RedisConstant::CULTURE . $locale . '_' . $domain, json_encode($yml));
        return $yml;
    }
}
