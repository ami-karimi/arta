<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function sign_in(Request $request){
        if($request->username == ""){
            return response()->json(['error' => true, 'result' => 'لطفا نام کاربری را وارد نمایید!'],403);
        }
        if($request->password == ""){
            return response()->json(['error' => true, 'result' => 'لطفا کلمه عبور را وارد نمایید!'],403);
        }

        $findUser = User::where('username',$request->username)->where('password',$request->password)->first();
        if($findUser){
            if(!$findUser->is_enabled){
                return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!']);
            }



        }

        return response()->json(['error' => true, 'result' => 'حساب کابری شما یافت نشد!']);


    }
}
