<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CateModel;
use App\Model\NewTitleModel;
use Illuminate\Support\Facades\Redis;

class CateNewController extends CommonController
{
    public function cateNew()
    {
        $res = CateModel::where(['status'=>1])->get();
        return $this->success($res);
    }

    /**
     * 分类列表
     * @param Request $request
     * @return array|void
     * @throws \App\Exceptions\ApiExceptions
     */
    public function cateTitle(Request $request)
    {
        $id = $request->id;
        $page = $request->post('page') ?? 1;
        if(!$id){
            return $this->fail('请传参数');
        }

        $redisKeyPage = 'new_list_info_'.$page;
        $redisKeyPage = $redisKeyPage.'_'.$this->getCacheVersion('news');

        //判断是否有缓存
        if($id_list = Redis::get($redisKeyPage)){
            $id_arr = unserialize($id_list);
            $lisi = $this->getListCache($id_arr);
        }

        $where = [
            ['new_title.cate_id','=',$id],
            ['new_title.statusss','=',1],
        ];
        $res = NewTitleModel::where($where)
                ->orderBy('new_title.c_times','desc')
                ->join('new_category','new_category.cate_id','=','new_title.cate_id')
                ->paginate(10);
        $res = collect($res)->toArray();
        // 根据列表的数据生成 原子的缓存 根据详情数据缓存
        if($res){
            $this->buildNewDetailCache($res['data']);
        }
        $this->buildNewListCache($redisKeyPage,$res['data']);

        return $this->success($res);
    }

    /**
     * 获取列表缓存
     */
    public function getListCache($id_arr)
    {
        $arr = [];
        foreach($id_arr as $k=>$v){
            $detail_key = 'news_detail_'.$v;
            $detail = Redis::hGetAll($detail_key);
            if($detail){
                $arr[] =$detail;
            }else{
                $res = NewTitleModel::where(['title_id'=>$v])->first()->toArray();
                $detail = collect($res)->toArray();
                Redis::hMset($detail_key,$detail);
                $arr[] =$detail;
            }
        }
        return true;
    }

    /**
     * 根据列表数据生成详情缓存
     */
    public function buildNewDetailCache($data)
    {
        foreach($data as $k=>$v){
            $detail_key = 'news_detail_'.$v['title_id'];
            Redis::hMset($detail_key,$v);
            Redis::expire($detail_key,3600);
        }
        return true;
    }

    /**
     * 列表缓存
     * @param $redisKeyPage
     * @param $res
     */
    public function buildNewListCache($redisKeyPage,$res)
    {
        $id_arr = array_column($res,'title_id');
        if(Redis::set($redisKeyPage,serialize($id_arr))){
            Redis::expire($redisKeyPage,3600);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 新闻详情
     * @param Request $request
     * @return array|void
     * @throws \App\Exceptions\ApiExceptions
     */
    public function newDetail(Request $request)
    {
        $id = $request->id;
        if(!$id){
            return $this->fail('缺少参数');
        }
        $data = NewTitleModel::where(['new_title.title_id'=>$id])
                    ->join('new_category','new_category.cate_id','=','new_title.cate_id')
                    ->first();
        return $this->success($data);
    }

    /**
     * 主页倒叙排序
     * @return array
     */
    public function descIndex()
    {
        $res = NewTitleModel::join('new_category','new_category.cate_id','=','new_title.cate_id')
                    ->orderBy('title_id','desc')->limit(10)->get();
        $res = collect($res)->toArray();
        return $this->success($res);
    }
}
