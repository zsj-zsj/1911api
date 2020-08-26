<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\ApiExceptions;
use App\Model\UserModel;
use Illuminate\Support\Facades\Redis;

class CommonController extends Controller
{
    /**
     * 返回正确
     * @param string $msg
     * @param int $status
     * @return array
     */
    public function success($data=[],$msg='ok',$status=200)
    {
        $arr=[
            'data'=>$data,
            'msg'=>$msg,
            'status'=>$status,
        ];
        return $arr;
    }

    /**
     * 错误返回
     * @param $msg
     * @param int $status
     * @param array $data
     * @throws ApiException
     */
    public function fail($msg,$status=500,$data=[])
    {
        throw new ApiExceptions($msg,$status);
    }

    /**
     * 接受值
     * @param $key
     * @return array|null|string
     * @throws ApiException
     */
    public function checkApiParam($key)
    {
        if(empty ($value = request()->post($key))){
            $this->fail('缺少参数'.$key);
        };
        return $value;
    }

    /**
     * 发短信
     * @param $mobile
     * @param $code
     * @return bool
     */
    public function sendCodeMobile($mobile,$code)
    {
        $host = "http://dingxin.market.alicloudapi.com";
        $path = "/dx/sendSms";
        $method = "POST";
        $appcode = env('ApiMobileCode');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "mobile=$mobile&param=code%3A$code&tpl_id=TP1711063";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $arr = json_decode(curl_exec($curl),true);
        if($arr['return_code'] == 00000 ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取缓存版本号
     */
    public function getCacheVersion($cache_type = 'news')
    {
        switch($cache_type){
            case 'news';
                $cache_version_key = 'news_cache_version';
			$version = Redis::get($cache_version_key);
                break;
            default;
                break;
        }
        if(empty($version)){
            Redis::set($cache_version_key,1);
			$version = 1;
			Redis::expire($version,3600);
        }
        return $version;
    }
}
