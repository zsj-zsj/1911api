<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
//    return view('welcome');
    phpinfo();
});

Route::prefix('api')->middleware('apiCount')->group(function(){
    //用户
    Route::post('reg','Api\UserController@reg');  //注册
    Route::post('sendModile','Api\UserController@sendModile'); //发短信
    Route::any('showImgCode','Api\UserController@showImgCode'); //图片验证码
    Route::any('getImgCodeUrl','Api\UserController@getImgCodeUrl'); //获取图片验证码路径
    Route::any('login','Api\UserController@Login');  //登录
    //新闻
    Route::any('cateNew','Api\CateNewController@cateNew');  //导航栏
    Route::any('cateTitle','Api\CateNewController@cateTitle');  //分类下的标题
    Route::any('newDetail','Api\CateNewController@newDetail');  //详情
    Route::any('descIndex','Api\CateNewController@descIndex');  //主页详情

    //评论
    Route::any('publish','Api\PublishController@publish');  //评论添加
    Route::any('publishList','Api\PublishController@publishList');  //评论列表
});
