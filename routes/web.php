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
    return view('welcome');
});
//第一次
Route::get('Weixin/vaild','WXController@get_vaild');
// 第二次以及以后
Route::post('Weixin/vaild','WXController@post_vaild');
//获取接口
Route::get('Weixin/access_token','WXController@get_access_token');
//创建菜单
Route::get('Weixin/create_menu','WXController@create_menu');
//群发
Route::get('Weixin/more_send','WXController@send');
//微信支付测试
Route::get('Weixin/test','WxPayController@test');
//回调
Route::get('Weixin/pay_notify','WxPayController@pay_notify');
