<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/25/16
 * Time: 4:40 PM
 */

namespace Trumpet\TelegramBot\Engine;

class Service
{

    protected $_name;

    protected $_definition;

    protected $_shared = false;

    protected $_sharedInstance;

    /**
     * Phalcon\Di\Service
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     */
    public final function __construct($name, $definition, $shared = false)
    {
        $this->_name = $name;
        $this->_definition = $definition;
        $this->_shared = $shared;
    }

    /**
     * Returns the service's name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Set the service definition
     *
     * @param mixed $definition
     */
    public function setDefinition($definition)
    {
        $this->_definition = $definition;
    }

    /**
     * Returns the service definition
     *
     * @return mixed
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /**
     * Resolves the service
     *
     * @param null $dependencyInjector
     * @return mixed
     * @throws \Exception
     */
    public function resolve($dependencyInjector = null)
    {
        $shared = $this->_shared;
        /**
         * Check if the service is shared
         */
        if ($shared) {
            $sharedInstance = $this->_sharedInstance;
            if ($sharedInstance !== null) {
                return $sharedInstance;
            }
        }

        $found = true;
        $instance = null;

        $definition = $this->_definition;
        if (is_string($definition)) {
            /**
             * String definitions can be class names without implicit parameters
             */
            if (class_exists($definition)) {
                $instance = new $definition;
            } else {
                $found = false;
            }
        } else if (is_object($definition)) {
            /**
             * Object definitions can be a Closure or an already resolved instance
             */
            if ($definition instanceof \Closure) {
                /**
                 * Bounds the closure to the current DI
                 */
                if ($dependencyInjector) {
                    $definition = \Closure::bind($definition, $dependencyInjector);
                }
                $instance = call_user_func($definition);
            } else {
                $instance = $definition;
            }
        }

        /**
         * If the service can't be built, we must throw an exception
         */
        if ($found === false) {
            throw new \Exception("Service '" . $this->_name . "' cannot be resolved");
        }

        /**
         * Update the shared instance if the service is shared
         */
        if ($shared) {
            $this->_sharedInstance = $instance;
        }
        return $instance;
    }
}