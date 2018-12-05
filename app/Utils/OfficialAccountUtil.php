<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/7
 * Time: 下午4:22
 */

namespace App\Utils;


use Overtrue\LaravelWeChat\Facade as EasyWeChat;

class OfficialAccountUtil
{
    public static function getApp($name = '')
    {
        return EasyWeChat::officialAccount($name);
    }

    public static function getAppId($name = 'default')
    {
        return config('wechat.official_account.' . $name . '.app_id');
    }

    public static function getSecret($name = 'default')
    {
        return config('wechat.official_account.' . $name . '.secret');
    }

    public static function getToken($name = 'default')
    {
        return config('wechat.official_account.' . $name . '.token');
    }

    public static function getAesKey($name = 'default')
    {
        return config('wechat.official_account.' . $name . '.aes_key');
    }


    public static function createMenu()
    {
        $app = self::getApp();
        $app->menu->delete();

        $buttons = [
            [
                'type' => 'view',
                'name' => '马上赚钱',
                'url' => 'https://swzx.lingyiliebian.com/h5/index',
            ],
            [
                'type' => 'view',
                'name' => '组队试玩',
                'url' => 'https://swzx.lingyiliebian.com/h5/collector',
            ],

            [
                'name' => '提现',
                'sub_button' => [
                    [
                        'type' => 'view',
                        'name' => '福利群',
                        'url' => 'http://a.gzliemao.com/c/3aa9cb8e73d1c679ae428ca3587da500',
                    ],
                    [
                        'type' => 'view',
                        'name' => '提现',
                        'url' => 'https://swzx.lingyiliebian.com/h5/my',
                    ]
                ]

            ]

            //[
            //    'type' => 'miniprogram',
            //    'name' => '程序试玩中心',
            //    'url' => 'https://swzx.lingyiliebian.com/',
            //    'appid' => 'wx7ac07da7a8a4acbd',
            //    'pagepath' => 'pages/index/index'
            //],
        ];


        return $app->menu->create($buttons);
    }

    public static function getMenuList()
    {
        $app = self::getApp();

        return $app->menu->list();
    }


}