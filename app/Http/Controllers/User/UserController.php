<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RadAuthAcctCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RadAcct;
use Morilog\Jalali\Jalalian;
use App\Models\RadPostAuth;

class UserController extends Controller
{
   public function index(){
       $findUser = User::where('id',auth()->user()->id)->first();
       if(!$findUser){
           return response()->json(['status' => false,'message' => 'حساب کاربری یافت نشد!'],403);
       }

       $leftTime = ($findUser->expire_date !== NULL ? Carbon::now()->diffInDays($findUser->expire_date, false) : false);
       $fulldate = 0;
       if($findUser->expire_type == 'month'){
           $fulldate = $findUser->expire_value * 30;
       }
       if($findUser->expire_type == 'days'){
           $fulldate = $findUser->expire_value * 1;
       }

       $preg = 0;
       if($leftTime){
           if($leftTime > 0 && $leftTime > 0 && $fulldate > 0){
               $preg = round(floor(($leftTime * 100 ) /  $fulldate));
           }
       }

       $lastOnline = RadAcct::where('username',$findUser->username)->orderBy('radacctid','DESC')->first();
       $onlineCount = RadAcct::where('username',$findUser->username)->where('acctstoptime',NULL)->count();
       return  response()->json([
           'status'=> true,
           'user' =>  [
               'id' => $findUser->id,
               'username' => $findUser->username,
               'password' => $findUser->password,
               'name' => $findUser->name,
               'is_enabled'=> $findUser->is_enabled,
               'group_id'=> $findUser->group_id,
               'group' => ($findUser->group ? $findUser->group->name : false),
               'multi_login' => $findUser->multi_login,
               'first_login' =>($findUser->first_login !== NULL ? Jalalian::forge($findUser->first_login)->__toString() : false),
               'account_status' =>  ($findUser->isOnline ? 'online': 'offline'),
               'time_left' => $leftTime,
               'last_online' => ($lastOnline ? Jalalian::forge($lastOnline->acctupdatetime)->__toString() : false),
               'preg_left' => $preg,
               'expire_date' => ($findUser->expire_date !== NULL ? Jalalian::forge($findUser->expire_date)->__toString() : false),
               'last_connect' => ($lastOnline ? $lastOnline->servername->name : false),
               'online_count' => $onlineCount,
           ]
       ]);
   }

   public function edit_password(Request $request){
       if(!$request->password){
           return response()->json([
               'status' => false,
               'message' => 'لطفا کلمه عبور جدید را وارد نمایید!'
           ],403);
       }
       if(strlen($request->password) < 4){
           return response()->json([
               'status' => false,
               'message' => 'کلمه عبور بایستی حداقل 4 کاراکتر باشد!'
           ],403);
       }
       if($request->password !== $request->password_confirm ){
           return response()->json([
               'status' => false,
               'message' => 'کلمه عبور جدید با هم مطابقت ندارند!'
           ],403);
       }
       $findUser = User::where('id',auth()->user()->id)->first();
       if(!$findUser){
           return response()->json(['status' => false,'message' => 'حساب کاربری یافت نشد!'],403);
       }
       $findUser->password = $request->password;
       $findUser->save();
       return response()->json(['status' => false,'message' => 'کله عبور با موفقیت بروزرسانی شد!']);
   }


   public function auth_log(Request $request){
       $radLog =  new RadPostAuth();
       $radLog = $radLog->where('username',$request->user()->username);

       return new RadAuthAcctCollection($radLog->orderBY('id','DESC')->paginate(5));
   }
}
