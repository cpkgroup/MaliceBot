<?php
namespace Trumpet\TelegramBot\Engine;
use Trumpet\TelegramBot\Config;

/**
 * Class DI
 * @package Trumpet\TelegramBot\Engine
 */
class DI implements DIInterface
{

    /**
     * List of registered services
     * @var Service[]
     */
    public $_services;

    /**
     * Latest DI build
     */
    protected static $_default;

    /**
     * Registers a service in the services container
     * @param $name
     * @param $definition
     * @param bool $shared
     * @return Service
     */
    public function set($name, $definition, $shared = true)
    {
        $service = new Service($name, $definition, $shared);
        $this->_services[$name] = $service;
        return $service;
    }


    /**
     * Resolves the service based on its configuration
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function get($name)
    {
        if (isset($this->_services[$name])) {
            $service = $this->_services[$name];
            /**
             * The service is registered in the DI
             */
            $instance = $service->resolve(self::$_default);
        } else {
            /**
             * The DI also acts as builder for any class even if it isn't defined in the DI
             */
            if (!class_exists($name)) {
                throw new \Exception("Service '" . $name . "' wasn't found in the dependency injection container");
            } else {
                $instance = new $name;
            }
        }
        return $instance;
    }

    public function getShared($name)
    {
        return $this->get($name);
    }

    public function setShared($name, $definition)
    {
        return $this->set($name, $definition);
    }

    /**
     * Return the latest DI created
     */
    public static function getDefault()
    {
        if (!self::$_default) {
            self::$_default = new self;
        }
        return self::$_default;
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_services[$name]);
    }

    /**
     * Resets the internal default DI
     */
    public static function reset()
    {
        self::$_default = null;
    }

    /**
     * Magic method __get
     */
    public function __get($propertyName)
    {
        /**
         * Fallback to the PHP userland if the cache is not available
         */
        if ($this->has($propertyName)) {
            $service = $this->getShared($propertyName);
            $this->{$propertyName} = $service;
            return $service;
        }

        /**
         * A notice is shown if the property is not defined and isn't a valid service
         */
        trigger_error("Access to undefined property " . $propertyName);
        return null;
    }

    public function getConfig()
    {
        return Config::getConfig();
    }
}
