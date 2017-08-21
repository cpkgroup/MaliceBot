<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 7/31/16
 * Time: 9:53 AM
 */

namespace Trumpet\TelegramBot\Services\Structures;

use Trumpet\TelegramBot\Services\Interfaces\InputMessageInterface;

abstract class InputMessageAbstract implements InputMessageInterface
{
    private $text = 'null';

    public function setText($text)
    {
        $this->text = $text;
    }

    abstract protected function _getText();

    public function getText()
    {
        return $this->text !== 'null' ? $this->text : $this->_getText();
    }
}