<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

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

      //用户的openid
      $oid = $res->FromUserName;
      // 公众号id
      $gzhid = $res->ToUserName;
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
               App\Model\WxText::insert($date);
               echo "<xml><ToUserName><![CDATA[$gzhid]]></ToUserName><FromUserName><![CDATA[$oid]]></FromUserName><CreateTime>".time()."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[欢迎关注{$u['nickname']}]]></Content></xml>";
       			}
   		}else if($res->MsgType=='text'){
       			$text_data = [
              'openid'=>$res->FromUserName,
              'gzhid'=>$res->ToUserName,
              'msgid'=>$res->MsgId,
              'text'=>$res->Content,
              'create_t'=>$res->CreateTime
            ];
            App\Model\WxText::insert($text_data);
            echo "<xml><ToUserName><![CDATA[$gzhid]]></ToUserName><FromUserName><![CDATA[$oid]]></FromUserName><CreateTime>".time()."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[我们已收到您的消息,亲,稍等]]></Content></xml>";
      }else if($res->MsgType=='voice'){
            $url ="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->get_access_token()."&media_id=".$res->MediaId;
            $url1 = file_get_contents($url);
            $file_name = time().mt_rand(11111,99999).'.amr';
            $re = file_put_contents('wx/voice/'.$file_name,$url1);
            $voice_data = [
              'openid'=>$res->FromUserName,
              'gzhid'=>$res->ToUserName,
              'msgid'=>$res->MsgId,
              'mediaid'=>$res->MediaId,
              'url'=>'wx/voice/'.$file_name,
            ];
            App\Model\WxVoice::insert($voice_data);
            echo "<xml><ToUserName><![CDATA[$gzhid]]></ToUserName><FromUserName><![CDATA[$oid]]></FromUserName><CreateTime>".time()."</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[我们已收到您的语音消息,亲,稍等]]></Content></xml>";
      }else if($res->MsgType=='image'){
            $img = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->get_access_token()."&media_id=".$res->MediaId;
            $img1 = file_get_contents($img);

            $client = new Client();
            $response = $client->get(new Uri($img));
            //响应头
            $header = $response->getHeaders();
            // dd($header);
            $pp = $header['Content-disposition'][0];
            $ppp = rtrim(substr($pp,-20),'"');
            $img_name = 'weixin/'.substr(md5(time().mt_rand()),10,8).'_'.$ppp;
            // echo $img_name;
            $image_data = [
              'openid'=>$res->FromUserName,
              'gzhid'=>$res->ToUserName,
              'msgid'=>$res->MsgId,
              'mediaid'=>$res->MediaId,
              'url'=>$img_name,
            ];
            App\Model\WxVoice::insert($image_data);
            $rs = Storage::put($img_name,$response->getbody());
            if($rs){
              echo '保存成功';
            }else{
              echo '保存失败';
            }
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
    public function create_menu(){
      //拼接接口
      $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->get_access_token();
      //传数据
      $data = [
        'button'=>[
              [
                'type'=>'click',
                'name'=>'hellow',
                'key'=>'key_menu_001'
              ],
              [
                'type'=>'click',
                'name'=>'world',
                'key'=>'key_menu_002'
              ],
        ]
      ];

      $datas = json_encode($data,JSON_UNESCAPED_UNICODE);
      //发送请求
      $client = new Client();
      $res1 = $client->request('POST',$url,[
            'body'=>$datas
      ]);
      // 处理请求
        $res = $res1->getBody();
        $res = json_decode($res);
        if($res->errcode == 0){
            echo "创建菜单成功";
        }else{
            echo "创建菜单失败";
        }
    }
}
