<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WXController extends Controller
{
   	public function get_vaild(){
   		echo $_GET['echostr'];
   	}
   	public function post_vaild(){
   		// print_r($_POST);
   		$content = file_get_contents("php://input");
   		$res = simplexml_load_string($content);
   		// dd($res);/
   		$str = $time.$content."\n";
   		file_put_contents("logs/wx_event.log",$str,FILE_APPEND);
   		
   	}
}
