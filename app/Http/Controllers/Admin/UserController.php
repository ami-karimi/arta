<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdminActivityCollection;
use App\Http\Resources\Api\AcctSavedCollection;
use App\Http\Resources\Api\UserGraphsResource;
use App\Http\Resources\Api\UserCollection;
use App\Http\Resources\Api\ActivityCollection;
use App\Http\Resources\WireGuardConfigCollection;
use App\Models\Activitys;
use App\Models\Financial;
use App\Models\User;
use App\Models\RadPostAuth;
use App\Models\Groups;
use App\Models\UserGraph;
use App\Utility\SaveActivityUser;
use Illuminate\Http\Request;
use App\Models\AcctSaved;
use App\Http\Requests\StoreSingleUserRequest;
use App\Http\Requests\EditSingleUserRequest;
use Morilog\Jalali\Jalalian;
use App\Models\RadAcct;
use App\Models\Ras;
use Carbon\Carbon;
use App\Utility\V2rayApi;
use App\Http\Controllers\Admin\MonitorigController;
use App\Utility\WireGuard;
use App\Models\WireGuardUsers;
use App\Utility\SmsSend;
use App\Http\Controllers\Admin\V2rayController;
use App\Utility\V2raySN;

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
                $user->where('expire_date','<=',Carbon::now('Asia/Tehran')->addDay(5))->where('expire_date','>=',Carbon::now('Asia/Tehran')->subDays(5));
            }
            if($request->expire_date == 'expire_20day'){
                $user->where('expire_set',1)->where('expire_date','<=',Carbon::now('Asia/Tehran')->subDays(45));
            }
            if($request->expire_date == 'not_use'){
                $user->where('expire_set',0);
            }
        }

        if($request->type_service){
            $user->where('service_group',$request->type_service);
        }

        return new UserCollection($user->orderBy('id','DESC')->paginate(50));
    }
    public function create(StoreSingleUserRequest $request){
        if($request->account_type){
            return $this->CreateWireGuardAccount($request);
        }

        if($request->service_group == 'v2ray'){
            $req_all = $request->all();
            $findUsername = User::where('username',$request->username)->first();
            if($findUsername){
                return response()->json(['status' => false,'نام کاربری '.$request->username.' موجود میباشد!']);
            }

            if(!$request->v2ray_location){
                return response()->json(['message' => 'لطفا لوکیشن سرور را انتخاب نمایید!'],403);
            }

            if(!$request->protocol_v2ray){
                return response()->json(['message' => 'لطفا  پرتکل را انتخاب نمایید'],403);
            }
            $findGroup = Groups::where('id', $request->group_id)->first();
            if(!$findGroup){
                return response()->json(['message' => 'گروه مورد نظر یافت نشد!'],403);
            }

            $findLocation = Ras::where('server_type','v2ray')->where('is_enabled',1)->where('id',$request->v2ray_location)->first();
            if(!$findLocation){
                return response()->json(['message' => 'لوکیشن یافت نشد!'],403);
            }
            $V2ray = new V2raySN(
                [
                    'HOST' =>  $findLocation->ipaddress,
                    "PORT" =>  $findLocation->port_v2ray,
                    "USERNAME" => $findLocation->username_v2ray,
                    "PASSWORD"=> $findLocation->password_v2ray,
                    "CDN_ADDRESS"=> $findLocation->cdn_address_v2ray,
                ]
            );
            if($V2ray->error['status']){
                return response()->json(['message' => $V2ray->error['message']],403);
            }

            $password = $request->password;
            if($request->random_password){
                $password = substr(rand(0,99999),0,(int) $request->random_password_num);
            }

            $expire_date = 0;

            if($findGroup->expire_value > 0){
                $expire_date = $findGroup->expire_value;
                if($findGroup->expire_type !== 'days'){
                    $expire_date *= 30;
                }
            }
            if ($findGroup->expire_type !== 'no_expire') {
                if ($findGroup->expire_type == 'minutes') {
                    $req_all['exp_val_minute'] = $findGroup->expire_value;

                } elseif ($findGroup->expire_type == 'month') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 43800);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }

                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }
                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }

                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }

                }
            }

            $req_all['expire_type'] = $findGroup->expire_type;
            $req_all['expire_set'] = 0;

            $add_client = $V2ray->add_client((int) $request->protocol_v2ray,$request->username,(int) $findGroup->multi_login,$findGroup->group_volume,$expire_date,true);

            if(!$add_client['success']){
                return response()->json(['message' => $add_client['msg']],403);
            }
            $client = $V2ray->get_user((int) $request->protocol_v2ray,$request->username);

            $req_all['username'] = $request->username;
            $req_all['v2ray_config_uri'] = $client['user']['url'];
            $req_all['last_update'] = Carbon::now();
            $req_all['password'] = $password;
            $req_all['groups'] = $request->username;
            $req_all['creator'] = $request->creator;
            $req_all['uuid_v2ray'] = $add_client['uuid'];

            User::create($req_all);
            return response()->json(['message' => 'کاربر با موفقیت ایجاد شد!']);

        }

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
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }

                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }
                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }

                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage'] = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024));
                    }

                }
            }

            $req_all['multi_login'] = $findGroup->multi_login;


            $req_all['expire_value'] = $findGroup->expire_value;
            $req_all['expire_type'] = $findGroup->expire_type;
            $req_all['expire_set'] = 0;

            if($findGroup->group_type == 'volume') {
                $req_all['multi_login'] = 5;
                $req_all['max_usage'] =@round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
            }

            User::create($req_all);



        }

        return response()->json(['status' => true, 'message' => 'کاربر با موفقیت اضافه شد!']);

    }
    public function CreateWireGuardAccount(Request $request){

        if($request->for_user){
            $user = User::where('id',$request->for_user)->first();
            if(!$user){
                return  response()->json(['status' => true,'message' => 'کاربر یافت نشد!'],403);
            }
            $create_wr = new WireGuard($request->server_id, $user->username.rand(1,5));

            $user_wi = $create_wr->Run();
            if($user_wi['status']) {
                $saved = new  WireGuardUsers();
                $saved->profile_name = $user_wi['config_file'];
                $saved->user_id = $user->id;
                $saved->server_id = $request->server_id;
                $saved->public_key = $user_wi['client_public_key'];
                $saved->user_ip = $user_wi['ip_address'];
                $saved->save();
                exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");

            }

            return  response()->json(['status' => true,'message' => 'کانفیگ با موفقیت ایجاد شد']);
        }

        $userNameList = [];

        if(!$request->server_id){
            response()->json(['status' => false, 'message' => 'لطفا سرور مقصد را انتخاب نمایید'],403);
        }
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
                    $req_all['max_usage']  = @round(((((int) 100 *1024) * 1024) * 1024 ) * $findGroup->expire_value) * $findGroup->multi_login;
                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
                    $req_all['max_usage']  = @round(1999999999.9999998  * $findGroup->expire_value) * $findGroup->multi_login;

                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                    $req_all['max_usage']  = @round(400000000  * $findGroup->expire_value) * $findGroup->multi_login;

                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                    $req_all['max_usage']  = @round(90000000000  * $findGroup->expire_value) * $findGroup->multi_login;

                }
            }

            $req_all['multi_login'] = 1;
            $req_all['service_group'] = 'wireguard';

            if($findGroup->group_type == 'expire') {
                $req_all['expire_value'] = $findGroup->expire_value;
                $req_all['expire_type'] = $findGroup->expire_type;
                $req_all['expire_date'] = Carbon::now()->addMinutes($req_all['exp_val_minute']);
                $req_all['first_login'] = Carbon::now();
                $req_all['expire_set'] = 1;
            }
            if($findGroup->group_type == 'volume') {
                $req_all['multi_login'] = 1;
                $req_all['max_usage'] =@round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
            }


                $user = User::create($req_all);
              if($user) {
                $create_wr = new WireGuard($request->server_id, $req_all['username']);

                    $user_wi = $create_wr->Run();

                  if($user_wi['status']) {
                      $saved = new  WireGuardUsers();
                      $saved->profile_name = $user_wi['config_file'];
                      $saved->user_id = $user->id;
                      $saved->client_private_key  =  $user_wi['client_private_key'];
                      $saved->server_id = $request->server_id;
                      $saved->public_key = $user_wi['client_public_key'];
                      $saved->user_ip = $user_wi['ip_address'];
                      $save = $saved->save();
                      if(!$save){
                          $user->delete();
                          return response()->json(['status' => true, 'message' => 'کاربر ایجاد نشد مجددا تلاش نمایید!','ss'=> $user_wi,'sss'=>$save],403);

                      }
                      exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");

                }else{
                      $user->delete();
                  }
            }
        }


        return response()->json(['status' => true, 'message' => 'کاربر با موفقیت اضافه شد!']);
    }
    public function edit(EditSingleUserRequest $request,$id){
        $find = User::where('id',$id)->first();
        if(!$find){
            return;
        }
        if($request->phonenumber){
            if(!preg_match('/^(09){1}[0-9]{9}+$/', $request->phonenumber)){
                return response()->json(['message' => 'لطفا یک شماره تماس معتبر وارد نمایید همراه با 0 باشد!'],403);
            }
        }
        $lst_name = $find->creator_name->name;
        $change_owner = false;
        if($request->creator !== $find->creator){
            $change_owner = true;
        }

        $login = false;




        if($request->service_group){
            $find->service_group = $request->service_group;
        }

        if($find->service_group == 'v2ray'){
            $login = new V2raySN(
                [
                    'HOST' => $find->v2ray_server->ipaddress,
                    "PORT" => $find->v2ray_server->port_v2ray,
                    "USERNAME" => $find->v2ray_server->username_v2ray,
                    "PASSWORD" => $find->v2ray_server->password_v2ray,
                    "CDN_ADDRESS"=> $find->v2ray_server->cdn_address_v2ray,

                ]
            );
            if($login->error['status']){
                return response()->json(['status' => false,'message' => 'خطا در برقراری ارتباط با سرور V2ray مجددا تلاش نمایید'],502);
            }
            $v2_current = $login->get_client($find->username);
        }


        $exp_val_minute = $find->exp_val_minute;
        $findGroup = Groups::where('id',$request->group_id)->first();

        if($findGroup->id !== $find->group_id){

            $find->group_id = $findGroup->id;

            if($findGroup->expire_type !== 'no_expire'){
                if($findGroup->expire_type == 'minutes'){
                    $exp_val_minute = $findGroup->expire_value;
                }elseif ($findGroup->expire_type == 'month') {
                    $find->exp_val_minute = floor($findGroup->expire_value * 43800);
                    $find->max_usage  = @round(((((int) 100 *1024) * 1024) * 1024 )  * $findGroup->expire_value) * $findGroup->multi_login;
                } elseif ($findGroup->expire_type == 'days') {
                    $find->exp_val_minute = floor($findGroup->expire_value * 1440);
                    $find->max_usage  = @round(1999999999.9999998  * $findGroup->expire_value) * $findGroup->multi_login;
                } elseif ($findGroup->expire_type == 'hours') {
                    $find->exp_val_minute = floor($findGroup->expire_value * 60);
                    $find->max_usage  = @round(400000000  * $findGroup->expire_value) * $findGroup->multi_login;
                } elseif ($findGroup->expire_type == 'year') {
                    $find->exp_val_minute = floor($findGroup->expire_value * 525600);
                    $find->max_usage  = @round(90000000000  * $findGroup->expire_value) * $findGroup->multi_login;
                }
            }

            SaveActivityUser::send($find->id,auth()->user()->id,'change_group',['last' => $find->group->name,'new' => $findGroup->name]);


            $find->expire_value = $findGroup->expire_value;
            $find->expire_type = $findGroup->expire_type;

            if($findGroup->group_type == 'volume') {
                $find->multi_login = 5;
                $find->max_usage = @round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
            }

            if($find->service_group == 'v2ray') {
                if ($login) {
                    $login->update_client($find->uuid_v2ray, [
                        'service_id' => $find->protocol_v2ray,
                        'username' => $find->username,
                        'multi_login' => $find->group->multi_login,
                        'totalGB' => @round((((int)$findGroup->group_volume * 1024) * 1024) * 1024),
                        'expiryTime' => $v2_current['expiryTime'],
                        'enable' => $request->is_enabled,
                    ]);

                }
            }

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
            $find->is_enabled = ($request->is_enabled == true ? 1 : 0);
            SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => $find->is_enabled]);
            if($find->service_group == 'wireguard'){
                $AllConfig = WireGuardUsers::where('user_id',$find->id)->get();
                foreach ($AllConfig as $row){
                    $wireGuard = new WireGuard($row->server_id,$row->profile_name);
                    $wireGuard->ChangeConfigStatus($row->public_key, ($request->is_enabled == true ? 1 : 0));
                    $row->is_enabled = ($request->is_enabled === true ? 1 : 0);
                    $row->save();
                }

            }
            if($login){
                $login->update_client($find->uuid_v2ray, [
                    'service_id' => $find->protocol_v2ray,
                    'username' => $find->username,
                    'multi_login' => $find->group->multi_login,
                    'totalGB' => $v2_current['total'],
                    'expiryTime' => $v2_current['expiryTime'],
                    'enable' => $request->is_enabled,
                ]);

            }
        }

        if($request->password !== $find->password){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_password',['new' => $request->password,'last' => $find->password]);
            $find->password = $request->password;
        }
        if($request->username !== $find->username){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_multi_login',['new' => $request->username,'last' => $find->username]);
            $find->username = $request->username;

            if ($login) {
                $login->update_client($find->uuid_v2ray, [
                    'service_id' => $find->protocol_v2ray,
                    'username' => $request->username,
                    'multi_login' => $find->group->multi_login,
                    'totalGB' => @round((((int)$findGroup->group_volume * 1024) * 1024) * 1024),
                    'expiryTime' => $v2_current['expiryTime'],
                    'enable' => $request->is_enabled,
                ]);

            }

        }
        if($request->multi_login !== $find->multi_login){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_multi_login',['last' => $find->multi_login,'new' => $request->multi_login]);
        }

        if($request->phonenumber){
            $find->phonenumber = $request->phonenumber;
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
        $login = false;
        /*
        if($findUser->service_group == 'v2ray'){
            $login = new V2rayApi($findUser->v2ray_server->ipaddress,$findUser->v2ray_server->port_v2ray,$findUser->v2ray_server->username_v2ray,$findUser->v2ray_server->password_v2ray);
            if($login){
                $login->update($findUser->port_v2ray,['reset' => true]);
            }
        }
        */

        UserGraph::where('user_id',$findUser->id)->delete();


        if($findUser->group->expire_type !== 'no_expire'){

            if ($findUser->group->expire_type == 'minutes') {
                $findUser->exp_val_minute = $findUser->group->expire_value;
            } elseif ($findUser->group->expire_type == 'month') {
                $findUser->exp_val_minute = floor($findUser->group->expire_value * 43800);
                if($findUser->group->group_volume > 0){
                $findUser->max_usage  = @round(((((int) $findUser->group->group_volume *1024) * 1024) * 1024 )  * $findUser->group->expire_value) * $findUser->group->multi_login;
                    }
            } elseif ($findUser->group->expire_type == 'days') {
                $findUser->exp_val_minute = floor($findUser->group->expire_value * 1440);
                if($findUser->group->group_volume > 0) {
                    $findUser->max_usage = @round($findUser->group->group_volume * $findUser->group->expire_value) * $findUser->group->multi_login;
                }
            } elseif ($findUser->group->expire_type == 'hours') {
                $findUser->exp_val_minute = floor($findUser->group->expire_value * 60);
                if($findUser->group->group_volume > 0) {
                    $findUser->max_usage = @round($findUser->group->group_volume * $findUser->group->expire_value) * $findUser->group->multi_login;
                }
            } elseif ($findUser->group->expire_type == 'year') {
                $findUser->exp_val_minute = floor($findUser->group->expire_value * 525600);
                if($findUser->group->group_volume > 0) {
                    $findUser->max_usage = @round($findUser->group->group_volume * $findUser->group->expire_value) * $findUser->group->multi_login;
                }
           }

            if($findUser->service_group !== 'wireguard') {
                $findUser->expire_set = 0;
                $findUser->first_login = NULL;
                $findUser->expire_date = NULL;
                $findUser->expired = 0;
            }
            if($findUser->group->group_type == 'volume'){
                $findUser->multi_login = 5;
                $findUser->usage = 0;
                $findUser->download_usage = 0;
                $findUser->upload_usage = 0;
                $findUser->expire_set = 0;
                $findUser->first_login = NULL;
                $findUser->expire_date = NULL;
                $findUser->max_usage = @round((((int) $findUser->group->group_volume *1024) * 1024) * 1024 );
                $findUser->expired = 0;
            }
            if($findUser->service_group == 'wireguard') {

                $findUser->expire_value = $findUser->group->expire_value;
                $findUser->expire_type = $findUser->group->expire_type;
                $findUser->expire_date = Carbon::now()->addMinutes($findUser->exp_val_minute);
                $findUser->first_login = Carbon::now();
                $findUser->expire_set = 1;
                $findUser->expired = 0;
                if($findUser->wg){
                    $mik = new WireGuard($findUser->wg->server_id,'null');
                    $peers = $mik->getUser($findUser->wg->public_key);
                    if($peers['status']){
                        $status =  $mik->ChangeConfigStatus($findUser->wg->public_key,1);
                        if($status['status']) {
                            SaveActivityUser::send($findUser->id, auth()->user()->id, 'active_status', ['status' => 0]);
                        }
                    }
                }

            }
        }

        if($findUser->expire_date !== NULL) {
            $last_time_s = (int) Carbon::now()->diffInDays($findUser->expire_date, false);
            if ($last_time_s > 0) {
                $findUser->exp_val_minute += floor($last_time_s * 1440);
                SaveActivityUser::send($findUser->id,auth()->user()->id,'add_left_day',['day' => $last_time_s]);
            }
        }

        if($findUser->service_group == 'v2ray'){
            $login = new V2raySN(
                [
                    'HOST' => $findUser->v2ray_server->ipaddress,
                    "PORT" => $findUser->v2ray_server->port_v2ray,
                    "USERNAME" => $findUser->v2ray_server->username_v2ray,
                    "PASSWORD" => $findUser->v2ray_server->password_v2ray,
                    "CDN_ADDRESS"=> $findUser->v2ray_server->cdn_address_v2ray,
                ]
            );
            if($login->error['status']){
                return response()->json(['status' => false,'message' => 'خطا در برقراری ارتباط با سرور V2ray مجددا تلاش نمایید'],502);
            }
            $days = $findUser->group->expire_value;
            $tm = floor(microtime(true) * 1000);
            $v2_current = $login->get_client($findUser->username);
            $expire_time = ((int) $v2_current['expiryTime'] > 0 ? (int) $v2_current['expiryTime'] /1000 : 0);
            $left = 0;
            $max_usage = $findUser->max_usage;
            if($expire_time  > 0){
                $ex = date('Y-m-d H:i:s', $expire_time);
                $left = Carbon::now()->diffInDays($ex, false);
            }
            $left_Usage = $v2_current['total'];
            $left_Usage -= ($v2_current['up'] + $v2_current['down']);
            if($left_Usage > 0 && $left > 0) {
                SaveActivityUser::send($findUser->id, auth()->user()->id, 'add_left_volume',['new' => $this->formatBytes($left_Usage)]);
                $max_usage += $left_Usage;
            }
            if($left > 0){
                $days += ($left > 5 ? 5 : $left);
                SaveActivityUser::send($findUser->id,auth()->user()->id,'add_left_day',['day' => ($left > 5 ? 5 : $left)]);
            }

            $expiretime = $tm + (864000 * $days * 100) ;

            $login->update_client($findUser->uuid_v2ray, [
                'service_id' => $findUser->protocol_v2ray,
                'username' => $findUser->username,
                'multi_login' => $findUser->group->multi_login,
                'totalGB' =>  $max_usage,
                'expiryTime' => $expiretime,
                'enable' => ($findUser->is_enabled ? true : false),
            ]);
            $login->reset_client($findUser->username,$findUser->protocol_v2ray);
        }



        $findUser->usage = 0;
        $findUser->download_usage = 0;
        $findUser->upload_usage = 0;
        SaveActivityUser::send($findUser->id,auth()->user()->id,'re_charge');
        $findUser->limited = 0;

        $findUser->save();

        return response()->json(['status' => false,'message' => 'کاربر با نام کاربری '.$findUser->username." با موفقیت شارژ شد.".$expiretime]);


    }
    public function formatBytes(int $size,int $format = 2, int $precision = 2) : string
    {
        $base = log($size, 1024);

        if($format == 1) {
            $suffixes = ['بایت', 'کلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت']; # Persian
        } elseif ($format == 2) {
            $suffixes = ["B", "KB", "MB", "GB", "TB"];
        } else {
            $suffixes = ['B', 'K', 'M', 'G', 'T'];
        }

        if($size <= 0) return "0 ".$suffixes[1];

        $result = pow(1024, $base - floor($base));
        $result = round($result, $precision);
        $suffixes = $suffixes[floor($base)];

        return $result ." ". $suffixes;
    }

    public function show($id){
        $userDetial = User::where('id',$id)->first();
        if(!$userDetial){
            return response()->json(['status' => false,'message' => 'کاربر یافت نشد!']);
        }


        if($userDetial->service_group == 'v2ray'){

            $findServer = false;
            $usage = 0;
            $total = 0;
            $left_usage = 0;
            $v2ray_user = [];
            $preg_left = 0;
            $down = 0;
            $up = 0;
            $enable = false;
            $portocol = [];
            $expire_time = false;

            if($userDetial->v2ray_server){
                $v2ray_user = true;
                $V2ray = new V2raySN(
                    [
                        'HOST' => $userDetial->v2ray_server->ipaddress,
                        "PORT" => $userDetial->v2ray_server->port_v2ray,
                        "USERNAME" => $userDetial->v2ray_server->username_v2ray,
                        "PASSWORD" => $userDetial->v2ray_server->password_v2ray,
                        "CDN_ADDRESS"=> $userDetial->v2ray_server->cdn_address_v2ray,

                    ]
                );

                if(!$V2ray->error['status']){
                    $list = $V2ray->InBoandList();
                    $re = [];
                    foreach ($list['obj'] as $key => $row){
                        $re[] = [
                            'id' => $row['id'],
                            'port' => $row['port'],
                            'protocol' => $row['protocol'],
                            'remark' => $row['remark'],
                            'tag' => $row['tag'],
                        ];
                    }
                    $portocol = $re;

                    $client = $V2ray->get_user($userDetial->protocol_v2ray,$userDetial->username);
                    if($client){
                        $clients = $V2ray->get_client($userDetial->username);

                        $expire_time = ((int) $clients['expiryTime'] > 0 ? (int) $clients['expiryTime'] /1000 : 0);
                         if($expire_time  > 0){
                             $ex = date('Y-m-d H:i:s', $expire_time);
                             $jalali = Jalalian::forge($ex)->toString();
                             $left = "(".Carbon::now()->diffInDays($ex, false)." روز)";

                             $expire_time = $jalali." - ".$left;
                         }
                        $v2ray_user = $client['user'];
                        $v2ray_user['online'] = in_array($userDetial->username,$V2ray->getOnlines()) ? true : false;
                        $v2ray_user['sub_link'] = url('/sub/'.base64_encode($userDetial->username));

                        $usage = $clients['up'] +  $clients['down'];
                        $enable = $clients['enable'];
                        $total = $clients['total'];
                        $left_usage = $this->formatBytes($total - $usage);
                        $preg_left = ($total > 0 ? ($usage * 100 / $total) : 0);
                        $preg_left = 100  - $preg_left  ;
                        $usage = $this->formatBytes($usage);
                        $total = $this->formatBytes($total);
                        $down = $this->formatBytes($clients['up']);
                        $up = $this->formatBytes($clients['down']);

                    }
                }
            }

            return  response()->json([
                'status' => true,
                'user' => [
                    'portocols' => $re,
                    'v2ray_user' => $v2ray_user,
                    'id' => $userDetial->id,
                    'server_detial' => ($userDetial->v2ray_server ? $userDetial->v2ray_server : false),
                    'left_usage' => $left_usage,
                    'down' => $down,
                    'up' => $up,
                    'preg_left' => $preg_left,
                    'usage' => $usage,
                    'total' => $total,
                    'expire_time' => $expire_time,
                    'name' => $userDetial->name,
                    'v2ray_location' => $userDetial->v2ray_location,
                    'v2ray_transmission' => $userDetial->v2ray_transmission,
                    'port_v2ray' => $userDetial->port_v2ray,
                    'remark_v2ray' => $userDetial->remark_v2ray,
                    'protocol_v2ray' => $userDetial->protocol_v2ray,
                    'v2ray_id' => $userDetial->v2ray_id,
                    'v2ray_u_id' => $userDetial->v2ray_u_id,
                    'service_group' => $userDetial->service_group,
                    'username' => $userDetial->username,
                    'creator' => $userDetial->creator,
                    'creator_detial' => ($userDetial->creator_name ? ['name' => $userDetial->creator_name->name ,'id' =>$userDetial->creator_name->id] : [] ) ,
                    'password' => $userDetial->password,
                    'group' => ($userDetial->group ? $userDetial->group->name : '---'),
                    'group_type' => ($userDetial->group ? $userDetial->group->group_type : '---'),
                    'group_id' => $userDetial->group_id,
                    'enable_config' => $enable ,
                    'is_enabled' => $userDetial->is_enabled ,
                    'created_at' => Jalalian::forge($userDetial->created_at)->__toString(),
                ],
                'groups' => Groups::select('name','id')->get(),
                'v2ray_servers' => Ras::select(['id','server_type','name','server_location'])->where('server_type','v2ray')->where('is_enabled',1)->get(),
                'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
            ]);
        }


        $left_usage = 0;
        $up = 0;
        $down = 0;
        $usage = 0;
        $total = 0;

        if($userDetial->group){
                $up = $userDetial->upload_usage;
                $down = $userDetial->download_usage;
                $usage = $userDetial->usage;
                $left_usage = $userDetial->max_usage - $usage;
                $total = $userDetial->max_usage;

        }

        $wireGuardConfigs = [];
        if($userDetial->service_group == 'wireguard'){
            $wireGuardConfigs =   new WireGuardConfigCollection(WireGuardUsers::where('user_id',$userDetial->id)->get());
        }

        $servers = [];
        $groups = Groups::select('name','id');
        if($userDetial->service_group == 'wireguard'){
            $value = "وایرگارد";
            $groups->where('name','like','%'.$value.'%');
            $groups->where('group_type',$userDetial->group->group_type);

            $servers = Ras::select(['name','ipaddress','server_location','l2tp_address','id'])->where('unlimited',($userDetial->group->group_type == 'volume' ? 0 : 1))->get();
        }

        return  response()->json([
            'servers' => $servers,
            'status' => true,
            'user' => [
                'id' => $userDetial->id,
                'wireguard' => $wireGuardConfigs,
                'name' => $userDetial->name,
                'down' => $down,
                'down_format' => $this->formatBytes($down,2),
                'left_usage' => $left_usage,
                'left_usage_format' =>  $this->formatBytes($left_usage,2),
                'up' => $up,
                'up_format' => $this->formatBytes($up,2),
                'usage' => $usage,
                'usage_format' => $this->formatBytes($usage,2),
                'total' => $total,
                'total_format' => $this->formatBytes($total,2),
                'username' => $userDetial->username,
                'creator' => $userDetial->creator,
                'multi_login' => $userDetial->multi_login,
                'phonenumber' => $userDetial->phonenumber,
                'creator_detial' => ($userDetial->creator_name ? ['name' => $userDetial->creator_name->name ,'id' =>$userDetial->creator_name->id] : [] ) ,
                'password' => $userDetial->password,
                'group' => ($userDetial->group ? $userDetial->group->name : '---'),
                'group_type' => ($userDetial->group ? $userDetial->group->group_type : '---'),
                'group_id' => $userDetial->group_id,
                'expire_type' => $userDetial->expire_type,
                'service_group' => $userDetial->service_group,
                'expire_value' => $userDetial->expire_value,
                'expire_date' => ($userDetial->expire_date !== NULL ? Jalalian::forge($userDetial->expire_date)->__toString() : '---'),
                'left_time' => ($userDetial->expire_date !== NULL ? Carbon::now()->diffInDays($userDetial->expire_date, false) : '---'),
                'status' => ($userDetial->isOnline ? 'online': 'offline'),
                'is_enabled' => $userDetial->is_enabled ,
                'created_at' => Jalalian::forge($userDetial->created_at)->__toString(),
            ],
            'groups' => $groups->get(),
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

        if($request->type == 'delete_20'){
            $list = User::where('expire_set',1)->where('service_group','l2tp_cisco')->where('expire_date','<=',Carbon::now('Asia/Tehran')->subDays(45))->get();
            foreach ($list as $user){
                RadPostAuth::where('username',$user->username)->delete();
                Activitys::where('user_id',$user->id)->delete();
                UserGraph::where('user_id',$user->id)->delete();
                $user->delete();

            }
            return response()->json([
                'status' => true,
                'message' => 'کاربران  با موفقیت حذف شدند!'
            ]);
        }
        foreach ($request->user_ids as $user_id){
            $find = User::where('id',$user_id)->first();
            if($find){
                if($find->service_group == 'v2ray'){
                    if($find->v2ray_server) {
                        $login_s = new V2rayApi($find->v2ray_server->ipaddress, $find->v2ray_server->port_v2ray, $find->v2ray_server->username_v2ray, $find->v2ray_server->password_v2ray);
                        if($login_s) {
                            $login_s->del($find->v2ray_id);
                        }
                    }
                }
                UserGraph::where('user_id',$user_id)->delete();

                $find->delete();
            }
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
                $find->limited = 0;

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

        $monitor = new MonitorigController() ;
        if($monitor->KillUser($find->servername,$find->username)) {
            $find->acctstoptime = Carbon::now('Asia/Tehran');
            $find->save();
            return response()->json([
                'status' => false,
                'message' => 'عملیات با موفقیت انجام شد!'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'متاسفانه نتوانستیم این کار را انجام دهیم!'
        ],403);

    }


    public function getUserBandwith(Request $request){

        $data = new RadAcct;
        if($request->SearchText){
            $data = $data->where('username',$request->SearchText);
        }
        $data = $data->where('acctstoptime','!=',NULL)->selectRaw('sum(acctoutputoctets) as upload_sum, sum(acctinputoctets) as download_sum, sum(acctinputoctets + acctoutputoctets) as total_sum,username,radacctid');




        return new UserGraphsResource($data->groupBy('username')->orderBy('total_sum','DESC')->paginate(1000));
    }

    public function buy_volume(Request $request,$id){
        $user = User::where('id',$id)->first();
        if(!$user){
            return response()->json([
                'status' => false,
                'message' => 'کاربر یافت نشد!'
            ],403);
        }

        SaveActivityUser::send($user->id,auth()->user()->id,'buy_new_volume',['new' => $request->volume,'last' => $this->formatBytes($user->max_usage,2)]);


        $user->max_usage += @round((((int) $request->volume *1024) * 1024) * 1024 ) ;
        $user->save();

        if($user->service_group == 'v2ray'){
            $login = new V2raySN(
                [
                    'HOST' => $user->v2ray_server->ipaddress,
                    "PORT" => $user->v2ray_server->port_v2ray,
                    "USERNAME" => $user->v2ray_server->username_v2ray,
                    "PASSWORD" => $user->v2ray_server->password_v2ray,
                    "CDN_ADDRESS"=> $user->v2ray_server->cdn_address_v2ray,
                ]
            );
            if($login->error['status']){
                return response()->json(['status' => false,'message' => 'خطا در برقراری ارتباط با سرور V2ray مجددا تلاش نمایید'],502);
            }
            $v2_current = $login->get_client($user->username);
            $login->update_client($user->uuid_v2ray, [
                'service_id' => $user->protocol_v2ray,
                'username' => $user->username,
                'multi_login' => $user->group->multi_login,
                'totalGB' => $v2_current['total'] + @round((((int) $request->volume *1024) * 1024) * 1024 ),
                'expiryTime' => $v2_current['expiryTime'],
                'enable' => ($user->is_enabled ? true : false),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'حجم مورد نظر با موفقیت اضافه شد!'
        ]);

    }

    public function buy_day(Request $request,$id){
        $find = User::where('id',$id)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],404);
        }

        $creator_id = $find->creator;

        if($request->day < 2 || $request->day > 30){
            return response()->json([
                'message' => 'خطای 401!'
            ],403);
        }
        if($find->group->group_type !== 'expire'){
            return response()->json([
                'message' => 'خطای 402!'
            ],403);
        }
        $price = 3700;
        $total_price = (int) $request->day * $price;


        if($find->expire_set) {
            $find->expire_date = Carbon::parse($find->expire_date)->addDays((int)$request->day);
        }
        if(!$find->expire_set){
            $find->exp_val_minute += floor((int)$request->day * 1440);
        }

        $find->save();


        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $total_price;
        $new->approved = 1;
        $new->description = 'کسر بابت خرید مقدار '.$request->day.' روز اضافه برای  '.$find->username." (توسط مدیر )";
        $new->creator = 2;
        $new->for = $creator_id;
        $new->save();
        SaveActivityUser::send($find->id,$creator_id,'buy_day_for_account',['new' => $request->day,'total' => floor($find->exp_val_minute / 1440) ]);






        return response()->json(['status' => false,'message' => "با موفقیت مقدار روز ".$request->day." به اکانت اضافه شد."]);
    }


}
