<?php

namespace App\Models;

use App\Services\MinaInfoService;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TopLine
 * @package App\Models
 * @property integer $id
 * @property string $remark
 * @property integer $status
 */
class TopLine extends Model
{
    protected $fillable = ['remark','status'];
    public static function createTopLine($user_mina_info, $inviterInfo){
        $inviteMina = MinaInfoService::getMinaByCondition('uid',$inviterInfo['id']);
        if($inviteMina){
            $inviterData = $inviteMina['name'];
        }else{
            $inviterData = $inviterInfo['company'];
        }
        $remark = $user_mina_info['name'].'与'.$inviterData.'刚达成换量合作';
        $data['remark'] = $remark;
        $data['status'] = 1;
        $result = self::create($data);
        if (!$result) {
            return false;
        }
        return $result;
    }
}
