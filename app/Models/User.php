<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * @package App\Models
 * @property integer $id
 * @property string $nick_name
 * @property string $user_name
 * @property string $avatar
 * @property string $wechat
 * @property string $openid
 * @property string $phone
 * @property string $company
 * @property integer $apply_count
 * @property integer $status
 * @property string $unionid
 * @property string $access_token
 * @property integer $uid
 * @property string $session_key
 * @property integer $login_at
 */
class User extends Model
{
    const DEFAULT_AVATAR = 'https://image.lingyiliebian.com/3cb3355d4b262fd7890a976b54450dac.png';

    public static function updateApplyCount($count){
        $user = new User();
        return $user->where('id','>',0)->update(['remain_apply_count'=>$count]);
    }
}
