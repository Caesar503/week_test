<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WXController extends Controller
{
   	public function get_vaild(){
   		echo $_GET['echostr'];
   	}
}
