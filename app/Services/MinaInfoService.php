<?php


namespace App\Services;


use App\Models\ComplainList;
use App\Models\MinaInfo;
use App\Models\MinaCategory;
use App\Models\User;
use App\Models\MinaCrowdRatio;
use App\Utils\ArrayUtil;
use App\Utils\PageUtil;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ServiceException;
use App\Models\TopLine;
use App\Http\Models\Status;
use App\Models\MinaLike;
use App\Models\InviteInfo;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\Log;
use App\Utils\MiniMessageUtil;
class MinaInfoService
{

    /**
     * 查询小程序信息
     * @param string $field
     * @param string $condition
     * @return array
     */
    public static function getMinaByCondition($field,$condition){
        $MinaInfo = new MinaInfo();
        return $MinaInfo::where($field,$condition)->where(['audit_type'=>1])->orderBy('id','desc')->first();
    }
    
    /**
     * 查询点赞状态
     * @param int $uid
     * @param int $mid
     * @return number
     */
    public static function checkLikeOrNot($uid,$mid){
        $minaLike = new MinaLike();
        return empty($minaLike::where(['uid'=>$uid,'mid'=>$mid])->first()) ? 0 : 1;
    }
    
    /**
     * 根据id查询小程序类目
     * @param int $id
     * @param string $param
     * @return array
     */
    public static function getMinaType($id = '',$param = ''){
        if($param){
             $data = MinaCategory::select($param)
                ->where("id",$id)
                ->first();
                return empty($data)?[]:$data->toArray();
        }
        if($id){
            return MinaCategory::where("id",$id)->first();
        }
        $cacheKey = CacheService::MinaTypeKey();
        $data = cacheservice::getCache($cacheKey);
        if($data){
            return $data;
        }else{
            $data = MinaCategory::select(['id','name'])->get()->toArray();
            CacheService::setCache($cacheKey, $data);
            return $data;
        }
    }

    public static function getTopLine(){
        $topLineCacheKey = CacheService::topLineKey();
        $topLineCache = CacheService::getCache($topLineCacheKey);
        if ( !empty($topLineCache)) {
            return $topLineCache;
        }
        $limit = MinaInfoService::getTopInfo(Status::TOP_LINE_SIZE);
        foreach ($limit as $k => $v) {
            if (strlen($v['remark']) > Status::TOP_LINE_MAX_WORD) {
                $limit[ $k ]['remark'] = mb_substr($v['remark'],0,Status::TOP_LINE_MAX_WORD,'utf-8');
            }
        }
        CacheService::setCache($topLineCacheKey, $limit, 1);
        return $limit;
    }
    
    /**
     * 发布小程序信息
     * @param array $request
     * [
     *    'img'=>'http://img.jpg',
     *    'name'=>'小程序',
     *    'cid'=>'1',
     *    'con_min'=>'2',
     *    'con_max'=>'5',
     *    'exc_condition'=>'1',
     *    'mina_remark'=>'备注',
     *    'mid'=>'1',
     *    'user_name'=>'kangkang',
     *    'company'=>'123',
     *    'phone'=>'15815879811',
     *    'wechat'=>'123'
     * ]
     * @param array $userInfo
     * [
     *    'id'=>'1'
     * ]
     * @return 
     */
    public static function createMinaInfo($request,$userInfo){
        $info = MinaInfoService::getMinaByCondition('name',$request["name"]);
        if(($info && empty($request['mid']))){
            $json_msg['msg'] = Status::HAVE_THIS_MINA_IN_HALL_MSG;
            $json_msg['data']['id'] = $info['id'];
            $message = json_encode($json_msg,JSON_UNESCAPED_UNICODE);
            throw new ServiceException($message,Status::HAVE_THIS_MINA_IN_HALL_CODE);
        }else if($info && ($request['mid'] != $info['id'])){
            throw new ServiceException("换量大厅已有该小程序");
        }
        if(empty(MinaInfoService::getMinaType($request["cid"]))){
            throw new ServiceException("类目有误");
        }
        $minaInfo = new MinaInfo();
        $minaInfo->uid = $userInfo['id'];
        $minaInfo->img = $request['img'];
        $minaInfo->name = $request['name'];
        $minaInfo->cid = $request['cid'];
        $minaInfo->con_min = $request['con_min'];
        $minaInfo->con_max = $request['con_max'];
        $minaInfo->exc_condition = is_array($request['exc_condition']) ? implode(',',$request['exc_condition']) : $request['exc_condition'];
        $minaInfo->region = empty($request['region']) ? '' : implode(',',$request['region']);
        //$minaInfo->region = is_array($request['region']) ? implode(',',$request['region']) : $request['region'];
        $minaInfo->code_img = empty($request['code_img']) ? '' : $request['code_img'];
        $minaInfo->mina_remark = empty($request['mina_remark']) ? '' : $request['mina_remark'];
        DB::beginTransaction();
        try {
            if(empty($request['mid'])){
                if(!empty($request['updated_at_true'])){
                    $minaInfo->updated_at = date('Y-m-d h:i:s',1538890422);
                }
                $res = $minaInfo->save();
                $request['mina_code_img'] = self::getMinaQrCode($minaInfo->id);
            }else{
                $res = true;
                //判断是否本人
                $mina_info = $minaInfo::select('updated_at','id','top_age_ratio_name','top_sex_ratio_name','top_mobile_ratio_name')->where(['id'=>$request['mid'],'uid'=>$userInfo['id']])->get()->toArray();
                if(!$mina_info){
                    throw new ServiceException('更新失败');
                }
                $update_mina_info = $minaInfo->toArray();
                $update_mina_info['updated_at'] = $mina_info[0]['updated_at'];
                $minaInfo::where(['id'=>$request['mid'],'uid'=>$userInfo['id']])->update($update_mina_info);
                $request['mina_code_img'] = $minaInfo::where(['id'=>$request['mid'],'uid'=>$userInfo['id']])->value('mina_code_image');
                //清除掉原有信息列表记录
                MinaCrowdRatio::where("mid",$request['mid'])->delete();
            }
            if(!$res){
                throw new ServiceException('换量信息保存失败');
            }
            $crowd_type = Status::$crowd_type;
            $question = config('minainfo.CROWD_QUESTION');
            $minaCrowdRatioInfo = [];
            $minaCrowdRatioInfoList = [];
            //把信息一项一项发出来的
            foreach ($request as $k => $v){
                if(strstr($k,'age') || strstr($k, 'sex') || strstr($k,'mobile')){
                    $kArray = explode('_',$k);
                    $minaCrowdRatioInfo['mid'] = empty($minaInfo['id']) ? $request['mid'] : $minaInfo['id'];
                    $minaCrowdRatioInfo['ratio_type'] = $crowd_type[$kArray['0']];
                    $minaCrowdRatioInfo['ratio_name'] = $question[$kArray['0']][$kArray['1']];
                    $minaCrowdRatioInfo['ratio_num'] = $v;
                    $minaCrowdRatioInfoList[] = $minaCrowdRatioInfo;
                }
            }
            $info = DB::table("mina_crowd_ratios")->insert($minaCrowdRatioInfoList);
            if(!$info){
                throw new ServiceException('换量基本信息保存失败');
            }
            if(empty($request['mid'])){
                self::mina_top_ration_name([0=>['id'=>$minaInfo->id,'top_age_ratio_name'=>'','top_sex_ratio_name'=>'','top_mobile_ratio_name'=>'','updated_at'=>$minaInfo->updated_at]]);
            }else{
                self::mina_top_ration_name($mina_info);
            }
            //修改用户信息
            $userModel = new User();
            $userList = [
                'user_name'=>$request["user_name"],
                'company' => $request["company"],
                'phone' => $request["phone"],
                'wechat' => $request["wechat"]
            ];
            $res3 = $userModel::where('id', $userInfo['id'])->update($userList);
            if(!$res3){
                Log::error('res3:'.$res3);
                throw new ServiceException('用户信息更新失败');
            }
            DB::commit();
            $request = self::_pushForMinaCache($request, $minaInfo, $userInfo);
            return $request;
        }catch(\Exception $e){
            Log::error(__METHOD__.$e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 同步换量信息用户比例最高值
     *
     * @param $mina_ids = [
     *      "id" => 22"                                 换量信息id
     *      "top_age_ratio_name" => "25-29岁"           年龄比例最大值
     *      "top_sex_ratio_name" => "女"                性别比例
     *      "top_mobile_ratio_name" => "Android"        机型比例
     *      "updated_at" => "2018-10-29 16:07:47"       修改时间
    *       ]
     * @return int
     */
    public static function mina_top_ration_name($mina_ids){
        $change_num = 0;
        foreach ($mina_ids as $k => $v){
            $info = MinaInfoService::topMinaType($v['id'],1,1,1);
            $updateData = [];
            $change = false;
            if(!empty($info['sex']) && ($info['sex'][0]['ratio_name'] != $v['top_sex_ratio_name'])){
                $updateData['top_sex_ratio_name'] = $info['sex'][0]['ratio_name'];
                $change = true;
            }
            if(!empty($info['age']) && ($info['age'][0]['ratio_name'] != $v['top_age_ratio_name'])){
                $updateData['top_age_ratio_name'] = $info['age'][0]['ratio_name'];
                $change = true;
            }
            if(!empty($info['mobile']) && ($info['mobile'][0]['ratio_name'] != $v['top_mobile_ratio_name'])){
                $updateData['top_mobile_ratio_name'] = $info['mobile'][0]['ratio_name'];
                $change = true;
            }
            if($change){
                $updateData['updated_at'] = $v['updated_at'];
                $minaInfo = new MinaInfo();
                if($minaInfo->where('id',$v['id'])->update($updateData)){
                    $change_num += 1;
                }
            }
        }
        return $change_num;
    }
    
    /**
     * 将换量信息存储到缓存中
     * @param array $request
     * @param array $minaInfo
     * @param array $userInfo
     * @return array
     */
    private static function _pushForMinaCache($request,$minaInfo,$userInfo){
        $request['id'] = empty($minaInfo['id']) ? $request['mid'] : $minaInfo['id'];
        $request['uid'] = $userInfo['id'];
        $minaType = self::getMinaType($request['cid'],'name');
        $request['categorie_name'] =  empty($minaType) ? '' : $minaType['name'];
        $request['userInfo'] = [
            'avatar' => UserService::getUserById($userInfo['id'],'avatar')['avatar'],
            'user_name' => $request['user_name'],
            'company' => $request['company'],
            'phone' => $request['phone'],
            'wechat'=> $request['wechat']
        ];
        $exc_condition = is_array($request['exc_condition']) ? implode(',',$request['exc_condition']) : $request['exc_condition'];
        $request['exc_condition_name'] =  self::returnExcCondition($exc_condition,Status::$exc_condition);
        $request['sex1'] = $request['sex_1'];
        $request['sex2'] = $request['sex_2'];
        $request['age1'] = $request['age_1'];
        $request['age2'] = $request['age_2'];
        $request['age3'] = $request['age_3'];
        $request['age4'] = $request['age_4'];
        $request['age5'] = $request['age_5'];
        $request['age6'] = $request['age_6'];
        $request['mina_list'] = MinaInfoService::getMinaType();
        foreach (Status::$exc_condition as $k => $v){
            $exc['id'] = $k;
            $exc['name'] = $v;
            $exc_data[] = $exc;
        }
        $request['exc_list'] = $exc_data;
        //换量小程序二维码
        //$code_image = 'https://image.lingyiliebian.com/res_auction/2018102/7175035/0000007/210ddc7/1onx1NJbXHRqoFElE1FIo2lAazB0SKmm0DiXXOOa.jpeg';
        $request['mina_code_img'] = empty($request['mina_code_img']) ? self::getMinaQrCode($request['id']) : $request['mina_code_img'];
        $cacheKey = CacheService::minaInfoForId($request['id']);
        CacheService::setCache($cacheKey, $request);
        return $request;
    }
    
    /**
     * 获取换量小程序列表
     * @param int $limit
     */
    public static function getMinaInfoList($page = 1,$pageSize = 10,$uid = '',$request,$type = ''){
        $MinaInfo = new MinaInfo();
        $User = new User();
        $info = $MinaInfo::getMinaList($page,$pageSize,$uid,$request,$type);
        $inviteArray = $info['invite'];
        if($info['list']){
            foreach ($info['list'] as $k => $v){
                $info['list'][$k]['complain_info'] = isset($v['com_type']) ? Status::$complaint_type[$v['com_type']] : '';
                $info['list'][$k]['label'] = self::returnExcCondition($v['label'],Status::$mina_label);
                $info['list'][$k]['invite_status'] = self::checkInviteForArray($v['id'],$inviteArray);
                $info['list'][$k]['con_min'] = self::roundForNum($v['con_min']);
                $info['list'][$k]['con_max'] = self::roundForNum($v['con_max']);
                $minaType = self::getMinaType($v['cid'],'name');
                $info['list'][$k]['categorie_name'] =  empty($minaType) ? '' : $minaType['name'];
                $tabData = self::topMinaType($v['id'],1,1,1,'ratio_num');
                $info['list'][$k]['sexMax'] = $tabData['sex'];
                $info['list'][$k]['age'] = $tabData['age'];
            }
        }
        return $info;
    }

    /**
     * 检查用户小程序
     * @param $mid
     * @param $inviteArray
     * @return int
     */
    public static function checkInviteForArray($mid,$inviteArray){
        if(empty($inviteArray)){
            return 0;
        }
        foreach ($inviteArray as $k => $v){
            if($v['mina_id'] == $mid){
                return $v['status'];
            }
        }
        return 0;
    }

    /**
     * 对万取整
     * @param $num
     * @return int|string
     */
    public static function roundForNum($num){
        if(!is_numeric($num)){
            return 0;
        }
        return $num>=10000 ? round($num/10000,1).'万' : $num;
    }
    
    public static function getTopInfo($limit = 20){
        $topLine = new TopLine();
        $data = $topLine::where('status',Status::TOP_NEWS_SHOW)->select([
            'id',
            'remark',
        ])->orderBy('updated_at', 'desc')->limit($limit)->get()->toArray();
        return $data;
    }
    
    /**
     * 小程序比例信息
     * @param unknown $mid
     * @return array
     */
    public static function topMinaType($mid,$sexLimit = 1,$ageLimit = 2,$mobileLimit = 1,$orderBy = 'ratio_num',$sort = 'desc'){
        $MinaCrowdRatio = new MinaCrowdRatio();
        $sex = $MinaCrowdRatio::select('ratio_name','ratio_num')
                    ->where("mid",$mid)
                    ->where("ratio_type",2)
                    ->orderBy($orderBy,$sort)
                    ->limit($sexLimit)->get()->toArray();
        $age = $MinaCrowdRatio::select('ratio_name','ratio_num')
                    ->where("mid",$mid)
                    ->where("ratio_type",1)
                    ->orderBy($orderBy,$sort)
                    ->limit($ageLimit)->get()->toArray();
        $mobile = $MinaCrowdRatio::select('ratio_name','ratio_num')
                    ->where("mid",$mid)
                    ->where("ratio_type",3)
                    ->orderBy($orderBy,$sort)
                    ->limit($mobileLimit)->get()->toArray();
        $reutrnInfo['sex'] = $sex;
        $reutrnInfo['age'] = $age;
        $reutrnInfo['mobile'] = $mobile;
        return $reutrnInfo;
    }
    
    public static function minaDetails($mid,$user,$is_update = false,$invite_id = ''){
        try{
            /*$cacheKey = CacheService::minaInfoForId($mid);
            $cacheData = CacheService::getCache($cacheKey);
            if(!empty($cacheData)){
                //不是自己的信息需要隐藏掉
                if($user['id'] != $cacheData['uid']){
                    $cacheData['userInfo']['company'] = InviteInfoService::str_replaces($cacheData['userInfo']['company'], 2, 4);
                    $cacheData['userInfo']['wechat'] = InviteInfoService::str_replaces($cacheData['userInfo']['wechat'], 2, 4);
                }
                //return $cacheData;
            }*/
            $result = self::getMinaByCondition('id', $mid);

            $minType = self::getMinaType($result['cid'],'name');
            $result['categorie_name'] =  empty($minType) ? '' : $minType['name'];

            $minaInviteInfo = InviteInfo::where(['uid'=>$user['id'],'mina_id'=>$mid])->orderBy('id','desc')->value('status');
            //当页面是从合作记录里接受邀请列表进来时，需要额外对状态判断
            if(!empty($invite_id)){
                $invite_type = InviteInfo::where('id',$invite_id)->value('status');
                $minaInviteInfo = $invite_type == 1 ? 4 : $minaInviteInfo;
            }
            $result['invite_id'] = $invite_id;
            $result['invite_status'] = empty($minaInviteInfo) ? 0 : $minaInviteInfo;

            if($result['uid'] == $user['id']){
                $userInfo['avatar'] = $user['avatar'];
                $userInfo['user_name'] = $user['user_name'];
                $userInfo['company'] = $user['company'];
                $userInfo['phone'] = $user['phone'];
                $userInfo['wechat'] = $user['wechat'];
            }else{
                $userInfo = UserService::getUserById($result['uid'],['avatar','user_name','company','phone','wechat']);
                //合作成功状态不隐藏信息
                if($result['invite_status'] != 2){
                    $userInfo['company'] = InviteInfoService::str_replaces($userInfo['company'], 2, 4);
                    $userInfo['wechat'] = InviteInfoService::str_replaces($userInfo['wechat'], 2, 4);
                }
            }
            //除了更新时拿的详情信息，其他时候都要改为万
            if(!$is_update){
                $result['con_min'] = self::roundForNum($result['con_min']);
                $result['con_max'] = self::roundForNum($result['con_max']);
            }
            $result['userInfo'] = $userInfo;
            $result['exc_condition_name'] = self::returnExcCondition($result['exc_condition'],Status::$exc_condition);
            $result['label'] = self::returnExcCondition($result['label'],Status::$mina_label);
            $nextAndTheLastMinaId = self::returnLastNextMinaId($mid,$user['id']);
            $result['the_last_mina_id'] = $nextAndTheLastMinaId['the_last'];
            $result['next_mina_id'] = $nextAndTheLastMinaId['next'];
            $tabData = self::topMinaType($mid,count(Status::$crowd['sex']),count(Status::$crowd['age']),count(Status::$crowd['mobile']),'id','asc');
            $result['sex_max'] = $tabData['sex'];
            $result['age'] = $tabData['age'];
            $result['mobile'] = $tabData['mobile'];
            $result['region_name'] = self::returnExcCondition($result['region'],Status::$region);
            foreach ($result['sex_max'] as $k => $v){
                $key = 'sex'.($k+1);
                $result[$key] = $v['ratio_num'];
            }
            foreach ($result['age'] as $k => $v){
                $key = 'age'.($k+1);
                $result[$key] = $v['ratio_num'];
            }
            if($result['mobile']){
                foreach ($result['mobile'] as $k => $v){
                    $key = 'mobile'.($k+1);
                    $result[$key] = $v['ratio_num'];
                }
            }

            $result['mina_list'] = MinaInfoService::getMinaType();
            $result['mina_remark'] = is_null($result['mina_remark']) ? '' : $result['mina_remark'];
            foreach (Status::$exc_condition as $k => $v){
                $exc['id'] = $k;
                $exc['name'] = $v;
                $exc['checked'] = false;
                $exc_data[] = $exc;
            }
            $result['exc_list'] = $exc_data;
            foreach (Status::$region as $k => $v){
                $e['id'] = $k;
                $e['name'] = $v;
                $e['checked'] = false;
                $e_data[] = $e;
            }
            $result['region_list'] = $e_data;
            if(empty($result['mina_code_image'])){
                $result['mina_code_img'] = self::getMinaQrCode($mid);
            }else{
                $result['mina_code_img'] = $result['mina_code_image'];
            }
            //$result['mina_code_img'] = 'https://image.lingyiliebian.com/res_auction/2018102/7175035/0000007/210ddc7/1onx1NJbXHRqoFElE1FIo2lAazB0SKmm0DiXXOOa.jpeg';
            //CacheService::setCache($cacheKey, $result);
            return $result;
        }catch(\Exception $e){
            Log::error($e->getMessage());
            throw new ServiceException('获取详情失败');
        }
    }

    /**
     * 获取上一个和下一个换量信息id 排除自己的,未审核的,已邀请的
     * @param $mid
     * @param $uid
     * @return mixed
     */
    public static function returnLastNextMinaId($mid,$uid){
        $theLast = $next = 0;
        $catchKey = CacheService::userSearchMinaIdList($uid);
        $query = CacheService::getCache($catchKey);
        if(empty($query)){
            //已邀请的mina_id
            $invite_mina_id = InviteInfo::where(['uid'=>$uid])
                                        ->where('status',Status::INVITATIONS_AGREE)
                                        ->pluck('mina_id')->toArray();
            $query = MinaInfo::where('audit_type', Status::MINA_AUDIT_TRUE)
                             ->where('uid','!=',$uid)
                             ->when(!empty($invite_mina_id), function ($query) use ($invite_mina_id) {
                                 $query->wherenotin('id', $invite_mina_id);
                             })
                             ->pluck('id')->toArray();
        }
        if(!empty($query)){
            $midPosition = array_search($mid,$query);
            $theLast = $midPosition == 0 ? 0 : $query[$midPosition-1];
            $next = $midPosition == (count($query)-1) ? 0 : $query[$midPosition+1];
        }
        $return['the_last'] = $theLast;
        $return['next'] = $next;
        return $return;
    }

    public static function pushComplain($uid, $mark, $type, $mid, $imgs, ComplainList $comPlainList){
        $where['uid'] = $uid;
        //$where['complain_remark'] = $mark;
        //$where['com_type'] = $type;
        $where['mina_id'] = $mid;

        $listInfo = $comPlainList->select('id','img1','img2','img3','complain_remark','com_type','handle_type')->where($where)->first();
        $listInfo = !empty($listInfo) ? $listInfo->toArray() : $listInfo;
        if(!empty($listInfo) && $listInfo['handle_type'] == 2){
            throw new ServiceException('该投诉已受理，不能再修改噢');
        }
        if(!empty($listInfo) && !empty($imgs) && $listInfo['complain_remark'] == $mark && $listInfo['com_type'] == $type){
            $imageArray = $listInfo;
            $pushImage = $imgs;
            foreach ($imageArray as $k => $v){
                if(strstr($v,'http')){
                    $comImage[] = $v;
                }
            }
            foreach ($pushImage as $k => $v){
                if(!empty($v)){
                    $pushImageArray[] = $v;
                }
            }
            sort($comImage); sort($pushImageArray);
            if($comImage == $pushImageArray){
                throw new ServiceException('您已提交相同投诉');
            }
        }
        $comPlainList->uid = $uid;
        $comPlainList->mina_id = $mid;
        $comPlainList->com_type = $type;
        $comPlainList->complain_remark = $mark;
        if($imgs){
            foreach ($imgs as $k => $v){
                if(!empty($v)){
                    $imgValue = $k+1;
                    $img = 'img'.$imgValue;
                    $comPlainList->$img = $v;
                }
            }
        }
        DB::beginTransaction();
        try {
            $comPlainArray = $comPlainList->toArray();
            if($listInfo){
                //先保证图片路径清空.防止删图片的情况
                $cleanImg = ['img1'=>0,'img2'=>0,'img3'=>0];
                ComplainList::where('id',$listInfo['id'])->update($cleanImg);
                ComplainList::where('id',$listInfo['id'])->update($comPlainArray);
                $data = true;       //只有时间更改的情况下,update返回0，之后再看看  TODO
                /*if(ComplainList::where('id',$listInfo['id'])->update($comPlainArray)){
                    $data = true;
                }*/
            }else{
                $data = $comPlainList->save();
            }
            if(!$data){
                throw new ServiceException('投诉提交失败');
            }
            //新增的投诉需要添加投诉次数(改成后台审核过后才加次数)
            /*if(!$listInfo){
                $result = MinaInfo::where('id',$mid)->increment('complain_count');
                if(!$result){
                    throw new ServiceException('投诉次数更新失败');
                }
            }*/
            DB::commit();
        }catch(\Exception $e){
            Log::error(__METHOD__.'pushComplain:'.$e->getMessage());
            DB::rollBack();
            throw $e;
        }
        return $comPlainArray;
    }

    /**
     * 查询投诉情况
     * @param              $uid
     * @param              $mid
     * @param ComplainList $complainList
     * @return array
     */
    public static function complainEnquiryInfo($uid, $mid, ComplainList $complainList){
        $where['uid'] = $uid;
        $where['mina_id'] = $mid;
        $listInfo = $complainList
            ->select('com_type','complain_remark','img1','img2','img3','handle_type')
            ->where($where)->first();
        if(!empty($listInfo)){
            $listInfo = $listInfo->toArray();
            $imgArray = [];
            foreach ($listInfo as $k => $v){
                if(strstr($k, 'img') && $v){
                    $imgArray[] = $v;
                }
            }
            $listInfo['img_array'] = $imgArray;
        }
        $return = empty($listInfo) ? [] : $listInfo;
        return $return;
    }
    
    /**
     * 获取换量信息二维码
     */
    public static function getMinaQrCode($mid)
    {
        try{
            $array['mina_id'] = $mid;
            $res = MiniMessageUtil::getQrCodeToQiNiu($array);
            if($res){
                $minaInfo = new MinaInfo();
                $updated_at = $minaInfo::where('id',$mid)->where(['audit_type'=>1])->value('updated_at');
                $data['mina_code_image'] = $res;
                $data['updated_at'] = $updated_at;
                $minaInfo->where('id',$mid)->update($data);
                return $res;
            }else{
                Log::info('生成二维码不成功:'.$res);
            }
            //默认二维码
            return 'https://image.lingyiliebian.com/res_auction/2018102/7175035/0000007/210ddc7/1onx1NJbXHRqoFElE1FIo2lAazB0SKmm0DiXXOOa.jpeg';
        }catch(\Exception $e){
            Log::error("生成二维码失败".$e->getMessage());
            throw new ServiceException("生成二维码失败".$e->getMessage());
        }
    }
    
    public static function likeOrNot($uid,$mid){
        try{
            $mina_info = self::getMinaByCondition("id", $mid);
            if(empty($mina_info)){
                throw new ServiceException('该换量信息不存在，请继续浏览其他项目');
            }
            $likeModel = new MinaLike();
            $like_info = $likeModel::select('id','uid','mid')
                                ->where(['mid'=>$mid,'uid'=>$uid])
                                ->first();
            if(empty($like_info)){
                $likeModel->uid = $uid;
                $likeModel->mid = $mid;
                $likeModel->save();
                $data['agree_type'] = 1;
            }else{
                $likeModel::where('id',$like_info['id'])->delete();
                $data['agree_type'] = 0;
            }
            return $data;
        }catch(\Exception $e){
            throw new ServiceException('点赞失败,请稍后重试');
        }
    }

    /**
     * 返回数据库里1,2,3那样的数据
     * @param $exc      1,2,3
     * @return array
     */
    public static function returnExcCondition($exc = '',$info = []){
        if(empty($exc) || empty($info)){
            return [];
        }
        $return = [];
        if(strstr($exc,',')){
            foreach (explode(',',$exc) as $k => $v){
                $return[] = $info[$v];
            }
        }else{
            $return[] = $info[$exc];
        }
        return $return;
    }
}




