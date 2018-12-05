<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Http\Models\Status;

class CacheService
{

    private static $prefix  = 'exchange';
    private static $version = '1.0';

    /**
     * 构建缓存key
     * @param $key
     * @return string
     */
    public static function buildCacheKey($key)
    {
        return sprintf('%s_%s_', self::$prefix, self::$version) . $key;
    }

    public static function increment($key, $value)
    {
        if (!env('REDIS_CACHE_OPEN')){
            return false;
        }
        $res = Cache::has(self::buildCacheKey($key));
        if ($res === false){
            return false;
        }

        return Cache::increment(self::buildCacheKey($key), $value);
    }

    /**
     * 获取缓存
     * @param $key
     * @return bool|mixed
     */
    public static function getCache($key)
    {
        if (!env('REDIS_CACHE_OPEN')){
            return false;
        }
        $res = Cache::get(self::buildCacheKey($key));
        if ($res === null){
            return false;
        }

        return $res;
    }

    /**
     * 设置缓存
     * @param $key
     * @param $data
     * @param int $minutes
     * @return bool
     */
    public static function setCache($key, $data, $minutes = 720)
    {
        if (!env('REDIS_CACHE_OPEN')){
            return true;
        }
        self::clearCache($key);
        return Cache::add(self::buildCacheKey($key), $data, $minutes);
    }

    /**
     * 设置永久缓存
     * @param $key
     * @param $data
     * @return bool
     */
    public static function setForeverCache($key, $data)
    {
        if (!env('REDIS_CACHE_OPEN')){
            return true;
        }
        self::clearCache($key);
        Cache::forever(self::buildCacheKey($key), $data);
    }

    /**
     * 清除缓存
     * @param $key
     * @return bool
     */
    public static function clearCache($key)
    {
        if (!env('REDIS_CACHE_OPEN')){
            return true;
        }

        return Cache::forget(self::buildCacheKey($key));
    }
    
    
    /**
     * 邀请通知列表
     * @param int $userId
     * @param int $type  agree_or_not 接受邀请等待同意   agree  已同意
     * @return string
     */
    public static function userInviteCacheKey($userId,$type){
        return 'user_invite_info_'.$userId.'_'.$type;
    }
    /**
     * 接受邀请统计
     * @param int $userId
     * @return string
     */
    public static function userAcceptInviteCountKey($userId){
        return 'user_invite_accept_count_'.$userId;
    }
    //发起邀请数字的
    /**
     * 发起邀请统计
     * @param int $userId
     * @return string
     */
    public static function userLaunchInviteCountKey($userId){
        return 'user_launch_invite_count_'.$userId;
    }
    
    public static function userAllInviteCountKey($userId){
        return 'user_all_invite_count_'.$userId;
    }
    
    public static function MinaTypeKey(){
        return 'mina_type';
    }

    public static function minaInfoForId($id){
        return 'mina_info_for_id_'.$id;
    }
    //首页头部消息轮播
    public static  function topLineKey(){
        return 'top_line_list';
    }

    //用户token
    public static function userToken($uid){
        return 'user_token_relate'.$uid;
    }

    //用户更新次数
    public static function userUpdateReleaseKey($uid){
        return 'user_update_release'.$uid;
    }

    //保存微信客服回复进群图片media_id的key
    public static function customServiceJoinInGroup(){
        return 'custom_service_join_in_group';
    }

    //存储每个用户出来的mina_id列表
    public static function userSearchMinaIdList($uid){
        return 'user_search_mina_id_list'.$uid;
    }
}