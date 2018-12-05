<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MinaCategory
 * @package App\Models
 * @property integer $id
 * @property string $name
 */
class MinaCategory extends Model
{


    public static function getMinaType($id){
        return self::where("id",$id)->first();
        //return MinaInfo::where("name",$name)->first();
    }
}
