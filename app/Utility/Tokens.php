<?php
namespace App\Utility;
use App\Models\MobileToken;


class  Tokens {


    public function __construct()
    {

    }

    public function CreateToken($user_id){
        $token = md5($user_id.time());
        $m = new MobileToken();
        $m->user_id = $user_id;
        $m->token = $token;
        $m->expire_time = time() + strtotime('+1 hour');
        $m->save();
        return $m;
    }


    public function checkToken($token){
        $find = MobileToken::where('token',$token)->first();
        if($find){


         return $find;
        }

        return false;
    }

    public function removeToken($token){
        $find = MobileToken::where('token',$token)->delete();

        return $find;
    }


}
