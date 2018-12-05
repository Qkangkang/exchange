<?php

namespace App\Http\Middleware;

use App\Http\Models\Status;
use App\Services\CacheService;
use Closure;
use Illuminate\Support\Facades\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class Authenticate
{
    /**
     * 登录校验
     * @param         $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $access_token = Request::header('authorization');
        /** @var UserModel $user */
        $user = User::where('access_token',$access_token)->select('id','nick_name','company','avatar','wechat','phone','user_name','access_token','status','apply_count','remain_apply_count','session_key','company_address','job')->first();
        if (!$access_token || !$user){
            echo json_encode(['code' => 888, 'msg' => '请重新登录', 'data' => []]);
            exit;
        }
        $url = Request::route()->uri();
        //校验用户封禁
        $checkUserAuthUrlArray = [
            'api/invite/create',
            'api/invite/agree_or_not',
            'api/invite/update_release',
            'api/mina/store',
            'api/mina/complain',
            'api/mina/like_or_not'
        ];
        if(in_array($url,$checkUserAuthUrlArray)){
            if($user['status'] == Status::USER_DISABLED){
                echo json_encode(['code' => 400, 'msg' => Status::USER_DISABLED_NOTICE, 'data' => []]);
                exit;
            }
        }
        CacheService::setCache(CacheService::userToken($user['id']),$user['access_token']);
        $request->user = $user;
        return $next($request);
    }
}
