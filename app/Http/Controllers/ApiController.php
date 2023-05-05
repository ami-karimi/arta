<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utility\V2rayFrontApi;
use App\Utility\V2rayApi;
class ApiController extends Controller
{
    public function index(){
        $login = new V2rayApi("128.140.91.159","4454","amirtld","Amir@###1401");

        $data = $login->status();

        print_r($data);
    }
}
