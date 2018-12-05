<?php

namespace App\Services;


use App\Models\User;
use Overtrue\LaravelWeChat\Facade as EasyWeChat;

class WechatServices
{
    public static function decryptUserData($user, $iv, $encryptedData)
    {

        $sessionKey = $user->session_key;
        $app = EasyWeChat::miniProgram();
        try{
            $result = $app->encryptor->decryptData($sessionKey, $iv, $encryptedData);
            return $result;

        }catch(\Exception $e){
            return false;
        }

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


        //        $data = openssl_decrypt(base64_decode($encryptedData), "AES-128-CBC", base64_decode($sessionKey), 1, base64_decode($iv));
        //
        //        $userinfo = json_decode($data, true);
        //        if (empty($userinfo)) {
        //            throw new \Exception("更新失败");
        //        }
        //        return $userinfo;
    }

    public static function get_info($code)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $data = [
            'appid' => config("wechat.mini_program.default.app_id"),
            'secret' => config("wechat.mini_program.default.secret"),
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $result = json_decode(self::http_curl($url, $data, 0, 1), true);
        //            {
        //                "openid": "OPENID",
        //                "session_key": "SESSIONKEY",
        //                "unionid": "UNIONID"
        //            }
        if (!empty($result['errcode'])){
            throw new \Exception($result['errmsg']);
            throw new \Exception("登录失败");
        }

        //用户未授权
        return $result;
    }

    //微信付款到零钱
    public static function wx_pay($user, $money)
    {

        $config = config("wechat.default.wxpay");

        $data = [
            'mch_appid' => $config['appid'],
            'mchid' => $config['mchid'],
            'nonce_str' => md5($user['id'] . time() . uniqid()),
            'partner_trade_no' => "bzdt" . $user['id'] . "" . time() . rand(10000, 99999),
            'openid' => $user['openid'],
            'check_name' => "NO_CHECK",
            'amount' => $money * 100,
            'desc' => "地图宝藏用户提现",
            'spbill_create_ip' => "127.0.0.1",
        ];
        $data['sign'] = self::getSign($data, $config['key']);
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $result = self::http_curl($url, self::arrayToXml($data), 1, 1, $config);

        return self::xmlToArray($result);
    }

    public static function xmlToArray($xml)
    {

        //禁止引用外部xml实体

        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $val = json_decode(json_encode($xmlstring), true);

        return $val;

    }


    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));

        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach($paraMap as $k => $v){
            if (null != $v && "null" != $v){
                if ($urlEncode){
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0){
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }

        return $reqPar;
    }

    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach($arr as $key => $val){
            if (is_numeric($val)){
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }else{
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    public static function postCurl($url, $data, $type = "")
    {
        $curl = curl_init();
        if ($type == 'json'){//json $_POST=json_decode(file_get_contents('php://input'), TRUE);
            $headers = ["Content-type: application/json;charset=UTF-8", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"];
            $data = json_encode($data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    /**
     * @param $url 请求网址
     * @param bool $params 请求参数
     * @param int $ispost 请求方式
     * @param int $https https协议
     * @return bool|mixed
     */
    public static function http_curl($url, $params = false, $ispost = 0, $https = 0, $cert = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        }
        if ($ispost){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        }else{
            if ($params){
                if (is_array($params)){
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            }else{
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        if (!empty($cert)){
            //第一种方法，cert 与 key 分别属于两个.pem文件
            // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().$cert['apiclient_cert']);
            // curl_setopt($ch,CURLOPT_SSLKEY,getcwd().$cert['apiclient_key']);
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}