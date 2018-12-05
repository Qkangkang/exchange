<?php


namespace App\Services;


use App\Http\Models\Status;
use App\Models\MinaInfo;
use App\Models\User;
use GuzzleHttp\Psr7\Request;
use App\Models\TemplateMessage;
use App\Models\Shares;
use Illuminate\Support\Facades\Log;
use Overtrue\LaravelWeChat\Facade as EasyWeChat;

class UserService
{

    /**
     * 查用户信息
     */
    public static function getUserById($id,$param = ''){
        $User = new User(); 
        if($param){
            return $User::select($param)
                     ->where("id",$id)
                     ->get()->first();
        }
        return $User::where("id",$id)->first();
    }

    public static function changeUserInfo($request){
        $userModel = new User();
        $data = [
            'phone' => $request->get('phone'),
            'company' => $request->get('company'),
            'wechat' => $request->get('wechat'),
            'user_name' => $request->get("user_name"),
            'company_address' => $request->get("address",''),
            'job' => $request->get("job",'')
        ];
        return $userModel->where('id', $request->user['id'])->update($data);
    }

    /**
     * 发送模板消息
     * @param        $uid       消息接收方
     * @param        $user      消息发送方
     * @param string $type      模板消息类型  'invite' 邀请合作   ‘agree_invite’ 同意合作   ‘refuse_invite’  拒绝合作
     * @param string $notice    备注
     */
    public static function sendMessageForweChat($uid,$user,$type = 'invite',$notice = '您有一条未读消息'){
        $users = self::getUserById($uid,['openid','nick_name']);
        $arr = [
            'send_openid' => $users->openid,
            'nick_name' => $user['nick_name'],
            'uid' => $uid,
            'info' =>$notice
        ];
        TemplateMessage::sendTplMessage($arr,$type,$notice);
    }

    /**
     * 登录请求
     * @param string   $code
     * @param int      $uid
     * @param string   $mid
     * @param User     $userData
     * @param MinaInfo $minaInfo
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public static function goLogin($code = '', $uid = 0 ,$mid = '',User $userData,MinaInfo $minaInfo){
        $wechat = EasyWeChat::miniProgram();
        $info = $wechat->auth->session($code);
        if (isset($info['errcode']) and isset($info['errmsg'])) {
            abort($info['errcode'], $info['errmsg']);
        }
        $userInfo = $userData::where(['openid' => $info['openid']])->first();
        if (empty($userInfo)) {
            $userData->uid = $uid;  //邀请用户
            $userData->access_token = md5(uniqid());
            $userData->openid = $info['openid'];
            $userData->session_key = $info['session_key'];
            $userData->login_at = time();
            $userData->unionid = isset($info['unionid']) ? $info['unionid'] : '';
            $userData->save();
            if ( !empty($mid)) {
                $dataInfo['updated_at'] = date('Y-m-d H:i:s', time());
                $minaInfo->where("id", $mid)->update($dataInfo);
            }
            if ($uid) {
                self::share($uid,$userData['id']);
            }
        } else {
            $change['session_key'] = $info['session_key'];
            $change['login_at'] = time();
            $userData::where(['openid' => $info['openid']])
                ->update($change);
        }
        $return['access_token'] = !empty($userInfo) ? $userInfo['access_token'] : $userData->access_token;
        $return['uid'] = empty($userInfo) ? $userData->id : $userInfo['id'];
        CacheService::setCache(CacheService::userToken($return['uid']), $return['access_token']);
        return $return;
    }

    public static function updateUserInfo(){

    }
    
    /**
     * 邀请用户增加邀请合作次数
     * @param int $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public static function share($uid,$init_uid){
        //$user = $request->user;
        //$inUserData = UserService::getUserById($request->get('in_uid'),'access_token');
        /* if(empty($inUserData) || md5($request->get('in_uid').$user['id'].'exchange') != $request->get('string')){
         Log::info('用户'.$request->get('in_uid').'分享校验失败');
         return $this->error(400, '分享失败');
         } */
        //$res = UserService::getUserById($user['id'],'created_at');
        //逻辑是只有是从未用过小程序的才是新用户，当被邀请用户点进时，自动注册
        /* if(time()>(strtotime($res['created_at'])+15)){
         return $this->error(400,'分享失败');
         } */
        /* $add_time = date('Y-m-d',time());
        $check = Shares::where(['uid'=>$request->get('in_uid'),'group_id'=>$user['id']])->select('id','add_time')->orderBy('id','desc')->first();
        if(!empty($check) && date('Y-m-d',$check['add_time']) == $add_time){
            return $this->error(400, '分享失败');
        } */
        if($uid == 0 || empty($uid)){
            return true;
        }
        $check = Shares::where(['uid'=>$uid,'group_id'=>$init_uid])->select('id','add_time')->orderBy('id','desc')->first();
        if(!empty($check)){
            return true;
        }
        /*$minaInfo = new MinaInfo();
        $data['updated_at'] = date('Y-m-d H:i:s',time());
        $minaData = MinaInfoService::getMinaByCondition('uid',$uid);
        if($minaData){
            $minaInfo->where("id",$minaData->id)->update($data);
        }*/
        $add_share_times = User::where("id", $uid)->increment('remain_apply_count');
        $result = Shares::createShare($uid, $init_uid);
        return true;
    }

    /**
     * @param $position
     * @param $advert
     * @return array
     */
    public static function getAdvertList($position, $advert){
        $list = $advert->select('name','mark','image','app_id','app_path')->where(['position'=>$position,'status'=>Status::ADVERT_PUT_ON])->get()->toArray();
        if(empty($list)){
            return [];
        }else{
            $path = config('minainfo.DEFAULT_QINIU_PATH');
            foreach ($list as $k => $v){
                $list[$k]['image'] = $path.$v['image'];
            }
            return $list;
        }
    }
    
}




