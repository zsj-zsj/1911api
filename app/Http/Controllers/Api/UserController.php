<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\UserModel;
use App\Model\UserMobileCode;
use App\Model\UserTokenModel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserController extends CommonController
{
    public $expire = 300;

    /**
     * 注册
     * @return array|void
     * @throws \App\Exceptions\ApiExceptions
     */
    public function reg()
    {
        $user_name = $this->checkApiParam('user_name');
        $pwd = $this->checkApiParam('pwd');
        $pwds = $this->checkApiParam('pwds');
        $mobile = $this->checkApiParam('mobile');
        $mobileCode = $this->checkApiParam('mobileCode');
        $reg_type = $this->checkApiParam('reg_type');

        $name = UserModel::where(['user_name'=>$user_name])->first();
        if($name){
            return $this->fail('用户名已存在');
        }

        if($pwd != $pwds){
            return $this->fail('密码不一致');
        }

        $preg = '/^1{1}\d{10}$/';
        if(! preg_match_all($preg,$mobile)){
            return $this->fail('手机号格式不对');
        }

        $code = UserMobileCode::where(['phone'=>$mobile])->orderBy('msg_id','desc')->select('msg_code','expire')->first();

        if($mobileCode != $code->msg_code){
            return $this->fail('验证码有误');
        }

        if($code->expire < time()){
            return $this->fail('验证码超时');
        }

        $data = [
            'user_name'=>$user_name,
            'pwd'=>password_hash($pwd,PASSWORD_BCRYPT),
            'mobile'=>$mobile,
            'c_time'=>time(),
            'reg_type'=>$reg_type,
            'status'=>1
        ];

        $res =  UserModel::insertGetId($data);
        if($res){
            return $this->success($res);
        }else{
            return $this->fail('注册失败，请重试');
        }
    }

    /**
     * 发送验证码
     * @param Request $request
     * @return array
     */
    public function sendModile( Request $request )
    {
        $sid = $this->checkApiParam('sid');
        $mobile = $this->checkApiParam('mobile');
        $imgCode = $this->checkApiParam('imgCode');
        $type = $this->checkApiParam('type');

        $request->session()->setId($sid);
        $request->session()->start();

        $session_code = $request->session()->get('img_code');

        if($session_code != $imgCode){
            return $this->fail('图片验证码不正确');
        }else{
            $request->session()->forget('img_code');
        }

        $count = UserModel::where(['mobile'=>$mobile])->count();
        if($count > 3){
            return $this->fail('手机号注册上限');
        }

        $where=[
            ['phone','=',$mobile],
            ['type','=',$type],
        ];
        $mobileInfo = UserMobileCode::where($where)->orderBy('msg_id','desc')->first();
        $msgCode = rand(1000,9999);

        if($mobileInfo){
            $time = strtotime(date('Y-m-d').'00:00:00');
            $countWhere=[
                ['phone','=',$mobile],
                ['ctime','>=',$time],
            ];
            $userCount = UserMobileCode::where($countWhere)->count();
            if($userCount >= 10){
                return $this->fail('今天已上限');
            }
            if(time() < $mobileInfo->c_time +60 ){
                return $this->fail('发送频繁,请稍后');
            }
        }
        $arr=[
            'phone'=>$mobile,
            'type'=>$type,
            'msg_code'=>$msgCode,
            'status'=>1,
            'ctime'=>time(),
            'expire'=> time() + $this->expire,
        ];

        $res = UserMobileCode::insertGetId($arr);
        if ($this->sendCodeMobile($mobile,$msgCode) ){
            return $this->success();
        }else{
            return $this->fail('发送失败，请重试');
        }
    }

    /**
     * 获取验证码路径
     */
    public function getImgCodeUrl(Request $request)
    {
        $request->session()->start();
        $session_id = $request->session()->getId();
        $arr['url'] = 'http://newapi.com/api/showImgCode?sid='.$session_id;
        $arr['sid'] = $session_id;
        return $this->success($arr);
    }

    /**
     * 图片验证码
     */
    public function showImgCode(Request $request)
    {
        $sid = $request->get('sid');
        if(empty($sid)){
            return $this->fail('图片验证码输出失败');
        }
        $request->session() ->setId($sid);
        $request->session()->start();

        header('Content-Type: image/png');

        $im = imagecreatetruecolor(100,30);

        $white = imagecolorallocate($im,255,255,255);
        $black = imagecolorallocate($im,0,0,0);
        imagefilledrectangle($im,0 ,0, 399 ,29,$white);
        $grey = imagecolorallocate($im ,128 ,128,128);

        $num = ''.rand(1000,9999);

        $request->session()->put('img_code',$num);
        $request->session()->save();

        $font = storage_path().'/font/Gabriola.ttf';
        $i = 0;
        while($i < strlen($num)){
            imageline($im,rand(0,10),rand(0,25),rand(90,100),rand(10,25),$grey);
            imagettftext($im,20,rand(-15,15),11+20*$i,21,$black,$font,$num[$i]);
            $i++;
        }
        imagepng($im);
        imagedestroy($im);
        exit;
    }

    /**
     * 登录
     */
    public function Login(Request $request)
    {
        $user = $this->checkApiParam('user_name');
        $pwd = $this->checkApiParam('pwd');
        $tt = $this->checkApiParam('tt');

        $where=[
            ['user_name','=',$user],
            ['status','<','4']
        ];
        $userInfo = UserModel::where($where)->orWhere(['mobile'=>$user])->first();

        if(!$userInfo){
            return $this->fail('没有此用户');
        }
        $pass = password_verify($pwd,$userInfo->pwd);
        $redis_incr_key = 'incr_'.$userInfo->user_id;

        $incr_ennor = Redis::get($redis_incr_key);

        if($pass){
            //密码正确
            if($incr_ennor >=4){
                return $this->fail('用户被锁定');
            }
            Redis::del($redis_incr_key);
            $token = $this->_createUserToken($userInfo->user_id,$tt);
            $tokenResArr = collect($userInfo)->toArray();
            $tokenResArr['token']=$token;
            $redisUserKey = 'userInfo_'.$userInfo->user_id;
            Redis::hMset($redisUserKey,$tokenResArr);
            Redis::expire($redisUserKey,7200);
            return $this->success($tokenResArr);
        }else{
            //密码错误
            if($incr_ennor >= 4){
                $expire = Redis::ttl($redis_incr_key);
                if($expire < 60){
                    $error_time = $expire.'秒';
                }elseif( $expire < 3600 ){
                    $minutes = intval($expire /60 );
                    $error_time = $minutes.'分钟';
                }else{
                    $hour = intval($expire / 3600);
                    $minutes = intval(($expire - 3600) / 60);
                    $error_time = $hour.'小时'.$minutes.'分钟';
                }
                return $this->fail('用户已被锁定，'.$error_time.'后解锁');
            }
            if($incr_ennor < 5){
                Redis::incr($redis_incr_key);
            }
            if($incr_ennor == null || $incr_ennor == 0){
                Redis::expire($redis_incr_key,7200);
            }
            return $this->fail('密码错误'.($incr_ennor+1).'次,五次将被锁定');
        }
    }

    /**
     * 用户token入库
     */
    private function _createUserToken($user_id ,$tt)
    {
        $token = Str::random(32);
        $time = time();
        $where = [
          ['user_id','=',$user_id],
          ['tt','=',$tt],
          ['expire','>',$time],
        ];
        $model = new UserTokenModel();
        $userTokenObj = $model->where($where)->first();
        if(empty($userTokenObj)){
            $model -> user_id = $user_id;
            $model -> tt = $tt;
            $model -> token = $token;
            $model -> expire = $time + 7200;
            $model -> ctime = time();
            $model -> status = 1;
            $res = $model -> save();
        }else{
            $userTokenObj -> expire = $time + 7200;
            $res = $userTokenObj ->save();
        }
        if($res){
            return $token;
        }else{
            return $this->fail('令牌生成失败,请重试');
        }
    }
}
