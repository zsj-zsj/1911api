<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
class ApiCount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = $request->path();
        $ip = $_SERVER['SERVER_ADDR'];
        $a = md5($path.$ip);
        $incr_key='ApcCountincr_'.$a;
        $a=Redis::get($incr_key);
        if($a<20){
            $incr=Redis::incr($incr_key);
            Redis::expire($incr_key,60);
        }else{
            $arr=[
                'erron'=>500,
                'msg'=>'勿刷接口'
            ];
            return $arr;
        }
        return $next($request);
    }
}
