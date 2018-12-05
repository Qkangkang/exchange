<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InviteInfo
 * @package App\Models
 * @property integer $id
 * @property integer $uid
 * @property integer $b_uid
 * @property integer $mina_id
 * @property integer $status
 * @property integer $agree_type
 */
class InviteInfo extends Model
{
    protected $fillable = ['uid', 'b_uid', 'mina_id', 'status', 'agree_type'];
    public static function createInvite($info,$user){
        $result = self::create([
            'uid' => $user['id'],
            'b_uid' => $info['uid'],
            'mina_id' => $info['id'],
            'status' => 1,
            'agree_type' => false,
        ]);
        if (!$result) {
            return false;
        }
        return $result;
    }
}
