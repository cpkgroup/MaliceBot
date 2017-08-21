<?php
/**
 * Created by PhpStorm.
 * User: mohamad
 * Date: 8/1/16
 * Time: 10:01 AM
 */

namespace Trumpet\TelegramBot\Services;

class NumberFormat
{
    /**
     * Replace all English numbers to Persian
     *
     * @param string $str
     * @return string
     */
    public function persianNumber($str)
    {
        $numbers = [
            0 => '۰', 1 => '۱', 2 => '۲', 3 => '۳', 4 => '۴', 5 => '۵', 6 => '۶', 7 => '۷', 8 => '۸', 9 => '۹',
        ];
        return strtr($str, $numbers);
    }

    /**
     * Replace all Persian and Arabic numbers to English
     *
     * @param string $str
     * @return string
     */
    public function englishNumber($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        $numbers = [
            '٠' => 0, '١' => 1, '٢' => 2, '٣' => 3, '٤' => 4, '٥' => 5, '٦' => 6, '٧' => 7, '٨' => 8, '٩' => 9,
            '۰' => 0, '۱' => 1, '۲' => 2, '۳' => 3, '۴' => 4, '۵' => 5, '۶' => 6, '۷' => 7, '۸' => 8, '۹' => 9,
        ];
        return strtr($str, $numbers);
    }

    public function priceFormat($str)
    {
        $str = number_format($str);
        $str = $this->persianNumber($str);
        return $str;
    }
}
