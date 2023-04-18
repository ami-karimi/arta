<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserCollection;
use App\Models\User;
use App\Models\RadPostAuth;
use App\Models\Groups;
use Illuminate\Http\Request;
use App\Models\AcctSaved;
use App\Http\Requests\StoreSingleUserRequest;
use App\Http\Requests\EditSingleUserRequest;
use Morilog\Jalali\Jalalian;
use App\Models\RadAcct;
use App\Models\Ras;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index(){

        return new UserCollection(User::where('role','user')->orderBy('id','DESC')->paginate(10));
    }
    public function create(StoreSingleUserRequest $request){


        $req_all = $request->all();
        AcctSaved::create($request->only(['username','password','creator']));

        $findGroup = Groups::where('id',$request->group_id)->first();
        if($findGroup->expire_type !== 'no_expire'){
            if($findGroup->expire_type == 'minutes'){
                $req_all['exp_val_minute'] = $findGroup->expire_value;
            }elseif($findGroup->expire_type == 'month'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 43800);
            }elseif($findGroup->expire_type == 'days'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
            }elseif($findGroup->expire_type == 'hours'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
            }elseif($findGroup->expire_type == 'year'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
            }
        }

        $req_all['multi_login'] = $findGroup->multi_login;
        $req_all['expire_value'] = $findGroup->expire_value;
        $req_all['expire_type'] = $findGroup->expire_type;
        $req_all['expire_set'] = 0;


        User::create($req_all);

        return response()->json(['status' => true,'message' => 'کاربر با موفقیت اضافه شد!']);
    }
    public function edit(EditSingleUserRequest $request,$id){
        $find = User::where('id',$id)->first();
        if(!$find){
            return;
        }
        $exp_val_minute = $find->exp_val_minute;

        if($request->group_id !== $find->group_id){
            $findGroup = Groups::where('id',$request->group_id)->first();
            if($findGroup->expire_type !== 'no_expire'){
                if($findGroup->expire_type == 'minutes'){
                    $exp_val_minute = $findGroup->expire_value;
                }elseif($findGroup->expire_type == 'month'){
                    $exp_val_minute = floor($findGroup->expire_value * 43800);
                }elseif($findGroup->expire_type == 'days'){
                    $exp_val_minute = floor($findGroup->expire_value * 1440);
                }elseif($findGroup->expire_type == 'hours'){
                    $exp_val_minute = floor($findGroup->expire_value * 60);
                }elseif($findGroup->expire_type == 'year'){
                    $exp_val_minute = floor($findGroup->expire_value * 525600);
                }
            }
        }

        $expire_date = false;
        if($find->first_login !== NULL){
            $expire_date = Carbon::parse($find->first_login)->addMinutes($exp_val_minute)->toDateTimeString();
        }

        $is_enabled = 1;
        if(!$request->is_enabled){
            $is_enabled = 0;
        }

        $find->update($request->only(['group_id','name','creator','multi_login','password','username']));
        $find->exp_val_minute = $exp_val_minute;
        $find->is_enabled = $is_enabled;

        if($expire_date){
            $find->expire_date = $expire_date;
            $find->expire_set = 1;
            $find->save();
        }
        return response()->json(['status' => true,'message' => 'کاربر با موفقیت بروزرسانی شد!']);
    }
    public function ReChargeAccount($username){
        $findUser = User::where('username',$username)->first();
        if(!$findUser){
            return response()->json(['status' => false,'message' => 'کاربر یافت نشد!']);
        }
        $findUser->expire_set = 0;
        $findUser->first_login = NULL;
        $findUser->expire_date = NULL;
        $findUser->save();

        return response()->json(['status' => false,'message' => 'کاربر با نام کاربری '.$findUser->username." با موفقیت شارژ شد."]);


    }
    public function show($id){
        $userDetial = User::where('id',$id)->first();
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



}
