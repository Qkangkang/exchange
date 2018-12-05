<?php

namespace App\Models;

use App\Services\WechatServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class TemplateMessage
 * @package App\Models
 * @property integer $id
 * @property integer $uid
 * @property string $formid
 * @property integer $add_time
 * @property integer $type
 * @property integer $status
 * @property string $msg
 */
class TemplateMessage extends Model
{
    const INVITE = 'invite';
    const AGREE_INVITE = 'agree_invite';
    const REFUSE_INVITE = 'refuse_invite';
    const MESSAGE_CODE = [
        self::INVITE => 'QXJ4sy7f9glqUVZsE8YlxdvrRtJxDkA3Uz8IsGINpwI',//"邀请",
        self::AGREE_INVITE => 'QXJ4sy7f9glqUVZsE8YlxYsoRPDgf7geJYu1IXIm1ww',//成功邀请
        self::REFUSE_INVITE => 'QXJ4sy7f9glqUVZsE8YlxYsoRPDgf7geJYu1IXIm1ww'//拒绝邀请
    ];
    const INVITE_MSG = '你发布的换量信息，有人邀请你啦，赶紧去看看吧';
    const AGREE_INVITE_MSG = '赶紧去看看对方的信息，去沟通吧';
    const REFUSE_INVITE_MSG = '赶紧去换量大厅找发现更适合你的人去换量吧';
    //
    //换取token
    public static function getAccessToken()
    {
        
        if (empty(Cache::get('user_access_token'))) {
            $appid = config("wechat.mini_program.default.app_id");
            $secret = config("wechat.mini_program.default.secret");
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
            $data = WechatServices::http_curl($url, [], $ispost = 0, $https = 1);
            $data = json_decode($data, true);
            Cache::put('user_access_token', $data['access_token'], 12);
        }
        return Cache::get("user_access_token");
    }
    
    public static function sendTplMessage($data, $type, $notice = '您有一条未读消息')
    {
        $token = self::getAccessToken();
        Log:info('token:'.$token);
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=" . $token;
        $arr = [];
        if ($type == self::INVITE) {
            $info = [
                "keyword1" => [
                    "value" => $data['nick_name'],
                ],
                "keyword2" => [
                    "value" => $notice,
                ],
            ];
            $arr['page'] = "pages/index?skip_page=together";
        } else if($type == self::AGREE_INVITE) {
            $info = [
                "keyword1" => [
                    "value" => '已通过邀请',
                ],
                "keyword2" => [
                    "value" => $data['nick_name'],
                ],
                "keyword3" => [
                    "value" => $notice,
                ],
            ];
            $arr['page'] = "pages/index?skip_page=together";
        } else if($type == self::REFUSE_INVITE) {
            $info = [
                "keyword1" => [
                    "value" => '已被拒绝',
                ],
                "keyword2" => [
                    "value" => $data['nick_name'],
                ],
                "keyword3" => [
                    "value" => $notice,
                ],
            ];
            $arr['page'] = "pages/index?skip_page=together";
        }
        $formid = FormId::getFormId($data['uid']);
        if(empty($formid)){
            return false;
        }
        $arr['touser'] = $data['send_openid'];
        $arr['template_id'] = self::MESSAGE_CODE[$type];
        $arr['form_id'] = $formid;
        $arr['data'] = $info;
        $result = WechatServices::postCurl($url, $arr,"json");
        $result = json_decode($result, true);
        $arr = [
            'uid' => $data['uid'],
            'formid' => $arr['form_id'],
            'add_time' => time(),
            'type' => $type,
        ];
        if ($result['errmsg'] == "ok") {
            $arr['status'] = 1;
        } else {
            Log::info($result['errmsg']);
            $arr['status'] = 2;
            $arr['msg'] = $result['errmsg'];
        }
        return $arr;
        
    }


}
