<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/7
 * Time: 下午1:40
 */

namespace App\Utils;

class TimeUtil
{
    public static function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }


    public static function desc($format = 'Y-m-d H:i:s')
    {
        $date = strtotime($format);
        $second = time() - $date;
        if ($second < 60){
            $desc = $second . '秒前';
        }elseif ($second < 60 * 60){
            $desc = floor($second / 60) . '分钟前';
        }elseif ($second < 60 * 60 * 24){
            $desc = floor($second / (60 * 60)) . '小时前';
        }else{
            $desc = floor($second / (60 * 60 * 24)) . '天前';
        }

        return $desc;
    }

}