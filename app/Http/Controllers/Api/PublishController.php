<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\PublishModel;
use App\Model\NewTitleModel;

class PublishController extends CommonController
{
    public function publish(Request $request)
    {
        $uid = $request->uid;
        $redisUserKey = 'userInfo_'.$uid['uid'];
        $user_info = Redis::hgetAll($redisUserKey);
        if(!$user_info){
            return $this->fail('请登录');
        }
        $p_text = $request->p_text;
        if(!$p_text){
            return $this->fail('请输入评论内容');
        }
        $title_id = $request->title_id;
        if(!$title_id){
            return $this->fail('请选择新闻');
        }
        $arr = [
            'p_text'=>$p_text,
            'p_time'=>time(),
            'user_id'=>$uid['uid'],
            'title_id'=>$title_id
        ];
        $res = PublishModel::insert($arr);
        if($res){
            $commont_count = NewTitleModel::find($title_id)->toArray();
            $num = $commont_count['commont_count'];
            $r = NewTitleModel::where(['title_id'=>$title_id])->update(['commont_count'=>$num+1]);
            if($r){
                return $this->success();
            }
        }else{
            return $this->fail('评论失败,请重试!');
        }
    }

    public function publishList(Request $request)
    {
        $id = $request->id;
        $page = $request->post('page') ?? 1;
        if(!$id){
            return $this->fail('缺少参数');
        }
        $res = PublishModel::where(['u_publish.title_id'=>$id])
                    ->join('u_user','u_user.user_id','=','u_publish.user_id')->paginate(3)->toArray();
        foreach ($res['data'] as $k => $v) {
            $res['data'][$k]['p_time'] = date('Y-m-d H:i:s', $v['p_time']);
        }
        return $this->success($res);
    }

}
