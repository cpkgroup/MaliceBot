<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 9/26/16
 * Time: 10:28 AM
 */

namespace Trumpet\TelegramBot\Engine;

/**
 * This interface must be implemented in those classes that uses internally the DI that creates them
 */
interface InjectionAwareInterface
{
    /**
     * Sets the dependency injector
     * @param DIInterface $dependencyInjector
     */
    public function setDI(DIInterface $dependencyInjector);

    /**
     * Returns the internal dependency injector
     */
    public function getDI();
}
