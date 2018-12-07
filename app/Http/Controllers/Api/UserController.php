<?php

namespace App\Http\Controllers\Api;

use App\Http\Models\Status;
use App\Models\Advert;
use App\Models\ComplainList;
use App\Models\MinaInfo;
use App\Services\MinaInfoService;
use App\Utils\MiniMessageUtil;
use DemeterChain\C;
use EasyWeChat\Kernel\Messages\Link;
use EasyWeChat\Kernel\Messages\Image;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\UserService;
use App\Models\User;
use Overtrue\LaravelWeChat\Facade as EasyWeChat;
use App\Services\WechatServices;
use App\Services\CacheService;
use Illuminate\Support\Facades\Request as frequest;
use App\Models\FormId;
use App\Services\LogService;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private $userService;
    protected $user;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    //存储formid
    public function formid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_id' => "required|string",
        ]);
        if ($validator->fails()) {
            return $this->error(400, '参数错误');
        }
        FormId::setFormId($request->user->id, $request->form_id);

        return $this->success(200, 'OK');
    }

    /**
     * 登录更新token
     * @param Request $request
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */


    public function login(Request $request, User $user,MinaInfo $minaInfo)
    {
        $rules = [
            'code' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->error(400, '参数格式错误');
        }
        try {
            $mid = $request->get('mid', '');
            $uid = $request->get('uid','');
            $code = $request->get('code','');
            $return = UserService::goLogin($code,$uid,$mid,$user,$minaInfo);
            return $this->success(200, 'ok', $return);
        } catch (\Exception $e) {
            Log::error('login:' . $e->getMessage());
            return $this->error(400, 登录失败);
        }
    }

    public function getUserInfo(Request $request)
    {
        try {
            $userInfo['avatar'] = empty($request->user['avatar']) ? '' : $request->user['avatar'];
            $userInfo['company'] = empty($request->user['company']) ? '' : $request->user['company'];
            $userInfo['user_name'] = empty($request->user['user_name']) ? '' : $request->user['user_name'];
            $userInfo['phone'] = empty($request->user['phone']) ? '' : $request->user['phone'];
            $userInfo['wechat'] = empty($request->user['wechat']) ? '' : $request->user['wechat'];
            $userInfo['address'] = empty($request->user['company_address']) ? '' : $request->user['company_address'];
            $userInfo['job'] = empty($request->user['job']) ? '' : $request->user['job'];
            return $this->success(200, 'ok', $userInfo);
        } catch (\Exception $e) {
            Log::error('getUserInfo:' . $e->getMessage());
            return $this->error(400, $e->getMessage());
        }
    }

    //用户授权 更新信息
    public function userInfo(Request $request)
    {
        try {
            $userinfo = WechatServices::decryptUserData($request->user, $request->header('iv'), $request->header('encryptedData'));
            /*
             'openId' => string 'o7xC45exyawHUQyAo5Z41NuziMV0' (length=28)
             'nickName' => string '离枝' (length=6)
             'gender' => int 1
             'language' => string 'zh_CN' (length=5)
             'city' => string 'Ganzhou' (length=7)
             'province' => string 'Jiangxi' (length=7)
             'country' => string 'China' (length=5)
             'avatarUrl' => string 'https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTLIj95pG7HPsc72eAau3bBpw1Ec1kzibtl1V19SbuyiabQhGfxic8xNIlzmBQYuF4Ovib3phsj0pm6TrA/132' (length=127)
             'unionId' => string 'owJVz0j74_EfzBQyFplfZYgpil3k' (length=28)
             'watermark' =>
             array (size=2)
             'timestamp' => int 1534421207
             'appid' => string 'wxc81e05526abe6e97' (length=18)
             */
            $return = [];
            if ($userinfo !== false) {
                $data['nick_name'] = $userinfo['nickName'];
                $data['avatar'] = empty($userinfo['avatarUrl']) ? User::DEFAULT_AVATAR : $userinfo['avatarUrl'];
                $data['unionid'] = empty($userinfo['unionId']) ? "" : $userinfo['unionId'];
                $res = User::where("id", $request->user['id'])->update($data);
                $return['nick_name'] = $data['nick_name'];
                $return['avatar'] = $data['avatar'];
            }
            return $this->success(200, "OK", $return);
        } catch (\Exception $e) {
            Log::error('userInfo:' . $e->getMessage());
            return $this->error(400, '网络繁忙');
        }
    }

    public function change(Request $request)
    {
        $rules = [
            'user_name' => 'required|max:5',
            'company'   => 'required|max:15',
            'phone'     => 'required|regex:/^1[3456789][0-9]{9}$/',
            'wechat'    => 'required',
            'address'   => 'max:50',
            'job'       => 'max:15',
        ];
        $messages = [
            'user_name.required' => '请填写姓名',
            'user_name.max'      => '请正确填写姓名',
            'company.required'   => '请填写公司名称',
            'company.max'        => '公司名称最多15字',
            'phone.required'     => '请正确填写手机号',
            'wechat.required'    => '请填写微信号',
            'address.max'        => '请正确填写地址',
            'job.max'            => '请填写正确职位信息',
        ];
        $val = Validator::make($request->all(), $rules, $messages);
        if ($val->fails()) {
            return $this->error(400, $val->errors()
                                         ->first());
        }
        try {
            $result = $this->userService::changeUserInfo($request);
            return $this->success(200, 'ok', $result);
        } catch (\Exception $e) {
            Log::error('change:' . $e->getMessage());
            return $this->error(400, '系统繁忙');
        }
    }

    /**
     * 获取未读信息条数
     * @param Request $request
     * @return array
     */
    public function pullInviteAcceptCount(Request $request)
    {
        $authorization = frequest::header('authorization');
        $uid = $request->get('uid');
        if (CacheService::getCache(CacheService::userToken($uid)) != $authorization) {
            return $this->success(200, '成功', '');
        }
        $type = $request->get('type', 1);
        $acceptInviteKey = Cacheservice::userAcceptInviteCountKey($uid);
        $launchInviteKey = CacheService::userLaunchInviteCountKey($uid);
        $inviteCache = CacheService::getCache($acceptInviteKey);
        $acceptCache = CacheService::getCache($launchInviteKey);
        $inviteCount = $inviteCache !== false ? $inviteCache : 0;
        $acceptCount = $acceptCache !== false ? $acceptCache : 0;
        $return = $type == 1 ? ($inviteCount + $acceptCount) : ['inviteCount' => $inviteCount, 'acceptCount' => $acceptCount];
        return $this->success(200, '获取成功', $return);
    }

    /**
     * 拉取通知
     *
     * @param Request $request
     * @return array|array
     */
    public function pullNotice(Request $request)
    {
        $authorization = frequest::header('authorization');
        $uid = $request->get('id');
        if (CacheService::getCache(CacheService::userToken($uid)) != $authorization) {
            return $this->success(200, '成功', '');
        }
        $key = $request->get('key');
        if (empty($authorization) || !in_array($key, config('minainfo.INVITE_TYPE'))) {
            return $this->success(200, '成功', '');
        }
        $cacheKey = CacheService::userInviteCacheKey($uid, $key);
        $res = CacheService::getCache($cacheKey);
        if ($res !== false) {
            CacheService::clearCache($cacheKey);

            return $this->success(200, '获取成功', $res);
        } else {
            return $this->success(200, '获取成功', '');
        }
    }

    public function getMedia()
    {
        //$offset = $request->get('offset', 0);
        //$app = MiniMessageUtil::getApp();
        //$app = WechatServices::
        //return $app->material->list('image', 'https://image.lingyiliebian.com/exchange/2018110/8163018/0000006/8d6c2e9/5f0c265b699e7a1940cb8fa4.jpg', 20);
        //$app = MiniMessageUtil::getApp();
        $wechat = EasyWeChat::miniProgram();
        //$path = public_path();
        return $wechat->media->uploadImage(public_path('20181109111815.png'));
    }

    /**
     * 小程序流量变现
     *
     * @return array
     */
    public function minaRealize()
    {
        return $this->success(200, '请求成功', ['notice' => Status::MINA_REALIZE_NOTICE, 'wechat' => Status::MINA_REALIZE_WECHAT]);
    }

    /**
     * 广告列表
     *
     * @param Request $request
     * @param Advert  $advert
     * @return array
     */
    public function advertList(Request $request, Advert $advert)
    {
        $rules = [
            'position' => 'required',
        ];
        $messages = [
            'position.required' => '广告位置错误',
        ];
        $val = Validator::make($request->all(), $rules, $messages);
        if ($val->fails()) {
            return $this->error(400, $val->errors()
                                         ->first());
        }
        try {
            $result = $this->userService::getAdvertList($request->get('position'), $advert);
            return $this->success(200, 'ok', $result);
        } catch (\Exception $e) {
            Log::error('change:' . $e->getMessage());
            return $this->error(400, '系统繁忙');
        }
    }

    /**
     * 客服回复图片消息
     *
     * @param Request $request
     * @return mixed|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     */
    public function message(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $request->get('echostr', '验证字符串');
        } else {
            $openId = $request->get('openid', 'oX24J46KIQ1mfPCJJyZrZp4SKsKU');
            $app = MiniMessageUtil::getApp();
            /*     $link = [
                     'title' => "马上进群",
                     'description' => '加入零一换量助手群',
                     'url' => 'https://shimo.im/docs/FqcryngGWZMzuaoL',
                     'thumb_url' => 'https://image.lingyiliebian.com/kankan/images/dian-logo.png',
                 ];
             $message = new Link($link);*/
            $catchKey = CacheService::customServiceJoinInGroup();
            $catch = CacheService::getCache($catchKey);
            if (empty($catch)) {
                $info = self::getMedia();
                if ( !empty($info['media_id'])) {
                    //保存两天
                    CacheService::setCache($catchKey, $info['media_id'], 2880);
                } else {
                    Log::error('获取微信客服图片失败');
                }
            }
            $message = new Image(CacheService::getCache($catchKey));
            $app->customer_service->message($message)
                                  ->to($openId)
                                  ->send();
            return "success";
        }

    }
}
