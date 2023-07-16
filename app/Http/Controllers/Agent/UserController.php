<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Admin\MonitorigController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AcctSavedCollection;
use App\Http\Resources\Api\AgentUserCollection;
use App\Http\Resources\Api\ActivityCollection;
use App\Http\Resources\Api\AdminActivityCollection;
use App\Http\Resources\WireGuardConfigCollection;
use App\Models\AcctSaved;
use App\Models\Financial;
use App\Models\Groups;
use App\Models\PriceReseler;
use App\Models\RadAcct;
use App\Models\Ras;
use App\Models\User;
use App\Models\UserGraph;
use App\Models\WireGuardUsers;
use App\Utility\Helper;
use App\Utility\V2rayApi;
use App\Utility\WireGuard;
use Carbon\Carbon;
use http\Client\Response;
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
        $userDetial = User::where('id',$id)->where('creator',auth()->user()->id)->first();
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

            return  response()->json([
                'status' => true,
                'user' => [
                    'id' => $userDetial->id,
                    'server_detial' => ($userDetial->v2ray_server ? $userDetial->v2ray_server->only(['server_location','ipaddress','cdn_address_v2ray']) : false),
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
            'status' => true,
            'servers' => $servers,
            'user' => [
                'wireguard' => $wireGuardConfigs,
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
                'group_type' => ($userDetial->group ? $userDetial->group->group_type : '---'),
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
                'service_group' => $userDetial->service_group,

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
    public function getActivityAll(Request $request){

        $getAgentUsers = User::select('id')->where('creator',auth()->user()->id)->get()->pluck('id');
        $activitys =  Activitys::whereIn('user_id',$getAgentUsers);

        $per_page = 10;
        if($request->per_page){
            $per_page = (int) $request->per_page;
        }

        return new ActivityCollection($activitys->orderBy('id','DESC')->paginate($per_page));
    }
    public function ReChargeAccount(Request $request,$username){
        if(!$username){
            return response()->json(['status' => false,'message' => 'حساب یافت نشد'],403);
        }
        $find = User::where('username',$username)->where('creator',auth()->user()->id)->where('expire_set',1)->first();
        if(!$find){
            return response()->json(['status' => false,'message' => 'کاربر یافت نشد!'],403);
        }


        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  =  $icom_user - $minus_income;

        if($incom <= 0 ){
            return response()->json(['status' => false,'message' => 'موجودی شما کافی نمیباشد!'],403);
        }

        if(!$request->group_id){
            return response()->json(['status' => false,'message' => 'لطفا گروه کاربری را انتخاب  نمایید!'],403);

        }

        $findGroup = Groups::where('id',$request->group_id)->first();
        if(!$findGroup){
            return response()->json(['status' => false,'message' => 'گروه کاربری یافت نشد!'],403);
        }

        $price = $findGroup->price_reseler;
        $findSellectPrice =  PriceReseler::where('group_id',$findGroup->id)->where('reseler_id',auth()->user()->id)->first();
        if($findSellectPrice){
            $price = (int) $findSellectPrice->price;
        }
        /*
        $priceList = Helper::GetReselerGroupList('one',$findGroup->id,auth()->user()->id);
        if($priceList){
            $price = (int) $priceList['reseler_price'];
        }
        */

        if($incom < $price ){
            return response()->json(['status' => false,'message' => 'موجودی شما برای پرداخت '.number_format($price).' تومان کافی نمیباشد!'],403);
        }

        if ($findGroup->expire_type !== 'no_expire') {
            if ($findGroup->expire_type == 'minutes') {
                $find->exp_val_minute = $findGroup->expire_value;
            } elseif ($findGroup->expire_type == 'month') {
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
            $find->multi_login = $findGroup->multi_login;

        }

        if($find->expire_date !== NULL) {
            $last_time_s = (int) Carbon::now()->diffInDays($find->expire_date, false);
            if ($last_time_s > 0) {
                $find->exp_val_minute += floor($last_time_s * 1440);
                SaveActivityUser::send($find->id,auth()->user()->id,'add_left_day',['day' => $last_time_s]);
            }
        }

        if($find->group_id !== $findGroup->id){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_group',['last' => $find->group->name,'new'=> $findGroup->name]);
        }
        $find->group_id = $findGroup->id;
        $find->first_login = NULL;

        if($findGroup->group_type == 'expire') {
            $find->expire_value = $findGroup->expire_value;
            $find->expire_type = $findGroup->expire_type;
            $find->expire_date = NULL;
            $find->expire_set = 0;

        }elseif($findGroup->group_type == 'volume'){
            $find->max_usage = @round((((int) $findGroup->group_volume *1024) * 1024) * 1024 );
            $find->expire_value = 1;
            $find->expire_type = 'no_expire';
            $find->expire_date = NULL;
            $find->multi_login = 5;
            $find->expire_set = 0;
        }
        $find->creator = auth()->user()->id;
        UserGraph::where('user_id',$find->id)->delete();

        $find->save();
        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $price;
        $new->approved = 1;
        $new->description = 'کسر بابت شارژ اکانت '.$find->username;
        $new->creator = 2;
        $new->for = auth()->user()->id;
        $new->save();

        SaveActivityUser::send($find->id,auth()->user()->id,'re_charge');
        return response()->json(['status' => false,'message' => "اکانت با موفقیت شارژ شد!"]);
    }
    public function create(Request $request){


        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  = $icom_user - $minus_income;
        if($incom <= 0 ){
            return response()->json(['status' => false,'message' => 'موجودی شما کافی نمیباشد!'],403);
        }

        if(!$request->username && !$request->group_id){
            return response()->json(['status' => false,'message' => 'تمامی فیلد ها ضروری میباشند!'],403);
        }
        $findGroup = Groups::where('id',$request->group_id)->first();
        if(!$findGroup){
            return response()->json(['status' => false,'message' => 'گروه کاربری یافت نشد!'],403);
        }


        $price = $findGroup->price_reseler;

        $findSellectPrice =  PriceReseler::where('group_id',$findGroup->id)->where('reseler_id',auth()->user()->id)->first();
        if($findSellectPrice){
            $price = (int) $findSellectPrice->price;
        }
        if($request->account_type) {
            if(!$request->server_id){
                return response()->json(['status' => false,'message' => "لطفا سرور را انتخاب نمایید!"],403);
            }
        }
        /*
        $priceList = Helper::GetReselerGroupList('one',$findGroup->id,auth()->user()->id);
        if($priceList){
            $price = (int) $priceList['reseler_price'];
        }
        */

        $userNameList = [];
        if($request->group_account){
            if(!(int) $request->from){
                return response()->json(['status' => false,'message' => 'لطفا عدد شروع ایجاد را به عدد و به درستی وارد نمایید'],403);
            }
            if(!(int)$request->to){
                return response()->json(['status' => false,'message' => 'لطفا عدد شروع ایجاد را به عدد و به درستی وارد نمایید'],403);
            }

            $countAll =  (int)$request->to  - (int) $request->from + 1;
            if($countAll <= 0){
                return response()->json(['status' => false,'message' => 'تعداد اکانت نباید منفی باشد لطفا از تا را بررسی نمایید'],403);
            }
            $start  = (int) $request->from;
            $end  = (int) $request->to + 1;
            $price *= $countAll;
            $userNames = $request->username;
            for ($i= $start; $i < $end;$i++) {
                $buildUsername = $userNames . $i;
                $findUsername = User::where('username', $buildUsername)->first();
                if ($findUsername) {
                    return response()->json(['status' => false, 'نام کاربری ' . $buildUsername . ' موجود میباشد!']);
                }
                $password = $request->password;
                if ($request->random_password) {
                    $password = substr(rand(0, 99999), 0, (int)$request->random_password_num);
                }

                $userNameList[] = ['username' => $buildUsername, 'password' => $password];
            }


        }else{

            $findNotUserIn = User::where('username',$request->username)->first();
            if($findNotUserIn){
                return response()->json(['status' => false,'message' => " نام کاربری ".$request->username." در سیستم موجود میباشد لطفا نام کاربری دیگری انتخاب نمایید!"],403);
            }
            $password = $request->password;
            if ($request->random_password) {
                $password = substr(rand(0, 99999), 0, (int)$request->random_password_num);
            }
            $userNameList[] = ['username' => $request->username, 'password' =>$password];
        }

        if($incom < $price ){
            return response()->json(['status' => false,'message' => 'موجودی شما برای پرداخت '.number_format($price).' تومان کافی نمیباشد!'],403);
        }



        $req_all = $request->all();

        foreach ($userNameList as $row) {


            if ($findGroup->expire_type !== 'no_expire') {
                if ($findGroup->expire_type == 'minutes') {
                    $req_all['exp_val_minute'] = $findGroup->expire_value;
                } elseif ($findGroup->expire_type == 'month') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 43800);
                    $req_all['max_usage'] = ((((int) 100 *1024) * 1024) * 1024 )  ;

                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);

                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                    $req_all['max_usage'] = 60000000000  ;

                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                }
            }
            if($findGroup->group_type == 'expire') {
                $req_all['expire_value'] = $findGroup->expire_value;
                $req_all['expire_type'] = $findGroup->expire_type;
                $req_all['expire_set'] = 0;
                $req_all['multi_login'] = $findGroup->multi_login;
            }

            if($findGroup->group_type == 'volume') {
                $req_all['multi_login'] = 5;
                $req_all['max_usage'] =@round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
            }


            $req_all['password'] = $row['password'];
            $req_all['username'] = $row['username'];

            $req_all['creator'] = auth()->user()->id;

            $create = true;





            $user = User::create($req_all);
            if($request->account_type) {
                $user->service_group = "wireguard";
                $user->save();
                if ($user) {
                    $create_wr = new WireGuard($request->server_id, $row['username']);

                    $user_wi = $create_wr->Run();
                    if ($user_wi['status']) {
                        $saved = new  WireGuardUsers();
                        $saved->profile_name = $user_wi['config_file'];
                        $saved->user_id = $user->id;
                        $saved->server_id = $request->server_id;
                        $saved->public_key = $user_wi['client_public_key'];
                        $saved->user_ip = $user_wi['ip_address'];
                        $saved->save();
                        exec('qrencode -t png -o /var/www/html/arta/public/configs/' . $user_wi['config_file'] . ".png -r /var/www/html/arta/public/configs/" . $user_wi['config_file'] . ".conf");
                    }else{
                        $user->delete();
                         return response()->json(['status' => false,'message' => 'متاسفانه نتوانستیم کاربر درخواستی را در سرور مورد نظر ایجاد کنیم !'],403);
                    }
                }
            }

            $req_all['username'] = $row['username'];
            $req_all['password'] = $row['password'];
            $req_all['groups'] = $request->username;
            $req_all['creator'] = auth()->user()->id;
            AcctSaved::create($req_all);
            SaveActivityUser::send($user->id,auth()->user()->id,'create');
        }

        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $price;
        $new->approved = 1;
        $new->description = 'کسر بابت ایجاد اکانت '.$req_all['username'];
        $new->creator = 2;
        $new->for = auth()->user()->id;
        $new->save();

        return response()->json(['status' => false,'message' => "اکانت با موفقیت ایجاد شد!"]);

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
                    $req_all['max_usage']  = @round(((((int) 100 *1024) * 1024) * 1024 )  * $findGroup->expire_value) * $findGroup->multi_login;
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
                    $saved->server_id = $request->server_id;
                    $saved->public_key = $user_wi['client_public_key'];
                    $saved->user_ip = $user_wi['ip_address'];
                    $saved->save();
                    exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");

                }
            }
        }


        return response()->json(['status' => true, 'message' => 'کاربر با موفقیت اضافه شد!']);
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

        $login = false;
        if($find->service_group == 'v2ray'){
            $login = new V2rayApi($find->v2ray_server->ipaddress,$find->v2ray_server->port_v2ray,$find->v2ray_server->username_v2ray,$find->v2ray_server->password_v2ray);
        }

        if($find->service_group == 'v2ray') {
            if ($request->protocol_v2ray !== $find->protocol_v2ray) {
                if ($login) {
                    $change = $login->update($find->port_v2ray, ['protocol' => $request->protocol_v2ray]);
                    if ($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'change_user_protocol', ['last' => $find->protocol_v2ray, 'new' => $request->protocol_v2ray]);
                        $find->protocol_v2ray = $request->protocol_v2ray;
                    }
                }
            }
            if ($request->v2ray_transmission !== $find->v2ray_transmission) {
                if ($login) {
                    $change = $login->update($find->port_v2ray, ['transmission' => $request->v2ray_transmission]);
                    if ($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'change_user_transmission', ['last' => $find->v2ray_transmission, 'new' => $request->v2ray_transmission]);
                        $find->v2ray_transmission = $request->v2ray_transmission;
                    }
                }
            }
            if ($request->remark_v2ray !== $find->remark_v2ray) {
                if ($login) {
                    $change = $login->update($find->port_v2ray, ['remark' => $request->remark_v2ray]);
                    if ($change) {
                        SaveActivityUser::send($find->id, auth()->user()->id, 'remark_v2ray', ['last' => $find->remark_v2ray, 'new' => $request->remark_v2ray]);
                        $find->remark_v2ray = $request->remark_v2ray;
                    }
                }
            }

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

        if($request->name){
            $find->name = $request->name;
        }
        if($request->username !== $find->username){
            $findElse = User::where('username',$request->username)->where('id','!=',$find->id)->first();
            if($findElse){
                return response()->json([
                    'message' => 'امکان تغییر به این نام کاربری وجود ندارد برای کاربر دیگری استفاده شده است!'
                ],403);
            }
            SaveActivityUser::send($find->id, auth()->user()->id, 'change_username', ['last' =>$find->username, 'new' => $request->username]);
            $find->username = $request->username;

        }

        $find->save();
        return response()->json([
            'message' => 'کاربر با موفقیت بروزرسانی شد!'
        ]);
    }

    public function AcctSaved(Request $request){
        $savedAccounts = AcctSaved::where('creator',auth()->user()->id)->select('*')->orderBy('id','DESC')->groupBy('groups');

        return new AcctSavedCollection($savedAccounts->paginate(20));
    }
    public function AcctSavedView(Request $request){

        $findSaved = AcctSaved::where('id',$request->id)->where('creator',auth()->user()->id)->first();
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
        if($monitor->KillUser($find->nasipaddress,$find->username)) {
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

    public function buy_volume(Request $request,$id){
        $find = User::where('id',$id)->where('creator',auth()->user()->id)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],404);
        }
        $price = 2300;
        $total_price = (int) $request->volume * $price;
        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  = $icom_user - $minus_income;
        if($incom <= $total_price ){
            return response()->json(['status' => false,'message' => 'موجودی شما کافی نمیباشد!'],403);
        }
        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $total_price;
        $new->approved = 1;
        $new->description = 'کسر بابت خرید '.$request->volume.'گیگ حجم '.' اضافه '.$find->username;
        $new->creator = 2;
        $new->for = auth()->user()->id;
        $new->save();
        SaveActivityUser::send($find->id,auth()->user()->id,'buy_new_volume',['new' => $request->volume,'last' => $this->formatBytes($find->max_usage,2)]);

        $find->max_usage += @round((((int) $request->volume *1024) * 1024) * 1024 ) ;
        $find->save();

        return response()->json(['status' => false,'message' => "حجم با موفقیت به کاربر اضافه شد!"]);

    }

    public function buy_day(Request $request,$id){
        $find = User::where('id',$id)->where('creator',auth()->user()->id)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],404);
        }
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
        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  = $icom_user - $minus_income;
        if($incom <= $total_price ){
            return response()->json(['status' => false,'message' => 'موجودی شما کافی نمیباشد!'],403);
        }

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
        $new->description = 'کسر بابت خرید مقدار '.$request->day.' روز اضافه برای  '.$find->username;
        $new->creator = 2;
        $new->for = auth()->user()->id;
        $new->save();
        SaveActivityUser::send($find->id,auth()->user()->id,'buy_day_for_account',['new' => $request->day,'total' => floor($find->exp_val_minute / 1440) ]);






        return response()->json(['status' => false,'message' => "با موفقیت مقدار روز ".$request->day." به اکانت اضافه شد."]);
    }


}
