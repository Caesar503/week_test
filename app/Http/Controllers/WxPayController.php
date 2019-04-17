<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WxPayController extends Controller
{
    //统一接口
    public $only = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    //回调接口
    public $notify = "http://1809zhaokai.comcto.com/Weixin/pay_notify";
    //测试
    public function test()
    {
        //随机字符串
        $str = Str::random(16);
        // echo $str;die;
        // 商户订单号
        $order = time().mt_rand(11111,99999);
//        echo $order;die;


        //组合参数
        $data = [
            'appid' =>  env('WEIXIN_APPID_0'),  //微信支付绑定的服务号
            'mch_id' =>  env('WEIXIN_MCH_ID'),   //商户的id
//            'device_info' =>''                   //设备终端号（没有-》不写）
            'nonce_str' => $str,                  //随机字符串
            'sign_type' => 'MD5',                //签名类型
            'body' => '开发测试-'.mt_rand(111,999).Str::random(6),
            'out_trade_no' => $order,
            'total_fee' => 1,
            'spbill_create_ip' =>  $_SERVER['REMOTE_ADDR'],
            'notify_url' => $this->notify,
            'trade_type' => 'NATIVE'
        ];
//        print_r($data);die;

        //签名
        $this->values = [];
        $this->values = $data;
        $this->SetSign();
//        print_r($this->values);
        $xml = $this->ToXml(); //转化为xml格式
//        echo $xml;


        //请求接口->接收响应数据
        $res = $this->postXmlCurl($xml,$this->only, $useCert = false, $second = 30);
        $res = simplexml_load_string($res);
        $url = $res->code_url;

        return view('weixin.test',['url'=>$url]);
    }
    public function SetSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }
    private function MakeSign()
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".env('WEIXIN_MCH_KEY');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    protected function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
    //转化为xml格式
    protected function ToXml()
    {
        if(!is_array($this->values)
            || count($this->values) <= 0)
        {
            die("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    private  function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//		if($useCert == true){
//			//设置证书
//			//使用证书：cert 与 key 分别属于两个.pem文件
//			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
//			curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
//			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
//			curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
//		}
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            die("curl出错，错误码:$error");
        }
    }
    public function pay_notify()
    {
        echo 1111;
    }
}
