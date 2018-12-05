<?php

namespace App\Http\Controllers\Api;

use App\Http\Models\Status;
use App\Services\CacheService;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\MinaInfo;
use App\Services\MinaInfoService;
use App\Models\ComplainList;
use App\Models\User;
use App\Services\UserService;
use App\Utils\QiniuUtil;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\Log;

class MinaInfoController extends Controller
{
    private $minaInfoService;
    private $userService;
    
    public function __construct(MinaInfoService $minaInfoService,UserService $userService)
    {
        $this->minaInfoService = $minaInfoService;
        $this->userService = $userService;
    }
    
    /**
     * 头条轮播
     */
    public function topLine(){
        try {
            $limit = MinaInfoService::getTopLine();
            return $this->success(200, 'ok', $limit);
        } catch (\Exception $e) {
            Log::error('topLine:' . $e->getMessage());
            return $this->error(400, '系统繁忙');
        }
    }

    /**
     * 获取精准筛选条件
     */
    public function getScreeningConditions(){
        $data['mina_list'] = MinaInfoService::getMinaType();
        foreach (Status::$crowd_question_info as $k => $v){
            $exc['id'] = $k;
            $exc['name'] = $v;
            $exc['checked'] = false;
            $exc_data[] = $exc;
        }
        $data['mina_crowd'] = $exc_data;
        foreach (Status::$mobile_type as $k => $v){
            $ex['id'] = $k;
            $ex['name'] = $v;
            $ex['checked'] = false;
            $ex_data[] = $ex;
        }
        $data['mobile_type'] = $ex_data;
        foreach (Status::$region as $k => $v){
            $e['id'] = $k;
            $e['name'] = $v;
            $e['checked'] = false;
            $e_data[] = $e;
        }
        $data['region'] = $e_data;
        return $this->success(200, '获取成功', $data);
    }
    /**
     * 展示换量小程序列表
     * @param Request $request
     * @return array
     */
    public function showList(Request $request)
    {
        /*$rules = [
            'mina_ids' => 'array',
            'mina_crowd_ids' => 'array',
            'mobile_types' => 'array',
            'region' => 'array'
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()){
            return $this->error(400,'参数格式错误');
        }*/
        try{
            $page = $request->get('page', 1);
            $pageSize = $request->get('page_size', 10) > Status::MAX_PAGE_SIZE ? Status::MAX_PAGE_SIZE : $request->get('page_size', 10);
            $limit = $this->minaInfoService->getMinaInfoList($page,$pageSize,'',$request);
            return $this->success(200, '获取成功', $limit);
        }catch(\Exception $e){
            Log::error('showList:'.$e->getMessage());
            return $this->error(400, '系统繁忙');
        }
    }
    
    /**
     * 展示换量小程序详情
     * @param Request $request
     * @return array
     */
    public function show(Request $request)
    {
        $rules = [
            'id' => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $user = $request->user;
        	$is_update = $request->get('update',false);
        	$invite_id = $request->get('invite_id','');
        	$data = $this->minaInfoService::minaDetails($request->get('id'),$user,$is_update,$invite_id);
        	return $this->success(200, 'ok', $data);
        }catch(\Exception $e){
            Log::error('show:'.$e->getMessage());
            return $this->error(400, '系统繁忙');
        }
    }
    
    /**
     * 点击发布按钮展示信息
     */
    public function clickRelease(Request $request){
        //UserController::checkUserAuth($request->user['id']);
        $userInfo['company'] = empty($request->user['company']) ? '' : $request->user['company'];
        $userInfo['user_name'] = empty($request->user['user_name']) ? '' : $request->user['user_name'];
        $userInfo['phone'] = empty($request->user['phone']) ? '' : $request->user['phone'];
        $userInfo['wechat'] = empty($request->user['wechat']) ? '' : $request->user['wechat'];
        try{
            $data['mina_list'] = $this->minaInfoService::getMinaType();
            foreach (Status::$exc_condition as $k => $v){
                $exc['id'] = $k;
                $exc['name'] = $v;
                $exc['checked'] = false;
                $exc_data[] = $exc;
            }
            $data['exc_list'] = $exc_data;
            foreach (Status::$region as $k => $v){
                $ex['id'] = $k;
                $ex['name'] = $v;
                $ex['checked'] = false;
                $ex_data[] = $ex;
            }
            $data['region_list'] = $ex_data;
            foreach (Status::$crowd['age'] as $k => $v){
                $crowd['question'] = $k;
                $crowd['answer'] = $v;
                $crowd_data[] = $crowd;
            }
            $data['crowd_list']['age'] = $crowd_data;
            $crowd = $crowd_data = [];
            foreach (Status::$crowd['sex'] as $k => $v){
                $crowd['question'] = $k;
                $crowd['answer'] = $v;
                $crowd_data[] = $crowd;
            }
            $data['crowd_list']['sex'] = $crowd_data;
            $crowd = $crowd_data = [];
            foreach (Status::$crowd['mobile'] as $k => $v){
                $crowd['question'] = $k;
                $crowd['answer'] = $v;
                $crowd_data[] = $crowd;
            }
            $data['crowd_list']['mobile'] = $crowd_data;
            $data['user_info'] = $userInfo;
            $data['return_mid'] = empty($request->return_mid) ? '' : $request->return_mid;
            return $this->success(200, 'ok', $data);
        }catch(\Exception $e){
            Log::error('clickRelease:'.$e->getMessage());
            return $this->error(400, '系统繁忙');
        }
    }
    
    public function pushToken()
    {
        $key = substr(QiniuUtil::getFilePath('exchange', Uuid::uuid(), 'jpg'), 1);
        $token = QiniuUtil::getUploadToken();
        Log::info($key);
        $return = ['uptoken' => $token, 'key' => $key];
        return $this->success(200, 'ok', $return);
    }

    /**
     * 提交更新小程序产品信息
     * @param Request $request
     * @return array
     */
    public function store(Request $request)
    {
        $rules = [
            "img" => "required|max:150",
            "name" => "required|max:15",
            "cid" => 'required|integer',
            'con_min' => 'required|numeric',
            'con_max' => 'required|numeric',
            'exc_condition' => 'required',
            'user_name' => 'required|max:15',
            'company' => 'required|max:15',
            'phone' => 'required|regex:/^1[3456789][0-9]{9}$/',
            'wechat' => 'required',
            "mid" => "integer",
            "age_1" => "required|numeric",
            "age_2" => "required|numeric",
            "age_3" => "required|numeric",
            "age_4" => "required|numeric",
            "age_5" => "required|numeric",
            "age_6" => "required|numeric",
            "sex_1" => "required|numeric",
            "sex_2" => "required|numeric",
            "mobile_1" => "required|numeric",
            "mobile_2" => "required|numeric",
            "region" => "required|array",
        ];
        $messages = [ 'img.required' =>  '请上传图片',
                      'name.required' => '请填写名称', 
                      'cid.required' => '请选择相应类目', 
                      'con_min.required' => '请填入每日可导量最低值', 
                      'con_max.required' => '请填入每日可导量最高值', 
                      'exc_condition.required' => '请选择换量条件',
                      'user_name.required' => '请填写姓名',
                      'user_name.max' => '请正确填写姓名',
                      'company.required' => '请填写公司名称',
                      'company.max' => '公司名称最多15字',
                      'phone.required' => '请正确填写手机号',
                      'wechat.required' => '请填写微信号',
                      'age_1.required' => '请填写用户人群比例',
                      'age_2.required' => '请填写用户人群比例',
                      'age_3.required' => '请填写用户人群比例',
                      'age_4.required' => '请填写用户人群比例',
                      'age_5.required' => '请填写用户人群比例',
                      'age_6.required' => '请填写用户人群比例',
                      'sex_1.required' => '请填写男女比例',
                      'sex_2.required' => '请填写男女比例',
                      'mobile_1.required' => '请填写机型比例',
                      'mobile_2.required' => '请填写机型比例',
                      'region.required' => '请选择地域分布',
                      'age_1.numeric' => '请正确填写用户人群比例',
                      'age_2.numeric' => '请正确填写用户人群比例',
                      'age_3.numeric' => '请正确填写用户人群比例',
                      'age_4.numeric' => '请正确填写用户人群比例',
                      'age_5.numeric' => '请正确填写用户人群比例',
                      'age_6.numeric' => '请正确填写用户人群比例',
                      'sex_1.numeric' => '请正确填写男女比例',
                      'sex_2.numeric' => '请正确填写男女比例',
                      'mobile_1.numeric' => '请正确填写机型比例',
                      'mobile_2.numeric' => '请正确填写机型比例',
        ];
        $val = Validator::make($request->all(), $rules, $messages); 
        if ($val->fails()) {
            return $this->error(400,$val->errors()->first());
        }
        $ageNum = $sexNum = $mobileNum = 0;
        foreach ($request->all() as $k => $v) {
            if (strstr($k, 'age')) {
                $ageNum += $v*100;
            }else if(strstr($k, 'sex')){
                $sexNum += $v*100;
            }else if(strstr($k,'mobile')){
                $mobileNum += $v*100;
            }
        }
        if($ageNum/100 != 100){
            return $this->error(400, '用户人群年龄比例总和应为100%');
        }else if($sexNum/100 != 100){
            return $this->error(400,'男女比例总和应为100%');
        }else if($mobileNum/100 != 100){
            return $this->error(400,'终端机型比例总和应为100%');
        }
        if($request->get('con_max')<$request->get('con_min')){
            return $this->error(400,'每日可导量最大值不能小于最小值');
        }
        try{
            $minaInfo = $this->minaInfoService->createMinaInfo($request->all(),$request->user);
            $minaInfo['return_mid'] = empty($request->get('return_mid')) ? '' : $request->get('return_mid');
            return $this->success(200, "保存成功", $minaInfo);
        }catch (\Exception $e){
            Log::error('store:'.$e->getMessage());
            $getCode = $e->getCode() == 0 ? 400 : $e->getCode();
            if($getCode == 300){
                $info = json_decode($e->getMessage());
                return $this->error($getCode, $info->msg, $info->data);
            }
            return $this->error($getCode , $e->getMessage());
        }
    }

    /**
     * 检查小程序是否重名接口
     * @param Request $request
     * @return array
     */
    public function checkMinaName(Request $request){
        $rules = [
            "name" => "required|max:15",
        ];
        $messages = [
            'name.required' => '请填写名称',
            'name.max' =>  '名称最多15字',
        ];
        $val = Validator::make($request->all(), $rules, $messages);
        if ($val->fails()) {
            return $this->error(400,$val->errors()->first());
        }
        $info = $this->minaInfoService->getMinaByCondition('name',$request->get("name"));
        if($info){
            return $this->error(300, "换量大厅已有该小程序，是否前往查看",['id'=>$info['id']]);
        }
        return $this->success();
    }

    /**
     * 投诉提交与修改
     * @param Request $request
     * @param ComplainList $comPlainList
     * @return string|unknown[]
     */
    public function complain(Request $request, ComplainList $comPlainList){
        $rules = [
            'mid' => 'required|integer',
            'mark'=> 'required|max:100',
            'type'=> 'required|integer'
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $userInfo = $request->user;
            $result = MinaInfoService::pushComplain($userInfo['id'],$request->get('mark'),$request->get('type'),$request->get('mid'),$request->get('imgs'),$comPlainList);
            return $this->success(200, '提交成功',$result);
        }catch(\Exception $e){
            Log::error('complain:'.$e->getMessage());
            return $this->error(400, $e->getMessage());
        }
    }

    /**
     * 根据换量信息id查询投诉情况
     * @param Request      $request
     * @param ComplainList $comPlainList
     * @return array
     */
    public function complainEnquiry(Request $request, ComplainList $comPlainList){
        $rules = [
            'mid' => 'required|integer',
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $userInfo = $request->user;
            $result = MinaInfoService::complainEnquiryInfo($userInfo['id'],$request->get('mid'),$comPlainList);
            return $this->success(200, '查询成功',$result);
        }catch(\Exception $e){
            Log::error('complain:'.$e->getMessage());
            return $this->error(400, $e->getMessage());
        }
    }
    
    /**
     * 点赞与取消点赞
     * @param Request $request
     * @return array
     */
    public function likeOrNot(Request $request){
        $rules = [
            'mid' => 'required|integer',
        ];
        $val = Validator::make($request->all(), $rules);
        if ($val->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $data = $this->minaInfoService::likeOrNot($request->user['id'],$request->get("mid"));
            return $this->success(200, 'ok', $data);
        }catch(\Exception $e){
            return $this->error(400, '系统繁忙');
        }
    }
    
}



