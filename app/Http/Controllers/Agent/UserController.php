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
use App\Utility\V2raySN;
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

        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;

        $user =  User::where('role','user');
        if($request->SearchText){
            $text = $request->SearchText;
            $user->where(function($query) use($text){
                $query->where('name', 'LIKE', "%$text%") ->orWhere('username', 'LIKE', "%$text%");
            });
        }
        $user->whereIn('creator',$sub_agents);

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
                $user->where('expire_set',1)->where('expire_date','<=',Carbon::now('Asia/Tehran')->subDays(20));
            }
            if($request->expire_date == 'not_use'){
                $user->where('expire_set',0);
            }
        }

        if($request->type_service) {
            if (in_array($request->type_service, ['l2tp_cisco', 'wireguard'])) {
                $user->where('service_group', $request->type_service);
            }
        }

        return new AgentUserCollection($user->orderBy('id','DESC')->paginate(50));
    }
    public function group_deactive(Request $request){

        foreach ($request->user_ids as $user_id){
            $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
            $sub_agents[] = auth()->user()->id;
            $find = User::where('id',$user_id)->whereIn('creator',$sub_agents)->first();
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
            $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
            $sub_agents[] = auth()->user()->id;
            $find = User::where('id',$user_id)->whereIn('creator',$sub_agents)->first();
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
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $userDetial = User::where('id',$id)->whereIn('creator',$sub_agents)->first();
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

            $V2rayGroup = Groups::select('id','name')->where('name','like','%v2ray%')->get();
            return  response()->json([
                'status' => true,
                'user' => [
                    'portocols' => $re,
                    'v2ray_user' => $v2ray_user,
                    'id' => $userDetial->id,
                    'server_detial' => ($userDetial->v2ray_server ? $userDetial->v2ray_server : false),
                    'expire_time' => $expire_time,
                    'left_usage' => $left_usage,
                    'down' => $down,
                    'up' => $up,
                    'preg_left' => $preg_left,
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
                    'enable_config' => $enable ,
                    'is_enabled' => $userDetial->is_enabled ,
                    'created_at' => Jalalian::forge($userDetial->created_at)->__toString(),
                ],
                'groups' => $V2rayGroup,
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

        $groups_list = [];
        $s = Helper::GetReselerGroupList('list',false,auth()->user()->id);
        if (auth()->user()->creator) {
            $s = array_filter($s, function ($item) {
                return $item['status_code'] !== "2" && $item['status_code'] !== "0";
            });
        } else {
            $s = array_filter($s, function ($item) {
                return $item['status_code'] !== "3"  && $item['status_code'] !== "0";
            });
        }
        foreach ($s as $row){
            $groups_list[]   = $row;
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
                'phonenumber' => $userDetial->phonenumber,

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
            'groups' => $groups_list,
            'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
        ]);


    }
    public function getActivity($id){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $find = User::where('id',$id)->whereIn('creator',$sub_agents)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],403);
        }
        return new ActivityCollection(Activitys::where('user_id',$find->id)->orderBy('id','DESC')->paginate(5));
    }
    public function getActivityAll(Request $request){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $getAgentUsers = User::select('id')->whereIn('creator',$sub_agents)->get()->pluck('id');
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
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $find = User::where('username',$username)->whereIn('creator',$sub_agents)->first();
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


        $findSellectPrice = Helper::GetReselerGroupList('one',$findGroup->id,auth()->user()->id);
        if($findSellectPrice){
            $price = (int) $findSellectPrice['reseler_price'];
        }
        $creator_price = 0;
        $income = 0;

        if(auth()->user()->creator){
            $income = Helper::getIncome(auth()->user()->creator);
            $creator_price = Helper::GetReselerGroupList('one',$findGroup->id,auth()->user()->creator)['reseler_price'];

            if($income <  $creator_price){
                return response()->json([
                    'message' => 'به دلیل نداشتن موجودی مدیر پنل امکان انجام عملیات وجود ندارد!'
                ],403);
            }
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
                if($findGroup->group_volume > 0) {
                    $find->max_usage = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024)) ;
                }
            } elseif ($findGroup->expire_type == 'days') {
                $find->exp_val_minute = floor($findGroup->expire_value * 1440);
                if($findGroup->group_volume > 0) {
                    $find->max_usage = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024)) ;

                }
             } elseif ($findGroup->expire_type == 'hours') {
                $find->exp_val_minute = floor($findGroup->expire_value * 60);
                if($findGroup->group_volume > 0) {
                    $find->max_usage = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024)) ;

                }
            } elseif ($findGroup->expire_type == 'year') {
                $find->exp_val_minute = floor($findGroup->expire_value * 525600);
                if($findGroup->group_volume > 0) {
                    $find->max_usage = @round(((((int)$findGroup->group_volume * 1024) * 1024) * 1024)) ;

                }
            }
            $find->multi_login = $findGroup->multi_login;

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

            $days = $find->group->expire_value;
            $tm = floor(microtime(true) * 1000);
            $v2_current = $login->get_client($find->username);
            $expire_time = ((int) $v2_current['expiryTime'] > 0 ? (int) $v2_current['expiryTime'] /1000 : 0);
            $left = 0;
            $max_usage = $find->max_usage;
            if($expire_time  > 0){
                $ex = date('Y-m-d H:i:s', $expire_time);
                $left = Carbon::now()->diffInDays($ex, false);
            }
            $left_Usage = $v2_current['total'];
            $left_Usage -= ($v2_current['up'] + $v2_current['down']);
            if($left_Usage > 0 && $left > 0) {
                SaveActivityUser::send($find->id, auth()->user()->id, 'add_left_volume',['new' => $this->formatBytes($left_Usage)]);
                $max_usage += $left_Usage;
            }
            if($left > 0){
                $days += ($left > 5 ? 5 : $left);
                SaveActivityUser::send($find->id,auth()->user()->id,'add_left_day',['day' => ($left > 5 ? 5 : $left)]);
            }

            $expiretime = $tm + (864000 * $days * 100) ;

            $login->update_client($find->uuid_v2ray, [
                'service_id' => $find->protocol_v2ray,
                'username' => $find->username,
                'multi_login' => $find->group->multi_login,
                'totalGB' => $max_usage,
                'expiryTime' => $expiretime,
                'enable' => ($find->is_enabled ? true : false),
            ]);
        }

        if($find->expire_date !== NULL && $findGroup->group_type !== 'volume') {
            $last_time_s = (int) Carbon::now()->diffInDays($find->expire_date, false);
            if ($last_time_s > 0) {
                $find->exp_val_minute += floor($last_time_s * 1440);
                SaveActivityUser::send($find->id,auth()->user()->id,'add_left_day',['day' => $last_time_s]);
            }
        }

         $find->limited = 0;

        if($find->group_id !== $findGroup->id){
            SaveActivityUser::send($find->id,auth()->user()->id,'change_group',['last' => $find->group->name,'new'=> $findGroup->name]);
        }
        $find->group_id = $findGroup->id;
        $find->first_login = NULL;

        if($findGroup->group_type == 'expire') {
            if($find->service_group !== 'wireguard') {
                $find->expire_value = $findGroup->expire_value;
                $find->expire_type = $findGroup->expire_type;
                $find->expire_date = NULL;
                $find->expire_set = 0;
            }elseif($find->service_group == 'wireguard') {

                $find->expire_value = $findGroup->expire_value;
                $find->expire_type = $findGroup->expire_type;
                $find->expire_date = Carbon::now()->addMinutes($find->exp_val_minute);
                $find->first_login = Carbon::now();
                $find->expire_set = 1;
                $find->expired = 0;
                if($find->wg){
                    $mik = new WireGuard($find->wg->server_id,'null');
                    $peers = $mik->getUser($find->wg->public_key);
                    if($peers['status']){
                        $status =  $mik->ChangeConfigStatus($find->wg->public_key,1);
                        if($status['status']) {
                            SaveActivityUser::send($find->id, auth()->user()->id, 'active_status', ['status' => 0]);
                        } else{
                         return response()->json(['status' => false,'message' => "امکان شارژ این اکانت وجود ندارد با مدیریت تماس بگیرید!"],403);
                       }
                    }else{
                        return response()->json(['status' => false,'message' => "امکان شارژ این اکانت وجود ندارد با مدیریت تماس بگیرید!"],403);

                    }
                }

            }


        }elseif($findGroup->group_type == 'volume'){
            $find->max_usage = @round((((int) $findGroup->group_volume *1024) * 1024) * 1024 );
            $find->multi_login = 5;
            $find->expire_value = $findGroup->expire_value;
            $find->expire_type = $findGroup->expire_type;
            $find->expire_date = NULL;
            $find->expire_set = 0;
            $find->usage = 0;
            $find->download_usage = 0;
            $find->limited = 0;
            $find->upload_usage = 0;
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

        if(auth()->user()->creator){
            $new =  new Financial;
            $new->type = 'minus';
            $new->price = $creator_price;
            $new->approved = 1;
            $new->description = 'کسر بابت شارژ اکانت توسط زیر نماینده :'.auth()->user()->id."( ".auth()->user()->name." )"." اکانت : ".$find->username;
            $new->creator = 2;
            $new->for = auth()->user()->creator;
            $new->save();
        }

        SaveActivityUser::send($find->id,auth()->user()->id,'re_charge');
        return response()->json(['status' => true,'message' => "اکانت با موفقیت شارژ شد!"]);
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

        $findSellectPrice = Helper::GetReselerGroupList('one',$request->group_id,auth()->user()->id);
        if($findSellectPrice){
            $price = (int) $findSellectPrice['reseler_price'];
        }
        $creator_price = 0;
        if(auth()->user()->creator){
            $income = Helper::getIncome(auth()->user()->creator);
            $creator_price = Helper::GetReselerGroupList('one',$request->group_id,auth()->user()->creator)['reseler_price'];

            if($income <  $creator_price){
                return response()->json([
                    'message' => 'به دلیل نداشتن موجودی مدیر پنل امکان انجام عملیات وجود ندارد!'
                ],403);
            }
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
        if($request->account_type){
            return $this->CreateWireGuardAccount($request,$price,$creator_price);
        }
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
            $creator_price *= $countAll;

            if(auth()->user()->creator){
                if($income <  $creator_price){
                    return response()->json([
                        'message' => 'به دلیل نداشتن موجودی مدیر پنل امکان انجام عملیات وجود ندارد!'
                    ],403);
                }
            }

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
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }

                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }

                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }

                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }
                }
            }
            if($findGroup->group_type == 'expire' || $findGroup->group_type == 'volume') {
                $req_all['expire_value'] = $findGroup->expire_value;
                $req_all['expire_type'] = $findGroup->expire_type;
                $req_all['expire_set'] = 0;
                $req_all['multi_login'] = $findGroup->multi_login;


                if($findGroup->group_type == 'volume') {
                    $req_all['multi_login'] = 5;
                    $req_all['max_usage'] =@round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
                }

            }



            $req_all['password'] = $row['password'];
            $req_all['username'] = $row['username'];

            $req_all['creator'] = auth()->user()->id;

            $create = true;





            $user = User::create($req_all);

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

        if(auth()->user()->creator){


            $new =  new Financial;
            $new->type = 'minus';
            $new->price = $creator_price;
            $new->approved = 1;
            $new->description = 'کسر بابت ایجاد اکانت زیر نماینده به شناسه:  '.auth()->user()->id." ( ".auth()->user()->name." ) "." اکانت ".$req_all['username'];
            $new->creator = 2;
            $new->for = auth()->user()->creator;
            $new->save();
        }


        return response()->json(['status' => false,'message' => "اکانت با موفقیت ایجاد شد!"]);

    }
    public function CreateWireGuardAccount(Request $request,$price = 0,$cr_price = 0){

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
            $req_all['creator'] = auth()->user()->id;

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
                    $saved->client_private_key  =  $user_wi['client_private_key'];
                    $saved->public_key = $user_wi['client_public_key'];
                    $saved->user_ip = $user_wi['ip_address'];
                    $saved->save();
                    exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");

                }
            }
        }


        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $price;
        $new->approved = 1;
        $new->description = 'کسر بابت ایجاد اکانت '.$req_all['username'];
        $new->creator = 2;
        $new->for = auth()->user()->id;
        $new->save();

        if(auth()->user()->creator){


        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $cr_price;
        $new->approved = 1;
        $new->description = 'کسر بابت ایجاد اکانت زیر نماینده به شناسه:  '.auth()->user()->id." ( ".auth()->user()->name." ) "." اکانت ".$req_all['username'];
        $new->creator = 2;
        $new->for = auth()->user()->creator;
        $new->save();
        }

        return response()->json(['status' => true, 'message' => 'کاربر با موفقیت اضافه شد!']);
    }

    public function edit(Request $request,$id){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $find = User::where('id',$id)->whereIn('creator',$sub_agents)->first();
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
        if($request->phonenumber){
            if(!preg_match('/^(09){1}[0-9]{9}+$/', $request->phonenumber)){
                return response()->json(['message' => 'لطفا یک شماره تماس معتبر وارد نمایید همراه با 0 باشد!'],403);
            }
        }
        $login = false;
        $v2_current = false;
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




        if($find->is_enabled !== ($request->is_enabled == true ? 1 : 0)){
            $find->is_enabled = ($request->is_enabled == true ? 1 : 0);
            SaveActivityUser::send($find->id,auth()->user()->id,'active_status',['status' => $find->is_enabled]);
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

        if($request->name){
            $find->name = $request->name;
        }
        if($request->phonenumber){
            $find->phonenumber = $request->phonenumber;
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
            if($login){
                $login->update_client($find->uuid_v2ray, [
                    'service_id' => $find->protocol_v2ray,
                    'username' => $request->username,
                    'multi_login' => $find->group->multi_login,
                    'totalGB' => $v2_current['total'],
                    'expiryTime' => $v2_current['expiryTime'],
                    'enable' => $request->is_enabled,
                    'reset' => 1,
                ]);

            }
        }

        $find->save();
        return response()->json([
            'message' => 'کاربر با موفقیت بروزرسانی شد!'
        ]);
    }

    public function AcctSaved(Request $request){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $savedAccounts = AcctSaved::whereIn('creator',$sub_agents)->select('*')->orderBy('id','DESC')->groupBy('groups');

        return new AcctSavedCollection($savedAccounts->paginate(20));
    }
    public function AcctSavedView(Request $request){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $findSaved = AcctSaved::where('id',$request->id)->whereIn('creator',$sub_agents)->first();
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

    public function buy_volume(Request $request,$id){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $find = User::where('id',$id)->whereIn('creator',$sub_agents)->first();
        if(!$find){
            return response()->json([
                'message' => 'کاربر یافت نشد!'
            ],404);
        }
        $price = 2300;
        $sub_sm = 1200;
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
            $login->update_client($find->uuid_v2ray, [
                'service_id' => $find->protocol_v2ray,
                'username' => $find->username,
                'multi_login' => $find->group->multi_login,
                'totalGB' => $v2_current['total'] + @round((((int) $request->volume *1024) * 1024) * 1024 ),
                'expiryTime' => $v2_current['expiryTime'],
                'enable' => ($find->is_enabled ? true : false),
            ]);
            $price = 4000;
            $sub_sm = 3500;
        }
        $total_price = (int) $request->volume * $price;
        $total_sub_price = (int) $request->volume * $sub_sm;
        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  = $icom_user - $minus_income;
        if($incom <= $total_price ){
            return response()->json(['status' => false,'message' => 'موجودی شما کافی نمیباشد!'],403);
        }

        if(auth()->user()->creator){
            $income = Helper::getIncome(auth()->user()->creator);
            if($income <  $total_sub_price){
                return response()->json([
                    'message' => 'به دلیل نداشتن موجودی مدیر پنل امکان انجام عملیات وجود ندارد!'
                ],403);
            }
        }

        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $total_price;
        $new->approved = 1;
        $new->description = 'کسر بابت خرید '.$request->volume.'گیگ حجم '.' اضافه '.$find->username;
        $new->creator = 2;
        $new->for = auth()->user()->id;
        $new->save();
        if(auth()->user()->creator) {
            $new = new Financial;
            $new->type = 'minus';
            $new->price = $total_sub_price;
            $new->approved = 1;
            $new->description = 'بابت خرید حجم اضافه '.$request->volume.' گیگ توسط  زیر نماینده '.auth()->user()->id." "."(".auth()->user()->name.")"." برای کاربر ".$find->username;
            $new->creator = 2;
            $new->for = auth()->user()->creator;
            $new->save();
        }
        SaveActivityUser::send($find->id,auth()->user()->id,'buy_new_volume',['new' => $request->volume,'last' => $this->formatBytes($find->max_usage,2)]);

        $find->max_usage += @round((((int) $request->volume *1024) * 1024) * 1024 ) ;
        if($find->max_usage >= $find->usage){
            $find->limited = 0;
        }
        $find->save();

        return response()->json(['status' => false,'message' => "حجم با موفقیت به کاربر اضافه شد!"]);

    }

    public function buy_day(Request $request,$id){
        $sub_agents = User::where('creator',auth()->user()->id)->where('role','agent')->get()->pluck('id');
        $sub_agents[] = auth()->user()->id;
        $find = User::where('id',$id)->whereIn('creator',$sub_agents)->first();
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
        $sub_price = 2500;
        $total_price = (int) $request->day * $price;
        $total_sub_price = (int) $request->day * $sub_price;
        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  = $icom_user - $minus_income;
        if($incom <= $total_price ){
            return response()->json(['status' => false,'message' => 'موجودی شما کافی نمیباشد!'],403);
        }
        if(auth()->user()->creator){
            $income = Helper::getIncome(auth()->user()->creator);
            if($income <  $total_sub_price){
                return response()->json([
                    'message' => 'به دلیل نداشتن موجودی مدیر پنل امکان انجام عملیات وجود ندارد!'
                ],403);
            }
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

        if(auth()->user()->creator){
            $new =  new Financial;
            $new->type = 'minus';
            $new->price = $total_sub_price;
            $new->approved = 1;
            $new->description = 'کسر بابت خرید روز اضافه توسط زیر نماینده به میزان '.$request->day." روز توسط نماینده به شناسه ".auth()->user()->id." (".auth()->user()->name." )"." برای کاربر ".$find->username;
            $new->creator = 2;
            $new->for = auth()->user()->creator;
            $new->save();
        }

        SaveActivityUser::send($find->id,auth()->user()->id,'buy_day_for_account',['new' => $request->day,'total' => floor($find->exp_val_minute / 1440) ]);






        return response()->json(['status' => false,'message' => "با موفقیت مقدار روز ".$request->day." به اکانت اضافه شد."]);
    }

    public function get_users_form_date(){
        $sub_agents = [];

        if(!auth()->user()->creator){
            $sub_agents =  User::where('creator',auth()->user()->id)->where('role','agent')->select(['name','id'])->get();
        }
        $s = Helper::GetReselerGroupList('list',false,auth()->user()->id);
        if (auth()->user()->creator) {
            $s = array_filter($s, function ($item) {
                return $item['status_code'] !== "2" && $item['status_code'] !== "0";
            });
        } else {
            $s = array_filter($s, function ($item) {
                return $item['status_code'] !== "3"  && $item['status_code'] !== "0";
            });
        }
        foreach ($s as $row){
            $groups_list[]   = $row;
        }



        $servers = Ras::select(['name','flag','server_location','id'])->withCount('WireGuards')->where('unlimited',1)->get();
        $v2ray_servers = Ras::select(['flag','id','server_location','name'])->where('server_type','v2ray')->get();


        $incom  = Helper::getIncome(auth()->user()->id);



        return response()->json([
            'credit' => $incom,
            'sub_agents' => $sub_agents,
            'groups_list' =>  $groups_list,
            'servers'=>  $servers,
            'v2ray_servers'=>  $v2ray_servers
        ]);


    }

    public function create_v2(Request $request){

        $group_account = false;
        $creator = false;
        if($request->creator){
            $sub_agents =  User::where('creator',auth()->user()->id)->where('id',$request->creator)->where('role','agent')->select(['name','id'])->first();
            if(!$sub_agents){
                return response()->json(['status' => false,'message' => 'خطا ! زیر نماینده یافت نشد!'],403);
            }
            $creator = $sub_agents->id;
        }
        if(!$request->type){
            return response()->json(['status' => false,'message' => 'لطفا نوع اکانت را انتخاب نمایید!'],403);
        }

        if(!$request->group_id){
            return response()->json(['status' => false,'message' => 'لطفا گروه کاربری را انتخاب نمایید!'],403);
        }
        $find_group = Helper::GetReselerGroupList('one',$request->group_id,auth()->user()->id);
        if(!$find_group){
            return response()->json(['status' => false,'message' => 'گروه کاربری یافت نشد!'],403);
        }
        $price = $find_group['reseler_price'];

        $creator_price = 0;
        $income = 0;

        if(!$request->numbers){
            return response()->json(['status' => false,'message' => 'لطفا تعداد اکانت درخواستی را انتخاب نمایید!'],403);
        }

        $numbers = (int) $request->numbers;
        $total_price = $numbers * $price;
        $total_price_creator = 0;

        if(auth()->user()->creator){
            $income = Helper::getIncome(auth()->user()->creator);
            $creator_price = Helper::GetReselerGroupList('one',$request->group_id,auth()->user()->creator)['reseler_price'];
            $total_price_creator = $numbers * $creator_price;

            if($income <  $total_price_creator){
                return response()->json([
                    'message' => 'به دلیل نداشتن موجودی مدیر پنل امکان انجام عملیات وجود ندارد!'
                ],403);
            }
        }


        $incom_sub  = Helper::getIncome(auth()->user()->id);

        if($total_price > $incom_sub){
            return response()->json(['message' => 'موجودی شما برای ایجاد اکانت کافی نمیباشد لطفا جهت افزایش موجودی به بخش امور مالی بروید!'],403);

        }



        if(!$find_group['status']){
            return response()->json(['status' => false,'message' => 'گروه کاربری برای شما یافت نشد!'],403);
        }


        if(!$request->username){
            return response()->json(['status' => false,'message' => 'لطفا نام کاربری را وارد نمایید!'],403);
        }
        $usernamePattern = '/^[a-zA-Z0-9_]+$/';
        if (!preg_match($usernamePattern, $request->username)) {
            $message = 'نام کاربری میتواند متشکل از اعداد و حروف انگلیسی A-Z و _ باشد!';
            return response()->json(['status' => false,'message' => $message],403);
        }

        if($request->random_password ){
           if(!$request->password){
               return response()->json(['status' => false,'message' => 'لطفا تعداد کاراکتر رمز را وارد نمایید بصورت عددی'],403);
           }
       }else{
           if(!$request->password){
               return response()->json(['status' => false,'message' => 'لطفا کلمه عبور را وارد نمایید'],403);
           }

            if(strlen($request->password) < 4  ){
                return response()->json(['status' => false,'message' => 'کلمه عبور نبایست کمتر از 4 کارکتر باشد'],403);
            }
       }

        if($request->type == 'wireguard'){
            if(!$request->server_id){
                return response()->json(['status' => false,'message' => 'لطفا سرور وارد گارد را انتخاب نمایید'],403);
            }
        }
        if($request->type == 'v2ray'){
            if(!$request->server_id){
                return response()->json(['status' => false,'message' => 'لطفا لوکیشن v2ray را انتخاب نمایید'],403);
            }
            if(!$request->protocol_v2ray){
                return response()->json(['status' => false,'message' => 'لطفا پرتکل اتصال v2ray را انتخاب نمایید'],403);
            }
        }


        if($request->numbers > 1){
            if(!$request->start_of){
                return response()->json(['status' => false,'message' => 'لطفا در بخش مشخصات پنل کاربری / اطلاعات ورود (شروع شمارش) را وارد نمایید.'],403);
            }

            if($request->start_of < 1 || $request->start_of > 500){
                return response()->json(['status' => false,'message' => 'حداقل شروع شمارش از 1 میباشد وبیشترین از 500.'],403);
            }

            $group_account = true;
        }

        if(strlen($request->username) < 3 || strlen($request->username) > 42 ){
            return response()->json(['status' => false,'message' => 'نام کاربری میبایست بیشتر از 3 کارکتر و کمتر از 42 کاراکتر باشد.'],403);
        }

        if($request->phone_number){
            if(!preg_match('/^(09){1}[0-9]{9}+$/', $request->phone_number)){
                return response()->json(['message' => 'لطفا یک شماره تماس معتبر وارد نمایید همراه با 0 باشد!'],403);
            }
        }




        $username_List = [];

        if($group_account){
            $start_of = (int) $request->start_of ;
            $endOF = $request->numbers + $start_of;
           // if(!($start_of % 2)){
              //  $endOF =  $endOF;
           // }

            $username = $request->username;

            for ($i= $start_of; $i < $endOF;$i++) {
                $buildUsername = $username . $i;
                $findUsername = User::where('username', $buildUsername)->first();
                if ($findUsername) {
                    return response()->json(['status' => false, 'نام کاربری ' . $buildUsername . ' موجود میباشد!']);
                }
                $password = $request->password;
                if ($request->random_password) {
                    $password = substr(rand(0, 99999), 0, (int)$request->password);
                }

                $username_List[] = ['username' => $buildUsername,'group' => $username."(".$start_of."-".$endOF.")", 'password' => $password];
            }

        }else {
            $findUsername = User::where('username', $request->username)->first();
            if($findUsername){
                return response()->json(['message' => 'کاربری با نام کاربری '.$findUsername->username.' در دیتابیس موجود است ! لطفا نام کاربری دیگری وارد نمایید .'],403);
            }
            $password = $request->password;
            if ($request->random_password) {
                $password = substr(rand(0, 99999), 0, (int)$request->password);
            }
            $username_List[] = ['username' => $request->username,'group' => $request->username,'password' => $password];
        }


        $status = false;

        // L2tp / Openvpn Account Create
        if($request->type == 'other'){
            $creator = ($creator ? $creator : auth()->user()->id);
            $status = $this->CreateOtherAccount($username_List,$creator,$request->group_id,[
                'name' => ($request->name ? $request->name : false),
                'phonenumber' => ($request->phone_number ? $request->phone_number : false),
            ]);
        }

        // V2ray Account
        if($request->type == 'v2ray'){
            $creator = ($creator ? $creator : auth()->user()->id);
            $status = $this->CreateAccountV2ray($username_List,$creator,$request->group_id,[
                'name' => ($request->name ? $request->name : false),
                'server_id' => $request->server_id,
                'protocol_v2ray' => $request->protocol_v2ray,
                'phonenumber' => ($request->phone_number ? $request->phone_number : false),
            ]);

        }

        // wireGuard Account Create

        if($request->type == 'wireguard'){
            $creator = ($creator ? $creator : auth()->user()->id);

            $status = $this->CreateWireGuardAccountV2($username_List,$creator,$request->group_id,[
                'name' => ($request->name ? $request->name : false),
                'server_id' => $request->server_id,
                'phonenumber' => ($request->phone_number ? $request->phone_number : false),
            ]);
        }


        if($status){
            $texts = ($group_account ? 'کسر بابت ایجاد اکانت گروهی '.$username_List[0]['group'] : 'کسر بابت ایجاد اکانت '.$request->username );
            $new =  new Financial;
            $new->type = 'minus';
            $new->price = $total_price;
            $new->approved = 1;
            $new->description = $texts;
            $new->creator = 2;
            $new->for = auth()->user()->id;
            $new->save();

            if(auth()->user()->creator){

                $text = ($group_account ? 'گروهی'.$username_List[0]['group'] : 'تک اکانت'.$request->username );

                $new =  new Financial;
                $new->type = 'minus';
                $new->price = $total_price_creator;
                $new->approved = 1;
                $new->description = 'کسر بابت ایجاد اکانت زیر نماینده به شناسه:  '.auth()->user()->id." ( ".auth()->user()->name." ) ".$text;
                $new->creator = 2;
                $new->for = auth()->user()->creator;
                $new->save();
            }


            return response()->json(['status' => true,'result' => $status]);
        }


        return response()->json(['message' => 'خطا در ایجاد کاربران لطفا مجددا تلاش نمایید !'],500);

    }

    public function CreateOtherAccount($username_List = [],$creator = false,$group_id = 0,$data){

        $findGroup = Groups::where('id',$group_id)->first();

        $saved_users = [];


        foreach ($username_List as $row) {
            $req_all = [];

            if($data['name']){
                $req_all['name'] = $data['name'];
            }

            if($data['phonenumber']){
                $req_all['phonenumber'] = $data['phonenumber'];
            }

            if ($findGroup->expire_type !== 'no_expire') {
                if ($findGroup->expire_type == 'minutes') {
                    $req_all['exp_val_minute'] = $findGroup->expire_value;
                } elseif ($findGroup->expire_type == 'month') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 43800);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }

                } elseif ($findGroup->expire_type == 'days') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }

                } elseif ($findGroup->expire_type == 'hours') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }

                } elseif ($findGroup->expire_type == 'year') {
                    $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
                    if($findGroup->group_volume > 0) {
                        $req_all['max_usage']  = @round(((((int) $findGroup->group_volume *1024) * 1024) * 1024 )) ;
                    }
                }
            }
            if($findGroup->group_type == 'expire' || $findGroup->group_type == 'volume') {
                $req_all['expire_value'] = $findGroup->expire_value;
                $req_all['expire_type'] = $findGroup->expire_type;
                $req_all['expire_set'] = 0;
                $req_all['multi_login'] = $findGroup->multi_login;


                if($findGroup->group_type == 'volume') {
                    $req_all['multi_login'] = 5;
                    $req_all['max_usage'] =@round((((int) $findGroup->group_volume *1024) * 1024) * 1024 ) ;
                }

            }



            $req_all['password'] = $row['password'];
            $req_all['username'] = $row['username'];

            $req_all['creator'] = $creator;
            $req_all['group_id'] = $group_id;



            $user = User::create($req_all);
            if($user) {
                $saved_users[] = ['id' => $user->id ,'username' => $row['username'],'password' => $row['password']];
                $req_all['username'] = $row['username'];
                $req_all['password'] = $row['password'];
                $req_all['groups'] = $row['group'];
                $req_all['creator'] = $creator;
                AcctSaved::create($req_all);
                SaveActivityUser::send($user->id, auth()->user()->id, 'create');
            }

        }

        if(count($saved_users) > 0){
            return $saved_users;
        }

        return false;


    }

    public function CreateWireGuardAccountV2($username_List = [],$creator = false,$group_id = 0,$data){

        $findGroup = Groups::where('id',$group_id)->first();


        foreach ($username_List as $row) {
            $req_all = [];
            $req_all['username'] = $row['username'];
            $req_all['password'] = $row['password'];
            $req_all['groups'] = $row['group'];
            $req_all['creator'] = $creator;
            AcctSaved::create($req_all);
            if($data['name']){
                $req_all['name'] = $data['name'];
            }

            if($data['phonenumber']){
                $req_all['phonenumber'] = $data['phonenumber'];
            }
            $req_all['group_id'] = $group_id;



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

            $user = User::create($req_all);
            if($user) {
                $create_wr = new WireGuard($data['server_id'], $req_all['username']);

                $user_wi = $create_wr->Run();
                if($user_wi['status']) {
                    $saved = new  WireGuardUsers();
                    $saved->profile_name = $user_wi['config_file'];
                    $saved->user_id = $user->id;
                    $saved->server_id = $data['server_id'];
                    $saved->client_private_key  =  $user_wi['client_private_key'];
                    $saved->public_key = $user_wi['client_public_key'];
                    $saved->user_ip = $user_wi['ip_address'];
                    $saved->save();
                    exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");


                    return new WireGuardConfigCollection(WireGuardUsers::where('user_id',$user->id)->get());
                }
                $user->delete();
            }
        }


        return false;
    }

    public function CreateAccountV2ray($username_List = [],$creator = false,$group_id = 0,$data){
        $findGroup = Groups::where('id',$group_id)->first();

        $findLocation = Ras::where('server_type','v2ray')->where('is_enabled',1)->where('id',$data['server_id'])->first();
        if(!$findLocation){
            return false;
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
            return false;
        }
        $expire_date = 0;

        if($findGroup->expire_value > 0){
            $expire_date = $findGroup->expire_value;
            if($findGroup->expire_type !== 'days'){
                $expire_date *= 30;
            }
        }
        $saved_account = [];

        foreach ($username_List as $row) {


            $req_all = [];
            $req_all['username'] = $row['username'];
            $req_all['password'] = $row['password'];
            $req_all['groups'] = $row['group'];
            $req_all['creator'] = $creator;
            AcctSaved::create($req_all);
            if($data['name']){
                $req_all['name'] = $data['name'];
            }

            if($data['phonenumber']){
                $req_all['phonenumber'] = $data['phonenumber'];
            }



            $add_client = $V2ray->add_client((int) $data['protocol_v2ray'],$row['username'],(int) $findGroup->multi_login,$findGroup->group_volume,$expire_date,true);
            if(!$add_client['success']){
                return false;
            }
            $client = $V2ray->get_user((int) $data['protocol_v2ray'],$row['username']);

            $req_all['v2ray_config_uri'] = $client['user']['url'];
            $req_all['group_id'] = $group_id;
            $req_all['protocol_v2ray'] = $data['protocol_v2ray'];
            $req_all['v2ray_location'] = $data['server_id'];
            $req_all['password'] = $row['password'];
            $req_all['username'] = $row['username'];
            $req_all['uuid_v2ray'] = $add_client['uuid'];
            $req_all['service_group'] = 'v2ray';

            $usr = User::create($req_all);
            if($usr){
                $saved_account[] = [
                  'id' =>   $usr->id,
                  'username' =>   $row['username'],
                  'password' =>   $row['password'],
                  'user' =>  $client['user']
                ];
            }
        }

        return $saved_account;

    }


}
