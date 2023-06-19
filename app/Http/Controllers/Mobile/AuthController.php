<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Utility\Tokens;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

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

            $token = new Tokens();
            $ts = $token->CreateToken($findUser->id);
            $expire_date = $findUser->expire_date ;
            if(!$findUser->expire_set){
                $findUser->expire_set = 1;
                $findUser->expire_date = Carbon::now()->addMinutes($findUser->exp_val_minute);
                $expire_date = $findUser->expire_date;
                $findUser->first_login = Carbon::now();
                $findUser->save();
            }

            return  response()->json([
               'error' => false,
               'result' =>  [
                  'token' => $ts->token,
                  'email' => $findUser->username,
                  'password' => $findUser->password,
                  'isPremium' => 0,
                  'subscriptionEndDate' => Jalalian::forge($expire_date)->__toString(),
                  'left_day' => Carbon::now()->diffInDays($expire_date, false),
                 ]
            ]);
        }

        return response()->json(['error' => true, 'result' => 'حساب کابری شما یافت نشد!']);


    }

    public function is_valid_token(Request $request){
        if(!$request->token){
            return response()->json(['error' => true, 'result' => 'توکن یافت نشد'],403);
        }
        $token = new Tokens();
        $check = $token->checkToken($token);
        if(!$check){
            return response()->json(['error' => true, 'result' => 'توکن نامعتبر میباشد '],403);
        }
        $findUser = User::where('username',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['error' => true, 'result' => 'کاربر یافت تشد! '],403);
        }
        if(!$findUser->is_enabled){
            return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!']);
        }
        $expire_date = $findUser->expire_date ;

        return  response()->json([
            'error' => false,
            'result' =>  [
                'token' => $check->token,
                'email' => $findUser->username,
                'password' => $findUser->password,
                'isPremium' => 0,
                'subscriptionEndDate' => Jalalian::forge($expire_date)->__toString(),
                'left_day' => Carbon::now()->diffInDays($expire_date, false),
            ]
        ]);

    }
}
