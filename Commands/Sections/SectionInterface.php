<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/26/16
 * Time: 8:11 PM
 */

namespace Trumpet\TelegramBot\Commands\Sections;

use Trumpet\TelegramBot\Services\Structures\InputMessageAbstract;

interface SectionInterface
{
    public function start(InputMessageAbstract $message);
}