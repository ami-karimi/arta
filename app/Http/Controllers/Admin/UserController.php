<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AdminActivityCollection;
use App\Http\Resources\Api\AcctSavedCollection;
use App\Http\Resources\Api\UserGraphsResource;
use App\Http\Resources\Api\UserCollection;
use App\Http\Resources\Api\ActivityCollection;
use App\Models\Activitys;
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

        if($request->service_group == 'v2ray'){
            $req_all = $request->all();
            $findUsername = User::where('username',$request->username)->first();
            if($findUsername){
                return response()->json(['status' => false,'نام کاربری '.$request->username.' موجود میباشد!']);
            }

            if(!$request->v2ray_location){
                return response()->json(['message' => 'لطفا لوکیشن مقضد را انتخاب نمایید!'],403);
            }
            if(!$request->v2ray_transmission){
                return response()->json(['message' => 'لطفا نوع  transmission	 را انتخاب نمایید!'],403);
            }
            if(!$request->protocol_v2ray){
                return response()->json(['message' => 'لطفا نوع پرتکل را انتخاب نمایید'],403);
            }
            $findGroup = Groups::where('id', $request->group_id)->first();
            if(!$findGroup){
                return response()->json(['message' => 'گروه مورد نظر یافت نشد!'],403);
            }
            if($findGroup->group_type !== 'volume'){
                return response()->json(['message' => 'امکان ایجاد کاربر در این گروه برای V2ray وجود ندارد!'],403);
            }
            $findLocation = Ras::where('server_type','v2ray')->where('is_enabled',1)->where('id',$request->v2ray_location)->first();
            $login = new V2rayApi($findLocation->ipaddress,$findLocation->port_v2ray,$findLocation->username_v2ray,$findLocation->password_v2ray);
            $status_data = ($login ?  true : false) ;
            if(!$status_data){
                return response()->json(['message' => 'متاسفانه لوکیشن درخواستی در دسترس نمیباشد!'],403);
            }

            $password = $request->password;
            if($request->random_password){
                $password = substr(rand(0,99999),0,(int) $request->random_password_num);
            }


            $create_status =  $login->add($request->protocol_v2ray,(int) $findGroup->group_volume,$request->v2ray_transmission,$request->remark_v2ray ." -".$request->username,$request->port_v2ray,strtotime(time()."+360 Days"));
            if(!$create_status){
                return response()->json(['message' => 'متسفانه کاربر در سرور مورد نظر ایجاد نشد ! مجددا تلاش نمایید'],403);
            }
            $req_all['username'] = $request->username;
            $req_all['password'] = $password;
            $req_all['groups'] = $request->username;
            $req_all['creator'] = $request->creator;

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
                    $req_all['max_usage']  = @round(60000000000  * $findGroup->expire_value) * $findGroup->multi_login;
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

            $req_all['multi_login'] = $findGroup->multi_login;

            if($findGroup->group_type == 'expire') {
                $req_all['expire_value'] = $findGroup->expire_value;
                $req_all['expire_type'] = $findGroup->expire_type;
                $req_all['expire_set'] = 0;
            }
            if($findGroup->group_type == 'volume') {
                $req_all['multi_login'] = 5;
                $req_all['max_usage'] =@round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
            }

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

        $login = false;
        if($find->service_group == 'v2ray'){
            $login = new V2rayApi($find->v2ray_server->ipaddress,$find->v2ray_server->port_v2ray,$find->v2ray_server->username_v2ray,$find->v2ray_server->password_v2ray);

            if($request->protocol_v2ray !== $find->protocol_v2ray){
                if($login) {
                    $change = $login->update($find->port_v2ray, ['protocol' => $request->protocol_v2ray]);
                    if($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'change_user_protocol', ['last' => $find->protocol_v2ray, 'new' => $request->protocol_v2ray]);
                        $find->protocol_v2ray = $request->protocol_v2ray;
                    }
                }
            }
            if($request->v2ray_transmission !== $find->v2ray_transmission){
                if($login) {
                    $change = $login->update($find->port_v2ray, ['transmission' => $request->v2ray_transmission]);
                    if($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'change_user_transmission', ['last' => $find->v2ray_transmission, 'new' => $request->v2ray_transmission]);
                        $find->v2ray_transmission = $request->v2ray_transmission;
                    }
                }
            }
            if($request->remark_v2ray !== $find->remark_v2ray){
                if($login) {
                    $change =  $login->update($find->port_v2ray, ['remark' => $request->remark_v2ray]);
                    if($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'remark_v2ray', ['last' => $find->remark_v2ray, 'new' => $request->remark_v2ray]);
                        $find->remark_v2ray = $request->remark_v2ray;
                    }
                }
            }

            if($request->port_v2ray !== $find->port_v2ray){
                if($login) {
                    $change =  $login->update($find->port_v2ray, ['port' => $request->port_v2ray]);
                    if($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'change_user_port', ['last' => $find->port_v2ray, 'new' => $request->port_v2ray]);
                        $find->port_v2ray = $request->port_v2ray;
                    }
                }
            }

        }

        if($request->service_group !== $find->service_group){
            if($find->service_group == 'v2ray'){
                $login->del($find->v2ray_id);
            }elseif($request->service_group == 'v2ray'){
                $req_all = $request->all();
                if(!$request->v2ray_location){
                    return response()->json(['message' => 'لطفا لوکیشن مقضد را انتخاب نمایید!'],403);
                }
                if(!$request->v2ray_transmission){
                    return response()->json(['message' => 'لطفا نوع  transmission	 را انتخاب نمایید!'],403);
                }
                if(!$request->protocol_v2ray){
                    return response()->json(['message' => 'لطفا نوع پرتکل را انتخاب نمایید'],403);
                }
                $findGroup = Groups::where('id', $request->group_id)->first();
                if(!$findGroup){
                    return response()->json(['message' => 'گروه مورد نظر یافت نشد!'],403);
                }
                if($findGroup->group_type !== 'volume'){
                    return response()->json(['message' => 'امکان ایجاد کاربر در این گروه برای V2ray وجود ندارد!'],403);
                }
                $findLocation = Ras::where('server_type','v2ray')->where('is_enabled',1)->where('id',$request->v2ray_location)->first();
                $login = new V2rayApi($findLocation->ipaddress,$findLocation->port_v2ray,$findLocation->username_v2ray,$findLocation->password_v2ray);
                $status_data = ($login ?  true : false) ;
                if(!$status_data){
                    return response()->json(['message' => 'متاسفانه لوکیشن درخواستی در دسترس نمیباشد!'],403);
                }

                $password = $request->password;
                if($request->random_password){
                    $password = substr(rand(0,99999),0,(int) $request->random_password_num);
                }


                $create_status =  $login->add($request->protocol_v2ray,10,$request->v2ray_transmission,$request->remark_v2ray ." -".$request->username,$request->port_v2ray,strtotime(time()."+360 Days"));
                if(!$create_status){
                    return response()->json(['message' => 'متسفانه کاربر در سرور مورد نظر ایجاد نشد ! مجددا تلاش نمایید'],403);
                }


            }
        }


        if($request->service_group){
            $find->service_group = $request->service_group;
        }



        $exp_val_minute = $find->exp_val_minute;
        $findGroup = Groups::where('id',$request->group_id)->first();

        if($findGroup->id !== $find->group_id){
            if($find->service_group == 'v2ray'){
                if($login){
                    $login->update($find->port_v2ray,['total' =>$findGroup->group_volume]);
                }
            }
            $find->group_id = $findGroup->id;

            if($findGroup->expire_type !== 'no_expire'){
                if($findGroup->expire_type == 'minutes'){
                    $exp_val_minute = $findGroup->expire_value;
                }elseif ($findGroup->expire_type == 'month') {
                    $find->exp_val_minute = floor($findGroup->expire_value * 43800);
                    $find->max_usage  = @round(60000000000  * $findGroup->expire_value) * $findGroup->multi_login;
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

            if($findGroup->group_type == 'expire') {
                $find->expire_value = $findGroup->expire_value;
                $find->expire_type = $findGroup->expire_type;
            }
            if($findGroup->group_type == 'volume') {
                $find->multi_login = 5;
                $find->max_usage = @round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
                $find->expire_value = 0;
                $find->expire_type = 'no_expire';
                $find->expire_date = NULL;
                $find->expire_set = 0;

            }



        }

        if($request->change_expire_type && $findGroup->group_type == 'expire'){
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
        if($find->first_login !== NULL && $findGroup->group_type == 'expire'){
            $expire_date = Carbon::parse($find->first_login)->addMinutes($exp_val_minute)->toDateTimeString();
        }

        if($find->is_enabled !== ($request->is_enabled == true ? 1 : 0)){
            $find->is_enabled = ($request->is_enabled === true ? 1 : 0);
            SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => $find->is_enabled]);
            if($login){
                $login->update($find->port_v2ray,['enable' => $request->is_enabled]);
            }
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
        if($expire_date && $findGroup->group_type == 'expire'){
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
        if($findUser->service_group == 'v2ray'){
            $login = new V2rayApi($findUser->v2ray_server->ipaddress,$findUser->v2ray_server->port_v2ray,$findUser->v2ray_server->username_v2ray,$findUser->v2ray_server->password_v2ray);
            if($login){
                $login->update($findUser->port_v2ray,['reset' => true]);
            }
        }

        UserGraph::where('user_id',$findUser->id)->delete();


        if($findUser->service_group !== 'v2ray' && $findUser->group->group_type !== 'volume'){

            $findUser->expire_set = 0;
           $findUser->first_login = NULL;
           $findUser->expire_date = NULL;
        }elseif($findUser->group->group_type == 'volume'){
            $findUser->expire_set = 0;
            $findUser->first_login = NULL;
            $findUser->expire_date = NULL;
        }


        SaveActivityUser::send($findUser->id,auth()->user()->id,'re_charge');

        $findUser->save();

        return response()->json(['status' => false,'message' => 'کاربر با نام کاربری '.$findUser->username." با موفقیت شارژ شد."]);


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
            if($userDetial->v2ray_server){
                $login_s =new V2rayApi($userDetial->v2ray_server->ipaddress,$userDetial->v2ray_server->port_v2ray,$userDetial->v2ray_server->username_v2ray,$userDetial->v2ray_server->password_v2ray);
                if($login_s) {
                    $v2ray_user =  $login_s->list(['port' => (int) $userDetial->port_v2ray]);
                    if(count($v2ray_user)) {
                        if (!$userDetial->v2ray_id) {
                            $userDetial->v2ray_id = $v2ray_user['id'];
                            $userDetial->save();
                        }
                        $usage = $login_s->formatBytes($v2ray_user['usage'],2);
                        $total = $login_s->formatBytes($v2ray_user['total'],2);
                        $left_usage = $login_s->formatBytes($v2ray_user['total'] - $v2ray_user['usage']);
                        $preg_left = ($v2ray_user['total'] > 0 ? ($v2ray_user['usage'] * 100 / $v2ray_user['total']) : 0);
                        $preg_left = 100  - $preg_left  ;
                        $down = $login_s->formatBytes($v2ray_user['down'],2);
                        $up = $login_s->formatBytes($v2ray_user['up'],2);
                    }
                }
            }

            if($userDetial->group){

                    $GraphData = UserGraph::where('user_id',$userDetial->id)->get();
                    $up = $GraphData->sum('tx');
                    $down = $GraphData->sum('rx');
                    $usage = $userDetial->max_usage - $GraphData->sum('total');
                    $total = $userDetial->max_usage;

            }
            return  response()->json([
                'status' => true,
                'user' => [
                    'id' => $userDetial->id,
                    'server_detial' => ($userDetial->v2ray_server ? $userDetial->v2ray_server : false),
                    'left_usage' => $left_usage,
                    'down' => $down,
                    'up' => $up,
                    'preg_left' => $preg_left,
                    'v2ray_user' => $v2ray_user,
                    'usage' => $usage,
                    'total' => $total,
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
                $GraphData = UserGraph::where('user_id',$userDetial->id)->get();
                $up = $GraphData->sum('tx');
                $down = $GraphData->sum('rx');
                $usage = $GraphData->sum('total');
                $left_usage = $userDetial->max_usage - $usage;

                $total = $userDetial->max_usage;

        }


        return  response()->json([
            'status' => true,
            'user' => [
                'id' => $userDetial->id,
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
                'creator_detial' => ($userDetial->creator_name ? ['name' => $userDetial->creator_name->name ,'id' =>$userDetial->creator_name->id] : [] ) ,
                'password' => $userDetial->password,
                'group' => ($userDetial->group ? $userDetial->group->name : '---'),
                'group_type' => ($userDetial->group ? $userDetial->group->group_type : '---'),
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

        return response()->json([
            'status' => false,
            'message' => 'حجم مورد نظر با موفقیت اضافه شد!'
        ]);

    }

}
