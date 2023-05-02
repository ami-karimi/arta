<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdminActivityCollection;
use App\Http\Resources\Api\AcctSavedCollection;
use App\Http\Resources\Api\UserCollection;
use App\Http\Resources\Api\ActivityCollection;
use App\Models\Activitys;
use App\Models\User;
use App\Models\RadPostAuth;
use App\Models\Groups;
use App\Utility\SaveActivityUser;
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
    public function index(Request $request){

        $user =  User::where('role','user');
        if($request->SearchText){
            $user->where('name', 'LIKE', "%$request->SearchText%")
            ->orWhere('username', 'LIKE', "%$request->SearchText%");
        }
        if($request->creator){
            $user->where('creator',$request->creator);
        }
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

        return new UserCollection($user->orderBy('id','DESC')->paginate(50));
    }
    public function create(StoreSingleUserRequest $request){

        $userNameList = [];

        $type = 'single';
        if(strpos($request->username,'{')) {


            $type = 'group';
            $pos = strpos($request->username,'{');
            $pos2 = strlen($request->username);
            $rem = substr($request->username,$pos,$pos2);
            $replace = str_replace(['{','}'],'',substr($request->username,$pos,$pos2));
            $exp_count = explode('-',$replace);
            $start = (int) $exp_count[0];
            $end = (int) $exp_count[1] + 1;
            $userNames = str_replace($rem,'',$request->username);
        }else{
            array_push($userNameList,['username' => $request->username,'password' => $request->password]);
        }

        if($type == 'group'){
            for ($i= $start; $i < $end;$i++){
                $buildUsername = $userNames.$i;
                $findUsername = User::where('username',$buildUsername)->first();
                if($findUsername){
                    return response()->json(['status' => false,'نام کاربری '.$buildUsername.' موجود میباشد!']);
                }
                $password = $request->password;
                if($request->random_password){
                    $password = substr(rand(0,99999),0,(int) $request->random_password_num);
                }

                array_push($userNameList,['username' => $buildUsername ,'password'  => $password]);
            }
        }

        foreach ($userNameList as $row) {
            $req_all = $request->all();
            $req_all['username'] = $row['username'];
            $req_all['password'] = $row['password'];
            $req_all['groups'] = $request->username;
            $req_all['creator'] = $request->creator;

            AcctSaved::create($req_all);

            $findGroup = Groups::where('id', $request->group_id)->first();
            if ($findGroup->expire_type !== 'no_expire') {
                if ($findGroup->expire_type == 'minutes') {
                    $req_all['exp_val_minute'] = $findGroup->expire_value;
                } elseif ($findGroup->expire_type == 'month') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 43800);
                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                }
            }

            $req_all['multi_login'] = $findGroup->multi_login;
            $req_all['expire_value'] = $findGroup->expire_value;
            $req_all['expire_type'] = $findGroup->expire_type;
            $req_all['expire_set'] = 0;


            User::create($req_all);

        }

        return response()->json(['status' => true, 'message' => 'کاربر با موفقیت اضافه شد!']);

    }
    public function edit(EditSingleUserRequest $request,$id){
        $find = User::where('id',$id)->first();
        if(!$find){
            return;
        }
        $lst_name = $find->creator_name->name;
        $change_owner = false;
        if($request->creator !== $find->creator){
            $change_owner = true;
        }

        $exp_val_minute = $find->exp_val_minute;
        $findGroup = Groups::where('id',$request->group_id)->first();

        if($findGroup->id !== $find->group_id){
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

            SaveActivityUser::send($find->id,auth()->user()->id,'change_group',['last' => $find->group->name,'new' => $findGroup->name]);

            $find->expire_value = $findGroup->expire_value;
            $find->expire_type = $findGroup->expire_type;

        }

        if($request->change_expire_type){
            if($request->expire_type == 'minutes'){
                $exp_val_minute = $request->expire_value;
            }elseif($request->expire_type == 'month'){
                $exp_val_minute = floor(((int) $request->expire_value) * 43800);
            }elseif($request->expire_type == 'days'){
                $exp_val_minute = floor(((int) $request->expire_value) * 1440);
            }elseif($request->expire_type == 'hours'){
                $exp_val_minute = floor(((int) $request->expire_value) * 60);
            }elseif($request->expire_type == 'year'){
                $exp_val_minute = floor(((int) $request->expire_value) * 525600);
            }
            $find->expire_value = $request->expire_value;
            $find->expire_type = $request->expire_type;
            SaveActivityUser::send($find->id,auth()->user()->id,'change_expire',['type' => $request->expire_type,'value' => $request->expire_value]);

        }






        $expire_date = false;
        if($find->first_login !== NULL){
            $expire_date = Carbon::parse($find->first_login)->addMinutes($exp_val_minute)->toDateTimeString();
        }

        if($find->is_enabled !== ($request->is_enabled == true ? 1 : 0)){
            $find->is_enabled = ($request->is_enabled === true ? 1 : 0);
            SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => $find->is_enabled]);
        }

        if($request->password !== $find->password){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_password',['new' => $request->password,'last' => $find->password]);
            $find->password = $request->password;
        }
        if($request->username !== $find->username){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_multi_login',['new' => $request->username,'last' => $find->username]);
            $find->username = $request->username;
        }
        if($request->multi_login !== $find->multi_login){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_multi_login',['last' => $find->multi_login,'new' => $request->multi_login]);
        }


        $find->update($request->only(['group_id','name','creator','multi_login']));
        $find->exp_val_minute = $exp_val_minute;
        if($expire_date){
            $find->expire_date = $expire_date;
            $find->expire_set = 1;
        }

        $find->save();

        if($change_owner) {
            $cr_name = User::where('id',$find->creator)->first();
            if($cr_name) {
                SaveActivityUser::send($find->id, auth()->user()->id, 'change_owner', ['last' => $lst_name, 'new' => $cr_name->name]);
            }
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

        SaveActivityUser::send($findUser->id,auth()->user()->id,'re_charge');

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
                'expire_type' => $userDetial->expire_type,
                'expire_value' => $userDetial->expire_value,
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
        $find = User::where('id',$id)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],403);
        }
        return new ActivityCollection(Activitys::where('user_id',$find->id)->orderBy('id','DESC')->paginate(5));
    }
    public function groupdelete(Request $request){

        foreach ($request->user_ids as $user_id){
            User::destroy($user_id);
        }


        return response()->json([
            'status' => true,
            'message' => 'کاربران انتخابی با موفقیت حذف شدند!'
        ]);

    }
    public function group_recharge(Request $request){

        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->first();
            if($find) {
                $find->expire_date = NULL;
                $find->first_login = NULL;
                $find->expire_set = 0;

                SaveActivityUser::send($find->id,auth()->user()->id,'re_charge');

                $find->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'کاربران انتخابی با موفقیت شارژ شدند!'
        ]);

    }
    public function group_deactive(Request $request){

        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->first();
            if($find) {
                $find->is_enabled = 0;
                SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => 0]);

                $find->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'کاربران انتخابی با موفقیت غیرفعال شدند!'
        ]);

    }
    public function group_active(Request $request){

        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->first();
            if($find) {
                $find->is_enabled = 1;
                SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => 1]);

                $find->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'کاربران انتخابی با موفقیت فعال شدند!'
        ]);

    }
    public function change_group_id(Request $request){
        $findGroup = Groups::where('id',$request->group_id)->first();
        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->first();
            if($find){

            $exp_val_minute = $find->exp_val_minute;

            if($request->group_id !== $find->group_id){
                SaveActivityUser::send($find->id,auth()->user()->id,'change_group',['last' => $find->group->name,'new' => $findGroup->name]);

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
                $find->exp_val_minute = $exp_val_minute;
                $find->multi_login = $findGroup->multi_login;
                $find->expire_type = $findGroup->expire_type;
                $find->expire_value = $findGroup->expire_value;
                $find->group_id = $findGroup->id;

            }

            $expire_date = false;
            if($find->first_login !== NULL){
                $expire_date = Carbon::parse($find->first_login)->addMinutes($exp_val_minute)->toDateTimeString();
            }

             if($expire_date){
                $find->expire_date = $expire_date;
                $find->expire_set = 1;
                $find->save();
               }

                $find->save();
          }
        }

        return response()->json([
            'status' => true,
            'message' => 'تغییر گروه کاربری با موفقیت انجام شد!'
        ]);

    }

    public function change_creator(Request $request){
        $findCreator = User::where('id',$request->creator)->first();
        if(!$findCreator){
            return response()->json([
                'status' => true,
                'message' => 'خطا! ایجاد کننده یافت نشد'
            ]);
        }
        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->first();
            if($find){
              $lst_name = $find->creator_name->name;
              $change_owner = false;
              if($findCreator->id !== $find->creator){
                  $change_owner = true;
              }
              $find->creator = $findCreator->id;
              $find->save();
              if($change_owner) {
                  SaveActivityUser::send($find->id, auth()->user()->id, 'change_owner', ['last' => $lst_name, 'new' => $findCreator->name]);
              }
           }
        }

        return response()->json([
            'status' => true,
            'message' => 'تغییر ایجاد کننده  با موفقیت انجام شد!'
        ]);

    }

    public function getActivityAll(Request $request){

        $activitys = new Activitys();
        if($request->user_id){
            $activitys = $activitys->where('user_id',$request->user_id);
        }
        if($request->by){
            $activitys = $activitys->where('by',$request->by);
        }
        $per_page = 10;
        if($request->per_page){
            $per_page = (int) $request->per_page;
        }

        return new AdminActivityCollection($activitys->orderBy('id','DESC')->paginate($per_page));
    }
    public function AcctSaved(Request $request){
        $savedAccounts = AcctSaved::with('by')->select('*')->orderBy('id','DESC')->groupBy('groups');

        return new AcctSavedCollection($savedAccounts->paginate(20));
    }
    public function AcctSavedView(Request $request){

        $findSaved = AcctSaved::where('id',$request->id)->first();
        if(!$findSaved){
            return response()->json([
                'status' => false,
                'message' => 'اکانت یافت نشد!'
            ],403);
        }
        $savedAccounts = AcctSaved::where('groups',$findSaved->groups);

        return new AcctSavedCollection($savedAccounts->paginate(50));
    }

    public function kill_user(Request $request){
        $find = RadAcct::where('radacctid',$request->radacctid)->first();
        if(!$find){
            return response()->json([
                'status' => false,
                'message' => 'نشست یافت نشد!'
            ],403);
        }


        $find->acctstoptime = Carbon::now('Asia/Tehran');
        $find->save();
        return response()->json([
            'status' => false,
            'message' => 'عملیات با موفقیت انجام شد!'
        ]);
    }


}
