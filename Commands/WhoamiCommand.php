<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Marco Boretto <marco.bore@gmail.com>
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;

/**
 * User "/whoami" command
 */
class WhoamiCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $enabled = false;
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
    }
}
