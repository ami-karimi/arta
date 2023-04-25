<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{



    public function login_user(Request $request){
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $findUser = User::select(['name','username','password','expire_date','is_enabled','group_id','id'])->where('username',$request->username)->where('password',$request->password)->where('role','user')->first();
        if(!$findUser){
            return response()->json(['status' => false,'message' => 'نام کاربری یا کلمه عبور اشتباه میباشد!'],403);
        }
        $token = Auth::login($findUser);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'user' => $findUser,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);


    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        if(!Auth::user()->is_enabled){
            return response()->json([
                'status' => 'error',
                'message' => 'حساب کاربری شما مسدود میباشد لطفا جهت رفع این مشکل با پشتیبانی تماس بگیرید',
            ], 403);
        }
        $user = Auth::user();
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }


}
