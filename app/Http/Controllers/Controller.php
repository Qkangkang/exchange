<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function json($code, $msg, $data = [])
    {
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }
    
    /**
     * 调用成功
     * @param string $msg
     * @param array $data
     * @return array
     */
    function success($code = 200, $msg = 'success', $data = [])
    {
        header('WWW-Authenticate: xBasic realm=""');
        
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }
    
    
    /**
     * 调用失败
     * @param string $msg
     * @param array $data
     * @return array
     */
    function error($code = 400, $msg = '系统繁忙', $data = [])
    {
        header('WWW-Authenticate: xBasic realm=""');
        
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }
}
