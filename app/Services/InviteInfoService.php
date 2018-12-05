<?php


namespace App\Services;


use App\Exceptions\ServiceException;
use App\Models\InviteInfo;
use App\Models\MinaInfo;
use App\Models\TemplateMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Models\Status;
use App\Models\TopLine;
use Illuminate\Support\Facades\Log;

class InviteInfoService
{
    /**
     * 邀请合作
     * @param array $request
     */
    public static function invitingCooperation(Request $request,$user){
        $userMina = MinaInfoService::getMinaByCondition('uid',$user['id']);
        if(empty($userMina)){
            throw new ServiceException(Status::RETURN_NOT_CREATE_MINA_IMAGE,Status::RETURN_NOT_CREATE_MINA_CODE);
        }
        if(InviteInfo::select('id')->where(['uid'=>$user['id'],'mina_id'=>$request->get('mid')])->whereIn('status',['1','2'])->first()){
            throw new ServiceException('您已经发出了邀请或已合作');
        }
        $remain_apply_count = UserService::getUserById($user['id'],['remain_apply_count'])['remain_apply_count'];
        if($remain_apply_count<=0){
            throw new ServiceException(Status::RETURN_INVITE_LIMIT_IMAGE,Status::RETURN_INVITE_LIMIT_CODE);
        }
        $request = $request->all();
        $mina = MinaInfoService::getMinaByCondition('id', $request['mid']);
        if (!$mina) {
            throw new ServiceException('该换量信息不存在，请继续浏览其他项目');
        }
        if($mina['uid'] == $user['id']){
            throw new ServiceException('自己不能与自己合作哦');
        }
        $userModel = new User();
        $userStatus = $userModel->where('id',$mina['uid'])->value('status');
        if($userStatus == Status::USER_DISABLED){
            throw new ServiceException(Status::USER_OTHER_DISABLED_NOTICE);
        }
        DB::beginTransaction();
        try{
            $result = InviteInfo::createInvite($mina,$user);
            if(!$result){
                throw new ServiceException('合作信息更新失败');
            }
            $userInfo = new User();
            $data['apply_count'] = $user['apply_count']+1;
            $data['remain_apply_count'] = $user['remain_apply_count']-1;
            $res = $userInfo->where("id",$user['id'])->update($data);
            if(!$res){
                throw new ServiceException('用户信息更新失败');
            }
            DB::commit();
            //多个邀请记录，展示最近一条
            $user_mina_info = MinaInfoService::getMinaByCondition('uid',$user['id']);
            $cacheKey = CacheService::userInviteCacheKey($mina['uid'],'agree_or_not');
            $userInfo = UserService::getUserById($user['id'],['nick_name','company','avatar','wechat','phone']);
            $userInfo['wechat'] = self::str_replaces($userInfo['wechat'], 2, 4);
            $userInfo['phone'] = self::str_replaces($userInfo['phone'], 4, 4);
            $userInfo['inid'] = $result->id;
            $info['id'] = $user_mina_info['id'];
            $info['name'] = $user_mina_info['name'];
            $info['img'] = $user_mina_info['img'];
            $info['con_min'] = MinaInfoService::roundForNum($user_mina_info['con_min']);
            $info['con_max'] = MinaInfoService::roundForNum($user_mina_info['con_max']);
            $minaType = MinaInfoService::getMinaType($user_mina_info['cid'],'name');
            $info['categorie_name'] =  empty($minaType) ? '' : $minaType['name'];
            $tabData = MinaInfoService::topMinaType($user_mina_info['id'],1,1,1,'ratio_num');
            $info['sexMax'] = $tabData['sex'];
            $info['age'] = $tabData['age'];
            $cacheList['user_info'] = $userInfo;
            $cacheList['mina_info'] = $info;
            $cacheList['hello'] = self::helloForInvite($info['categorie_name'],$info['con_max'],$user_mina_info['exc_condition']);
            CacheService::setCache($cacheKey, $cacheList);
            //对方
            $cacheAddKey = CacheService::userAcceptInviteCountKey($mina['uid']);
            CacheService::setCache($cacheAddKey, empty(CacheService::getCache($cacheAddKey)) ? 1 : CacheService::getCache($cacheAddKey)+1);
            //发送模板消息
            $send = UserService::sendMessageForweChat($mina['uid'],$user,TemplateMessage::INVITE,TemplateMessage::INVITE_MSG);
            return true;
        }catch(\Exception $ex){
            DB::rollBack();
            Log::error(__METHOD__.$ex->getMessage());
            throw new ServiceException('操作失败，请稍后重试');
        }
    }

    public static function helloForInvite($categorie_name,$con_max,$exc_condition){
        $exc_condition = MinaInfoService::returnExcCondition($exc_condition,Status::$exc_condition);
        $exc_condition = count($exc_condition) == 1 ? $exc_condition[0] : implode('/',$exc_condition);
        return "Hi".$categorie_name."类小程序求换量:日导量".$con_max."、\n".$exc_condition."换";
    }

    /**
     * 同意或拒绝合作
     * @param Request $request
     * @param unknown $user
     * @throws ServiceException
     * @return string
     */
    public static function agreeCooperateOrNot(Request $request,$user){
        $request = $request->all();
        $inviteInfo = new InviteInfo();
        $res = $inviteInfo::where("id", $request['inid'])
                          ->where("b_uid", $user['id'])
                          ->where('status', 1)
                          ->first();
        if (!$res) {
            throw new ServiceException('该合作信息有误');
        }
        //type 1同意2拒绝
        if($request['type'] == 1){
            $type = 2;
            $invite_type = TemplateMessage::AGREE_INVITE;
            $invite_msg = TemplateMessage::AGREE_INVITE_MSG;
        }else{
            $type = 3;
            $invite_type = TemplateMessage::REFUSE_INVITE;
            $invite_msg = TemplateMessage::REFUSE_INVITE_MSG;
        }
        //$type = $request['type'] == 1 ? 2 : 3;
        $result = $inviteInfo->where(['id'=>$request['inid']])->update(['status'=>$type]);
        if(!$result){
            throw new ServiceException('操作失败，请稍后重试');
        }
        if($request['type'] == 1){
            $cacheKey = CacheService::userInviteCacheKey($res['uid'],'agree');
            $user_mina_info = MinaInfoService::getMinaByCondition('id',$res['mina_id']);
            $userInfo = UserService::getUserById($user['id'],['nick_name','company','avatar','wechat','phone']);
            $userInfo['inid'] = $request['inid'];
            $info['id'] = $user_mina_info['id'];
            $info['name'] = $user_mina_info['name'];
            $info['img'] = $user_mina_info['img'];
            $info['con_min'] = MinaInfoService::roundForNum($user_mina_info['con_min']);
            $info['con_max'] = MinaInfoService::roundForNum($user_mina_info['con_max']);
            $minaType = MinaInfoService::getMinaType($user_mina_info['cid'],'name');
            $info['categorie_name'] =  empty($minaType) ? '' : $minaType['name'];
            $tabData = MinaInfoService::topMinaType($user_mina_info['id'],1,1,1,'ratio_num');
            $info['sexMax'] = $tabData['sex'];
            $info['age'] = $tabData['age'];
            $cacheList['user_info'] = $userInfo;
            $cacheList['mina_info'] = $info;
            CacheService::setCache($cacheKey, $cacheList);
            //生成合作信息头条轮播
            $inviterInfo = UserService::getUserById($res['uid'],['id','nick_name','company','avatar','wechat','phone']);       //邀请者信息
            TopLine::createTopLine($user_mina_info, $inviterInfo);
            $inviterUser['user_info'] = $inviterInfo;
        }else{
            $inviterUser = true;
        }
        //发送模板消息
        //$invite_type = $request['type'] == 1 ? TemplateMessage::AGREE_INVITE : TemplateMessage::REFUSE_INVITE;
        UserService::sendMessageForweChat($res['uid'],$user,$invite_type,$invite_msg);
        //对方
        $cacheLaunchKey = CacheService::userLaunchInviteCountKey($res['uid']);
        CacheService::setCache($cacheLaunchKey, empty(CacheService::getCache($cacheLaunchKey)) ? 1 : CacheService::getCache($cacheLaunchKey)+1);
        //清除掉用户对方缓存，保证在相应的弹窗信息不会出现
        $cacheKey = CacheService::userInviteCacheKey($user['id'],'agree_or_not');
        CacheService::clearCache($cacheKey);
        return $inviterUser;
    }
    
    public static function getinviteList($page = 1, $pageSize = 12, $type = 1,$self){
        
        $inviteInfo = new InviteInfo();
        $user = new User();
        $map = $type == 1 ? ['invite_infos.uid'=>$self['id']] : ['invite_infos.b_uid'=>$self['id']];
        $list = $inviteInfo::select('invite_infos.id','invite_infos.status','invite_infos.mina_id','invite_infos.uid','invite_infos.b_uid',
                                    //'u.avatar','u.company','u.nick_name','u.wechat','u.phone',
                                    'm.name','m.cid','m.con_min','m.con_max','m.img','m.audit_type')
            //->join('users as u', 'invite_infos.uid', 'u.id')
            ->join('mina_infos as m', 'invite_infos.mina_id', 'm.id')
            ->orderBy('invite_infos.created_at', 'desc')
            ->where($map);
        
        $data['page'] = $page;
        $data['total'] = $list->count();
        $data['total_page'] = ceil($data['total']/$pageSize);
        $data['list'] = $list->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->toArray();
        if(empty($data['list'])){
            return $data;
        }
        foreach ($data['list'] as $k => $v){
            //产品老哥说接受邀请地方要改成邀请方的第一个换量信息,要去掉join但不想去掉，哎(后来被我晓之以情，动之以理说服了~)
            /*if($type == 2){
                $minaData = MinaInfo::select('id','name','cid','con_min','con_max','img')->where('uid',$v['uid'])->limit(1)->orderBy('id','asc')->first()->toArray();
                $v['con_min'] = $minaData['con_min'];
                $v['con_max'] = $minaData['con_max'];
                $v['cid'] = $minaData['cid'];
                $data['list'][$k]['name'] = $minaData['name'];
                $data['list'][$k]['img'] = $minaData['img'];
                $data['list'][$k]['mina_id'] = $minaData['id'];
                $v['mina_id'] = $minaData['id'];
            }*/
            /*$v['mina_id'] = $type == 1 ? $v['mina_id'] : MinaInfo::select('name','cid','con_min','con_max','img')
                                                                 ->where('uid',$v['uid'])->value('id');*/
            if($type == 2){
                //查看TA发布的信息id
                $inviter_mina_id = MinaInfo::where(['uid'=>$v['uid'],'audit_type'=>Status::MINA_AUDIT_TRUE])->orderBy('id','desc')->value('id');
                $data['list'][$k]['inviter_mina_id'] = empty($inviter_mina_id) ? '' : $inviter_mina_id;
            }
            $data['list'][$k]['con_min'] = MinaInfoService::roundForNum($v['con_min']);
            $data['list'][$k]['con_max'] = MinaInfoService::roundForNum($v['con_max']);
            $data['list'][$k]['agree_type'] = MinaInfoService::checkLikeOrNot($v['uid'], $v['mina_id']);
            $minaType = MinaInfoService::getMinaType($v['cid'],'name');
            $data['list'][$k]['categorie_name'] = empty($minaType) ? '' : $minaType['name'];
            $tabData = MinaInfoService::topMinaType($v['mina_id'],1,1,1,'ratio_num');
            $data['list'][$k]['sexMax'] = $tabData['sex'];
            $data['list'][$k]['age'] = $tabData['age'];
            //发起的邀请当对方未同意时不展示对方用户信息,其余情况查询出信息
            if($v['uid'] == $self['id'] && ($v['status'] == 1 || $v['status'] == 3)){
                $userInfo = [];
            }else{
                $id = $type == 1 ? $v['b_uid'] : $v['uid'];
                $userInfo = UserService::getUserById($id,['avatar','company','nick_name','wechat','phone']);
                //接受邀请中未同意的隐藏用户信息
                if($v['b_uid'] == $self['id'] && ($v['status'] == 1 || $v['status'] == 3)){
                    $userInfo['company'] = self::str_replaces($user['company'], 2, 4);
                    $userInfo['nick_name'] = self::str_replaces($userInfo['nick_name'], 1, 4);
                    $userInfo['phone'] = self::str_replaces($userInfo['phone'], 4, 4);
                    $userInfo['wechat'] = self::str_replaces($userInfo['wechat'],1,4);
                }
            }
            $data['list'][$k]['userinfo'] = $userInfo;
        }
        $data['mina_unsanction_notice'] = Status::MINA_UNSANCTION_NOTICE;
        //$data['others_mina_unsanction_notice'] = Status::OTHERS_MINA_UNSANCTION_NOTICE;
        //接受邀请未读红点
        $key = $type == 1 ? CacheService::userLaunchInviteCountKey($self['id']) : CacheService::userAcceptInviteCountKey($self['id']);
        $count = CacheService::getCache($key);
        if($count){
            $data['red_count'] = $count;
            CacheService::clearCache($key);
        }else{
            $data['red_count'] = 0;
        }
        //清除掉用户对方缓存，保证在相应的弹窗信息不会出现
        $cacheKey = CacheService::userInviteCacheKey($self['id'],'agree_or_not');
        CacheService::clearCache($cacheKey);
        
        return $data;
    }

    /**
     * 更新置顶
     * @param          $uid
     * @param          $mid
     * @param MinaInfo $minaInfo
     * @throws ServiceException
     */
    public static function updateReleaseMina($uid, $mid, MinaInfo $minaInfo){
        $catchKey = CacheService::userUpdateReleaseKey($uid);
        $catch = CacheService::getCache($catchKey);
        if($catch){
            throw new ServiceException('一天仅有一次置顶机会,邀请新朋友自动刷新置顶');
        }
        $mina = $minaInfo->where(['id' => $mid , 'uid'=>$uid])->first();
        if(empty($mina)){
            throw new ServiceException('请求有误');
        }
        $data['updated_at'] = date('Y-m-d H:i:s',time());
        $result = $minaInfo->where("id",$mid)->update($data);
        if(!$result){
            throw new ServiceException('网络有误,请稍后重试');
        }
        CacheService::setCache($catchKey,true,intval((strtotime(date('Y-m-d',time()))+86400 - time())/60));
        return $mina['name'];
    }
    
    /**
     * @param $str 字符串
     * @param $start 替换字符的开始位置
     * @param $len  替换字符的长度
     * @param $symbol 替换的字符  例如*、#等
     * @return string
     */
    public static function str_replaces($str, $start, $len, $symbol='*')
    {
        if(empty($str)){
            return '';
        }
        $end = $start + $len;
        $str1 = mb_substr($str, 0, $start);
        $str2 = mb_substr($str, $end);
        $symbol_num = '';
        for ($i = 0; $i < $len; $i++) {
            $symbol_num .= $symbol;
        }
        return $str1 . $symbol_num . $str2;
    }
    
}




