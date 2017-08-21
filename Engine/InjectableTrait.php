<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/26/16
 * Time: 10:30 AM
 */

namespace Trumpet\TelegramBot\Engine;
use Trumpet\TelegramBot\Config;

/**
 * Class Injectable
 * @property \Redis session
 * @property \Trumpet\TelegramBot\Services\ApiService api
 * @property \Trumpet\TelegramBot\Services\CategoryService category
 * @property \Trumpet\TelegramBot\Services\LocationService location
 * @property \Trumpet\TelegramBot\Services\StatusService status
 * @property \Trumpet\TelegramBot\Services\CultureService culture
 * @property \Trumpet\TelegramBot\Services\MessageService messageHelper
 * @property \Trumpet\TelegramBot\Services\ThrottleService throttle
 * @property \Trumpet\TelegramBot\Services\MysqlService mysql
 * @property \Trumpet\TelegramBot\Services\HealthService health
 * @property \Trumpet\TelegramBot\Services\NumberFormat numberFormat
 * @property \Trumpet\TelegramBot\Services\AuthService auth
 * @package Trumpet\TelegramBot\Engine
 */
trait InjectableTrait
{
    /**
     * Search items limit
     * @var int
     */
    protected $limit = 10;

    /**
     * Dependency Injector
     *
     * @var DIInterface
     */
    protected $_dependencyInjector;

    /**
     * Sets the dependency injector
     * @param DIInterface $dependencyInjector
     */
    public function setDI(DIInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     */
    public function getDI()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if (!$dependencyInjector) {
            $dependencyInjector = DI::getDefault();
        }
        return $dependencyInjector;
    }

    public function getConfig()
    {
        return Config::getConfig();
    }

    /**
     * Magic method __get
     */
    public function __get($propertyName)
    {
        $dependencyInjector = $this->getDI();
        /**
         * Fallback to the PHP userland if the cache is not available
         */
        if ($dependencyInjector->has($propertyName)) {
            $service = $dependencyInjector->getShared($propertyName);
            return $service;
        }

        if ($propertyName == "di") {
            return $dependencyInjector;
        }

        /**
         * A notice is shown if the property is not defined and isn't a valid service
         */
        trigger_error("Access to undefined property " . $propertyName);
        return null;
    }
}