<?php

namespace App\Http\Controllers\Api;

use App\Http\Models\Status;
use App\Services\CacheService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\InviteInfoService;
use App\Services\MinaInfoService;
use App\Models\MinaInfo;
use Illuminate\Support\Facades\Validator;

class InviteInfoController extends Controller
{

    /**
     * 邀请合作
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $rules = [
            'mid' => 'required|integer',
        ];
        $val = Validator::make($request->all(), $rules);
        if ($val->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $limit = InviteInfoService::invitingCooperation($request,$request->user);
            return $this->success(200, 'ok', $limit);
        }catch(\Exception $e){
            $getCode = $e->getCode() == 0 ? 400 : $e->getCode();
            return $this->error($getCode , $e->getMessage());
        }
    }
    
    /**
     * 同意还是拒绝合作
     * @param request $request
     * @return array
     */
    public function agreeOrNot(request $request){
        $rules = [
            'inid' => 'required|integer',
            'type' => 'required|integer'
        ];
        $val = Validator::make($request->all(), $rules);
        if ($val->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $userInfo = $request->user;
            $limit = InviteInfoService::agreeCooperateOrNot($request,$userInfo);
            if($limit){
                return $this->success(200, 'ok', $limit);
            }
        }catch(\Exception $e){
            return $this->error(400,$e->getMessage());
        }
    }
    
    /**
     * 发布记录
     * @return unknown[]
     */
    public function releaseList(Request $request){
        try{
            $page = $request->get('page', 1);
                $pageSize = $request->get('page_size', 10) > Status::MAX_PAGE_SIZE ? Status::MAX_PAGE_SIZE : $request->get('page_size', 10);
            $userInfo = $request->user;
            $limit = MinaInfoService::getMinaInfoList($page,$pageSize,$userInfo['id'],$request);
            return $this->success(200, 'ok', $limit);
        }catch(\Exception $e){
            return $this->error(400, '系统繁忙');
        }
    }

    /**
     * 黑名单列表
     * @param Request $request
     * @return array
     */
    public function minaBlackList(Request $request){
        try{
            $page = $request->get('page', 1);
            $pageSize = $request->get('page_size', 10) > Status::MAX_PAGE_SIZE ? Status::MAX_PAGE_SIZE : $request->get('page_size', 10);
            $type = 'minaBlackList';
            $limit = MinaInfoService::getMinaInfoList($page,$pageSize,'',$request,$type);
            return $this->success(200, 'ok', $limit);
        }catch(\Exception $e){
            return $this->error(400, '系统繁忙');
        }
    }
    
    /**
     * 更新
     * @param Request $request
     * @param MinaInfo $minaInfo
     * @return string|unknown[]
     */
    public function updateRelease(Request $request,MinaInfo $minaInfo){
        $rules = [
            'mid' => 'required|integer'
        ];
        $val = Validator::make($request->all(), $rules);
        if ($val->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $userInfo = $request->user;
            $name = InviteInfoService::updateReleaseMina($userInfo['id'],$request->get('mid'),$minaInfo);
            return $this->success(200, '“'.$name.'”置顶成功', $minaInfo);
        }catch(\Exception $e){
            return $this->error(400, $e->getMessage());
        }
    }
    
    /**
     * 合作记录
     * @param Request $request
     * @return unknown[]
     */
    public function inviteList(Request $request){
        $rules = [
            'type' => 'required|integer'
        ];
        $val = Validator::make($request->all(), $rules);
        if ($val->fails()){
            return $this->error(400,'参数格式错误');
        }
        try{
            $page = $request->get('page', 1);
            $pageSize = $request->get('page_size', 10) > Status::MAX_PAGE_SIZE ? Status::MAX_PAGE_SIZE : $request->get('page_size', 10);
            $type = $request->get('type',1);
            $limit = InviteInfoService::getinviteList($page,$pageSize,$type,$request->user);
            return $this->success(200, 'ok', $limit);
        }catch(\Exception $e){
            return $this->error(400, '系统繁忙');
        }
    }
}
