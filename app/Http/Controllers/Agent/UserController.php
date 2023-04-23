<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgentUserCollection;
use App\Http\Resources\Api\ActivityCollection;
use App\Models\Groups;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use App\Utility\SaveActivityUser;
use App\Models\Activitys;

class UserController extends Controller
{
    public function index(Request $request){
        $user =  User::where('role','user');
        if($request->SearchText){
            $user->where('name', 'LIKE', "%$request->SearchText%")
                ->orWhere('username', 'LIKE', "%$request->SearchText%");
        }
        $user->where('creator',auth()->user()->id);

        if($request->group_id){
            $user->where('group_id',$request->group_id);
        }
        if($request->is_enabled == 'active'){
            $user->where('is_enabled',1);
        }elseif($request->is_enabled == 'deactive'){
            $user->where('is_enabled',0);
        }

        if($request->online_status){
            if($request->online_status == 'online') {
                $user->whereHas('raddacct', function ($query) {
                    return $query->where('acctstoptime',NULL);
                });
            }elseif($request->online_status == 'offline'){
                $user->whereHas('raddacct', function ($query) {
                    return $query->where('acctstoptime','!=',NULL);
                });
            }
        }

        if($request->expire_date){
            if($request->expire_date == 'expired'){
                $user->where('expire_date','<=',Carbon::now('Asia/Tehran'));
            }
            if($request->expire_date == 'expire_5day'){
                $user->where('expire_date','<=',Carbon::now('Asia/Tehran')->addDay(5));
            }
        }

        return new AgentUserCollection($user->orderBy('id','DESC')->paginate(50));
    }

    public function group_deactive(Request $request){

        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->where('creator',auth()->user()->id)->first();
            if($find) {
                $find->is_enabled = 0;
                $find->save();
            }else {
                return response()->json([
                    'status' => true,
                    'message' => 'کاربر با شناسه ' . $user_id . ' جزو کاربران    شما نمیباشد!'
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'کاربران انتخابی با موفقیت غیرفعال شدند!'
        ]);

    }
    public function group_active(Request $request){

        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->where('creator',auth()->user()->id)->first();
            if($find) {
                $find->is_enabled = 1;
                $find->save();
            }else {
                return response()->json([
                    'status' => true,
                    'message' => 'کاربر با شناسه ' . $user_id . ' جزو کاربران    شما نمیباشد!'
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'کاربران انتخابی با موفقیت فعال شدند!'
        ]);

    }

    public function show($id){
        $userDetial = User::where('id',$id)->where('creator',auth()->user()->id)->first();
        if(!$userDetial){
            return response()->json(['status' => false,'message' => 'کاربر یافت نشد!']);
        }

        return  response()->json([
            'status' => true,
            'user' => [
                'id' => $userDetial->id,
                'name' => $userDetial->name,
                'username' => $userDetial->username,
                'creator' => $userDetial->creator,
                'multi_login' => $userDetial->multi_login,
                'creator_detial' => ($userDetial->creator_name ? ['name' => $userDetial->creator_name->name ,'id' =>$userDetial->creator_name->id] : [] ) ,
                'password' => $userDetial->password,
                'group' => ($userDetial->group ? $userDetial->group->name : '---'),
                'group_id' => $userDetial->group_id,
                'expire_date' => ($userDetial->expire_date !== NULL ? Jalalian::forge($userDetial->expire_date)->__toString() : '---'),
                'left_time' => ($userDetial->expire_date !== NULL ? Carbon::now()->diffInDays($userDetial->expire_date, false) : '---'),
                'status' => ($userDetial->isOnline ? 'online': 'offline'),
                'is_enabled' => $userDetial->is_enabled ,
                'created_at' => Jalalian::forge($userDetial->created_at)->__toString(),
            ],
            'groups' => Groups::select('name','id')->get(),
            'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
        ]);


    }

    public function getActivity($id){
        $find = User::where('id',$id)->where('creator',auth()->user()->id)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],403);
        }
        return new ActivityCollection(Activitys::where('user_id',$find->id)->orderBy('id','DESC')->paginate(5));
    }
    public function edit(Request $request,$id){
        $find = User::where('id',$id)->where('creator',auth()->user()->id)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],404);
        }
        if(!$request->password){
            return response()->json([
                'message' => 'کلمه عبور کاربر نباید خالی باشد!'
            ],403);
        }
        if(strlen($request->password) < 4){
            return response()->json([
                'message' => 'کلمه عبور کاربر حداقل بایستی 4 کاراکتر باشد!'
            ],403);
        }


        if($find->is_enabled !== ($request->is_enabled == true ? 1 : 0)){

            $find->is_enabled = ($request->is_enabled === true ? 1 : 0);
            SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => $find->is_enabled]);
        }

        if($request->password !== $find->password){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_password',['new' => $request->password,'last' => $find->password]);
            $find->password = $request->password;
        }

        $find->save();
        return response()->json([
            'message' => 'کاربر با موفقیت بروزرسانی شد!'
        ]);
    }
}
