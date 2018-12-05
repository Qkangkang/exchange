<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/7
 * Time: 下午1:40
 */

namespace App\Utils;

class PriceUtil
{
    /**
     * @param int $price 金额
     * @param int $divisor 被除数
     * @param int $precision 保留几位小数
     * @return float|int
     */
    public static function format($price = 0, $divisor = 10000, $precision = 4)
    {
        if ($divisor < 0){
            return 0;
        }

        return round($price / $divisor, $precision);
    }

}