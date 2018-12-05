<?php
/**
 * Created by PhpStorm.
 * User: rain
 * Date: 2018/4/7
 * Time: 下午4:22
 */

namespace App\Utils;


use App\Exceptions\UtilException;
use Illuminate\Support\Facades\Log;
use Overtrue\LaravelWeChat\Facade as EasyWeChat;

class MiniMessageUtil
{

    const TEMPLATE_PROFIT_NOTICE = 1;
    const TEMPLATE_PLAY_NOTICE   = 2;


    public static function getApp($name = '')
    {
        return EasyWeChat::miniProgram($name);
    }

    public static function getAppId($name = 'default')
    {
        return config('wechat.mini_program.' . $name . '.app_id');
    }

    /**
     * 获取小程序绑定的手机号码
     * @param \App\Models\User $user
     * @param $iv
     * @param $encrypted
     * @param string $appName
     * @return array
     */
    public static function getPhoneNumber(User $user, $iv, $encrypted, $appName = '')
    {
        $app = self::getApp($appName);
        $appId = self::getAppId($appName);

        $sessionKey = CSessionInfo::getSessionKey($user->getUserOpenId($appId));
        $result = $app->encryptor->decryptData($sessionKey, $iv, $encrypted);

        //正确返回的数据格式
        //$result['purePhoneNumber'], $result['countryCode']);

        return $result;
    }


    /**
     * 解密用户信息
     * @param $code
     * @param $iv
     * @param $encrypted
     * @param string $appName
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \App\Exceptions\UtilException
     */
    public static function decryptUserData($code, $iv, $encrypted, $appName = '')
    {
        $app = self::getApp($appName);
        $result = $app->auth->session($code);
        if (!isset($result['unionid'], $result['openid'], $result['session_key'])){
            throw new UtilException('code验证失败');
        }
        $unionId = $result['unionid'];
        $openId = $result['openid'];
        $sessionKey = $result['session_key'];

        CSessionInfo::setSessionKey($openId, $sessionKey);

        $result = $app->encryptor->decryptData($sessionKey, $iv, $encrypted);
        //正确返回的数据格式
        //        {
        //            "openId": "o_xV75eRlb0PBWbpjyix7HMNMEKc",
        //            "nickName": "流年",
        //            "gender": 1,
        //            "language": "zh_CN",
        //            "city": "Shenzhen",
        //            "province": "Guangdong",
        //            "country": "China",
        //            "avatarUrl": "https://wx.qlogo.cn/mmopen/vi_32/ttdKiaybrdOA57eVvA9Hicy5xE8t7CPqpKJ68wicPUYXiata4lgD9b1tZvqnnVWLsSWribhJibZakUsia2g4DjhtC29vQ/132",
        //            "unionId": "oNESR0gPIbyWobH8XtJIIcteZ4bs",
        //            "watermark": {
        //            "timestamp": 1532418247,
        //                "appid": "wx03428e02d74df1af"
        //            }
        //        }

        return $result;

    }

    /**
     * 发送
     * @param $userId
     * @param $openId
     * @param $templateId
     * @param $data
     * @param string $page
     * @return array|bool|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    private static function send($userId, $openId, $templateId, $data, $page = 'index')
    {
        $template = self::getTemplateData($templateId, $data);
        if ($template === false){
            return false;
        }else{
            $templateId = $template['templateId'];
            $data = $template['data'];
        }

        $formId = FormId::getFormId($userId);
        if ($formId === false){
            return false;
        }

        $app = self::getApp();
        $templateMessage = [
            'touser' => $openId,
            'template_id' => $templateId,
            'page' => $page,
            'form_id' => $formId,
            'data' => $data,
            'emphasis_keyword' => $template['emphasis_keyword'],
        ];

        if (empty($templateMessage['emphasis_keyword'])){
            unset($templateMessage['emphasis_keyword']);
        }

        try{
            $result = $app->template_message->send($templateMessage);

            //Log::info(json_encode($result) . '===template_message===' . json_encode($templateMessage));
        }catch(\Exception $exception){
            Log::info('===send_template_error===' . $exception->getMessage() . '===' . json_encode($templateMessage));

            return false;
        }


        return $result;
    }
    
    
    /**
     * 获取小程序二维码到七牛
     * @param UserModel $user
     * @param int $type
     * @param array $array
     * @return mixed
     * @throws UtilException
     */
    public static function getQrCodeToQiNiu($array = [])
    {
        try{
            $mini = self::getApp();
            $scene = 'mina_id@' . $array['mina_id'];
            $option = [
                'page' => 'pages/index',
            ];
            $fileContent = $mini->app_code->getUnlimit($scene, $option);
            $file_name = '/exchange/' . uniqid() . '.png';
            $url = QiniuUtil::uploadBase64($fileContent);
            return $url;
        }catch(\Exception $e){
            Log::error("生成二维码失败".$e->getMessage());
            throw new UtilException("生成二维码失败".$e->getMessage());
        }
        
    }




}