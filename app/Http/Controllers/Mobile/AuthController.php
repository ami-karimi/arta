<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use http\Env\Response;
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
               'success' => true,
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
}
