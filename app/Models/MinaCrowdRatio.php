<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MinaCrowdRatio
 * @package App\Models
 * @property integer $id
 * @property integer $mid
 * @property string $ratio_name
 * @property integer $ratio_num
 * @property integer $ratio_type
 */
class MinaCrowdRatio extends Model
{
    //
    public function minaInfo(){
        return $this->hasMany(MinaInfo::class,'mid');
    }
}
