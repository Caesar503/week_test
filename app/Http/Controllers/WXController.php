<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App;
class WXController extends Controller
{
   	public function get_vaild(){
   		echo $_GET['echostr'];
   	}
   	public function post_vaild(){
   		$content = file_get_contents("php://input");
      //解析xml数据
      $res  = simplexml_load_string($content);
   		// dd($res);
   		$time = date('Y-m-d H:i:s',time());
   		$str = $time.$content."\n";
   		// 写入日志
   		file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
   		//判断
   		if($res->MsgType=='event'){
   			if($res->Event=='subscribe'){
   				// echo '关注事件';
   				 $l = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->get_access_token()."&openid=".$res->FromUserName."&lang=zh_CN";
           $data = file_get_contents($l);
           $u = json_decode($data,true);
           $date = [
              'openid'=>$res->FromUserName,
              'nickname'=>$u['nickname'],
              'sex'=>$u['sex'],
              'headimgurl'=>$u['headimgurl']
           ];
           App\Model\Wx::insert($date);
           echo "<xml><ToUserName><![CDATA[$oid]]></ToUserName><FromUserName><![CDATA[$gzhid]]></FromUserName><CreateTime>".time()."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[欢迎关注{$info['nickname']}]]></Content></xml>";
   			}
   		}else if($res->MsgType=='text'){
   			echo 'text';
   		}
   	}
   	public function get_access_token(){
   		$k = 'access_token';
        $token = Redis::get($k);
        if($token==''){
            $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_APPSECRET');
            $response = file_get_contents($url);
             // dd($response);
            $res = json_decode($response,true);
            // print_r($res);die;
            $token = $res['access_token'];
            Redis::set($k,$token);
            Redis::expire($k,3600);
        }
        return $token;
   	}
}
