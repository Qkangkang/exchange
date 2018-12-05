<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FormId
 * @package App\Models
 * @property integer $id
 * @property string $form_id
 * @property integer $uid
 * @property integer $express
 */
class FormId extends Model
{
    public static function getFormId($userId)
    {
        $time = time() + 7 * 3600 * 24;
        $find = self::select(['id', 'form_id'])
            ->where(["uid" => $userId, "is_use" => 0])
            ->where("express", ">", time())
            ->orderBy("created_at", "desc")
            ->first();
        if (empty($find)){
            return false;
        }
        $find->is_use = 1;
        $find->save();
        return $find->form_id;
    }
    
    public static function setFormId($userId, $form)
    {
        $formId = FormId::where([
            ['uid', '=', $userId],
            ['form_id', '=', $form],
        ])->first();
        if (empty($formId)){
            $formId = new FormId();
            $formId->uid = $userId;
            $formId->is_use = 0;
            $formId->express = strtotime("+1 week");
            $formId->form_id = $form;
            return $formId->save();
        }else{
            FormId::where([
                ['uid', '=', $userId],
                ['form_id', '=', $form],
            ])->update([
                'express' => strtotime("+1 week"),
            ]);
        }
        return true;
    }
}
