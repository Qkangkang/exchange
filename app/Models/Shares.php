<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shares extends Model
{
    //
    public static function createShare($uid, $init_uid){
        $share = new Shares();
        $share->uid = $uid;
        $share->group_id = $init_uid;
        $share->add_time = time();
        return $share->save();
    }
}
