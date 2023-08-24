<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\RadAcct;
use App\Models\Ras;
use Illuminate\Http\Request;
use App\Models\User;
use App\Utility\Tokens;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class AuthController extends Controller
{
    public $ANDROID_AVAILABLE_VERSIONS =["0.1","1.0"];

    public $panel_link = 'https://www.arta20.top/t/';

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

    public function sign_in(Request $request){

        if(!$request->version){
            return response()->json(['status' => false, 'result' => 'Bad request'],400);
        }

        if(!in_array($request->version,$this->ANDROID_AVAILABLE_VERSIONS)){
            return response()->json(['status' => true, 'result' =>
                [
              'update'=> true,
              'link' => 'https://www.arta20.xyz/download/last.apk',
            ]
            ],200);
        }

        if($request->username == ""){
            return response()->json(['status' => false, 'result' => [
                'message' => 'لطفا نام کاربری را وارد نمایید',
            ]],200);
        }
        if($request->password == ""){
            return response()->json(['status' => false, 'result' => [
                'message' => 'لطفا کلمه عبور را وارد نمایید',
            ]],200);
        }
        $left_date = null;
        $findUser = User::where('username',$request->username)->where('password',$request->password)->first();
        if($findUser){
            if(!$findUser->is_enabled){
                return response()->json(['status' => false, 'result' => [
                    'message' => 'اکانت شما غیرفعال شده است لطفا جهت رفع مشکل با مدیریت تماس بگیرید',
                ]],403);
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
            $left_bandwidth = '∞';
            $total_bandwidth = '∞';
            $usage = '---';
            $down_and_up  = '0M/0M';

            $end_bandwidth = false;
            if($findUser->group->group_type == 'volume'){
                $left_bandwidth = $this->formatBytes($findUser->max_usage - $findUser->usage);
                $usage = $this->formatBytes($findUser->usage);
                if($findUser->usage >= $findUser->max_usage){
                    $end_bandwidth = true;
                }
                $total_bandwidth = $this->formatBytes($findUser->max_usage);
                $down_and_up =  $this->formatBytes($findUser->download_usage)."/". $this->formatBytes($findUser->upload_usage);

            }
            $onlineCount = RadAcct::where('username',$findUser->username)->where('acctstoptime',NULL)->count();

            $expired = false;
            if($findUser->expire_set) {
                $left_date = Carbon::now()->diffInDays($expire_date, false);

                if($left_date <= 0){
                    $expired = true;
                }
            }

            $user_can_connect = true;

            if($expired || $end_bandwidth){
                $user_can_connect = false;
            }

            $notif_count = Blog::where('show_for','mobile')->where('published',1);
            if($request->notif_date){
                $notif_count->where('created_at','>',Carbon::parse($request->notif_date));
            }

            $count_not_read = $notif_count->count();

            return  response()->json([
               'status' => true,
               'result' =>  [
                   'link' => null,
                  'recommend' => $this->get_reccomecServer(),
                  'token' => $ts->token,
                  'panel_link'=> $this->panel_link.$ts->token,
                  'user_type' => $findUser->group->group_type,
                  'username' => $findUser->username,
                  'group_name' => $findUser->group->name,
                  'multi_login' => $findUser->multi_login,
                  'online_count' => $onlineCount,
                  'expire_date' => (!$findUser->expire_set ? 'بعد اولین اتصال' : Carbon::parse($expire_date)->format('Y-m-d H:i')),
                  'j_expire_date' => (!$findUser->expire_set ? 'بعد اولین اتصال' :  Jalalian::forge($expire_date)->__toString() ),
                  'left_day' =>  (!$findUser->expire_set ? '--- ' : $left_date),
                  'left_bandwidth' => $left_bandwidth,
                  'total_bandwidth' => $total_bandwidth,
                  'down_and_up' => $down_and_up,
                  'usage' => $usage,
                  'expired' => $expired,
                  'end_bandwidth' => $end_bandwidth,
                  'user_can_connect' => $user_can_connect,
                   'count_notification' => $count_not_read,
                 ]
            ]);
        }

        return response()->json(['status' => false, 'result' => 'حساب کابری شما یافت نشد!'],404);


    }

    public function get_reccomecServer(){
        $serversList = Ras::where('config','!=','')->where('in_app',1)->where('is_enabled',1)->get();
        $server_lists = [];

        $last_select = 0;
        $key = 0;
        foreach ($serversList as $keys =>  $nas){
            $online_count = $nas->getUsersOnline()->count();
            $load = 100;
            $max_online = 120;
            $tb = ($online_count * 100 ) / $max_online;
            $end_tb = 100 - $tb;
            $end_tb =($end_tb < 0 ? 0 : $end_tb);
            if($end_tb >= $last_select){
                $last_select = $end_tb;
                $key = $keys;
            }
            $server_lists[] = [
                'name' =>   $nas->name,
                'id' => $nas->id,
                'load' => floor($end_tb),
                'location' =>   $nas->server_location,
                'config' => $nas->config,
                'flag' => $nas->flag,
                'selected' => false,
            ];
        }

        $server_lists[$key]['selected'] = true;

        return $server_lists[$key];
    }


    public function is_valid_token(Request $request){

        if(!$request->version){
            return response()->json(['status' => false, 'result' => 'Bad request'],400);
        }


        if(!in_array($request->version,$this->ANDROID_AVAILABLE_VERSIONS)){
            return response()->json(['status' => true, 'result' =>
                [
                    'update'=> true,
                    'link' => 'https://www.arta20.xyz/download/last.apk',
                ]
            ],200);
        }

        if(!$request->token){

            return response()->json([
               'status' => true,
                'result' => [
                    'login'=> true,
                    'message' => 'Invalid token',
                ]
            ],200);
        }
        $token = new Tokens();
        $check = $token->checkToken($request->token);
        if(!$check){
            return response()->json(['status' => true, 'result' => [
                'login'=> true,
                'message' => 'Invalid token',
            ]
            ],200);
        }
        $findUser = User::where('id',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['status' => true, 'result' =>[
                'login'=> true,
                'message' => 'کاربر یافت نشد',
            ]],200);
        }
        if(!$findUser->is_enabled){
            return response()->json(['status' => true, 'result' =>[
                'login'=> true,
                'message' => 'اکانت شما غیرفعال شده است لطفا جهت رفع مشکل با مدیریت تماس بگیرید',
            ]]);
        }
        $expire_date = $findUser->expire_date ;
        $total_bandwidth = '∞';

        $left_bandwidth = '∞';
        $usage = '---';
        $down_and_up  = '0M/0M';

        $end_bandwidth = false;
        $notif_count = Blog::where('show_for','mobile')->where('published',1);
        if($request->notif_date){
            $notif_count->where('created_at','>',Carbon::parse($request->notif_date));
        }

        $count_not_read = $notif_count->count();

        if($findUser->group->group_type == 'volume'){
            $left_bandwidth = $this->formatBytes($findUser->max_usage - $findUser->usage);
            $usage = $this->formatBytes($findUser->usage);
            if($findUser->usage >= $findUser->max_usage){
                $end_bandwidth = true;
            }
            $total_bandwidth =  $this->formatBytes($findUser->max_usage);
            $down_and_up =  $this->formatBytes($findUser->download_usage)."/". $this->formatBytes($findUser->upload_usage);
        }
        $onlineCount = RadAcct::where('username',$findUser->username)->where('acctstoptime',NULL)->count();

        $expired = false;
        $left_date = null;
        if($findUser->expire_set) {
            $left_date = Carbon::now()->diffInDays($expire_date, false);

            if($left_date <= 0){
                $expired = true;
            }
        }

        $user_can_connect = true;

        if($expired || $end_bandwidth){
            $user_can_connect = false;
        }



        return  response()->json([
            'status' => true,
            'result' =>  [
                'link' => null,
                'login'=> false,
                'panel_link'=> $this->panel_link.$request->token,
                'recommend' => $this->get_reccomecServer(),
                'user_type' => $findUser->group->group_type,
                'username' => $findUser->username,
                'group_name' => $findUser->group->name,
                'multi_login' => $findUser->multi_login,
                'online_count' => $onlineCount,
                'expire_date' => (!$findUser->expire_set ? 'بعد اولین اتصال' : Carbon::parse($expire_date)->format('Y-m-d H:i')),
                'j_expire_date' => (!$findUser->expire_set ? 'بعد اولین اتصال' :  Jalalian::forge($expire_date)->__toString() ),
                'left_day' =>  (!$findUser->expire_set ? '--- ' : $left_date),
                'left_bandwidth' => $left_bandwidth,
                'total_bandwidth' => $total_bandwidth,
                'down_and_up' => $down_and_up,
                'usage' => $usage,
                'expired' => $expired,
                'end_bandwidth' => $end_bandwidth,
                'user_can_connect' => $user_can_connect,
                'count_notification' => $count_not_read,
            ]
        ]);

    }

    public function get_servers(Request $request){
        if(!$request->version){
            return response()->json(['status' => false, 'result' => 'Bad request'],400);
        }

        if(!in_array($request->version,$this->ANDROID_AVAILABLE_VERSIONS)){
            return response()->json(['status' => true, 'result' =>
                [
                    'update'=> true,
                    'link' => 'https://www.arta20.xyz/download/last.apk',
                ]
            ],200);
        }

        if(!$request->token){
            return response()->json(['status' => true, 'result' => [
                'login'=> true,
                'message' => 'Invalid token',
            ]],200);
        }
        $token = new Tokens();
        $check = $token->checkToken($request->token);
        if(!$check){
            return response()->json(['status' => true, 'result' => [
                'login'=> true,
                'message' => 'Invalid token',
            ]
            ]);
        }
        $findUser = User::where('id',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['status' => false, 'result' =>[
                'login'=> true,
                'message' => 'کاربر یافت نشد',
            ]],404);
        }
        if(!$findUser->is_enabled){
            return response()->json(['status' => false, 'result' =>[
                'login'=> true,
                'message' => 'اکانت شما غیرفعال شده است لطفا جهت رفع مشکل با مدیریت تماس بگیرید',
            ]],403);
        }

         $serversList = Ras::where('config','!=','')->where('in_app',1)->where('is_enabled',1)->get();
         $server_lists = [];

         $last_select = 0;
         $key = 0;
         foreach ($serversList as $keys =>  $nas){
             $online_count = $nas->getUsersOnline()->count();
             $load = 100;
             $max_online = 120;
             $tb = ($online_count * 100 ) / $max_online;
             $end_tb = 100 - $tb;
             $end_tb =($end_tb < 0 ? 0 : $end_tb);
             if($end_tb >= $last_select){
                 $last_select = $end_tb;
                 $key = $keys;
             }

             $config_convert = base64_decode($nas->config);
             $sr = preg_replace("/ /m", '\n', $config_convert);
             $server_lists[] = [
               'name' =>   $nas->name,
               'id' => $nas->id,
               'load' => floor($end_tb),
               'location' =>   $nas->server_location,
               'config' => "client
dev tun
proto tcp
remote s1.arta20.xyz 110
resolv-retry infinite
remote-random
nobind
tun-mtu 1500
tun-mtu-extra 32
mssfix 1450
persist-key
persist-tun
ping 15
ping-restart 0
ping-timer-rem
reneg-sec 0
remote-cert-tls server
auth-user-pass
verb 3
pull
fast-io
cipher AES-256-CBC
auth SHA512
redirect-gateway def1
<ca>
-----BEGIN CERTIFICATE-----
MIIDDzCCAfegAwIBAgIIEQM4BZdb5+0wDQYJKoZIhvcNAQELBQAwDTELMAkGA1UE
AwwCQ0EwHhcNMTkwNTI1MDg1NTUzWhcNMjkwNTIyMDg1NTUzWjANMQswCQYDVQQD
DAJDQTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAL/L7tAhIptwKsvB
LtGqxOdP+ToQOXxCXNIU5wUsQqBy+R6R4XHNHB2j2DfxMXzgdlnJr1+9dZCmcR4v
wP00JsQ4lPduEDwECvahaYAYdnWwOskBbbDTVSdTnanV0jx6ecKM7oWdaLisDnyc
m69hY5Cn7SWPYKkspH+7lDDgS2oYh2acLSRn5KQBkrmhsvmhlaFCIHlGhmm3NQBJ
GvlBRuTEGj72iTkjqm/yPdsf22Z+KuVM6oUQ9OdJIE1WS2/2/AEgfFDYPatdtdWg
NWefRzKmNgOiU3K1TzOn2dmEO3U+a2ndwPx944eAVw83yJL1uLTg5Kyd7i/pITY8
YBUqwG0CAwEAAaNzMHEwDwYDVR0TAQH/BAUwAwEB/zAOBgNVHQ8BAf8EBAMCAaYw
HQYDVR0OBBYEFCguAqSxsFq3eeC6wtZUklF5Lo4cMC8GA1UdHwQoMCYwJKAioCCG
Hmh0dHA6Ly9zMS5hcnRhMjAueHl6L2NybC8xLmNybDANBgkqhkiG9w0BAQsFAAOC
AQEAqBYbV9pBIVvzj6CA6uVtzXnFAeLK8wgCK166aFeUsFr1mkZeWqj7qOWeYoeT
125BVgw2unvkVs5RTgU6tJICxvWrofp4F+yD+gBG1TnJDyvfxqdwCUhDX+p7blLN
JMV18fEr33l06PLSDNwygIS9l2MrRQarM0TCJ/emKXVN/A+8AMZUMh7wIJvEAHb4
clr1bJCmlEMVtHL2fbs3w9BV0xUeKHyNFLKFSZtSkvZrhOFuyzvMyV/5vPT0qtNb
OXuHWbImC6NBzcxhePfe56UG9Sb9agveXsUEXVT31NACb0bzNMSwnh4aCOGCTbAp
N6sVJlVv/+nNT3IZ6FDmhOZIJg==
-----END CERTIFICATE-----

</ca>
<cert>
-----BEGIN CERTIFICATE-----
MIIDJDCCAgygAwIBAgIIIL4Q0dyYAXcwDQYJKoZIhvcNAQELBQAwDTELMAkGA1UE
AwwCQ0EwHhcNMTkwNTI1MDg1NjQzWhcNMjkwNTIyMDg1NjQzWjARMQ8wDQYDVQQD
DAZDbGllbnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCmzpMY2OZh
qnT36rFfWWWPDEht6gbEiJU/7gGbeqsGZg+Bhh4x05yrPWMxVzHTxCXfmeKoDHCx
wvaSbvGs9SqjDuDCp5EHk/Lz2cAVYH+xp8qEm+9eKQ9dCmCwg14dn2Uq1e6iKfpX
YZX+5sky0B9gfqJ6sEKL7lCtqLLwTlFE3RfJogqs558VeoMd0rQhgeIeypiXrZqL
G76gGLYDUqysWMpe8+7Ah+Y9+r6uHDGbQ1t/Ffee48IYBKtxnfrE7wam1Xj3BHfm
dC4F28WUWR6Sk84Pb0NH5lk5P2dLEyhyHl8N7022+m6QtY+21+tW3TNlDUBYHM2G
l7sdtvNG1ALPAgMBAAGjgYMwgYAwDwYDVR0TAQH/BAUwAwEB/zAOBgNVHQ8BAf8E
BAMCAbYwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMB0GA1UdDgQWBBRV
mEPYDjYQqmHs9RGpVAauEyYoJDAfBgNVHSMEGDAWgBQoLgKksbBat3ngusLWVJJR
eS6OHDANBgkqhkiG9w0BAQsFAAOCAQEAJWppBxOdGFucXaA2rYPSeO6GTcO2WfGY
VZuSZhGDu58Uohm4Auaabh367hdX6ZYyFuNRQfPS/kKcOp2IPySvivIf5fcIxktD
ja2A6IsjvMtozZDHmZQtwgLgvqpwWPr0kAcQ4V7IesBEOiOw5D1j3gVsDIa1U9nK
RAQjx1GxcCjTFytIF8YOXOzlY+kSEA7ioQpP40UJaHGYRhWBOWrWYEBKzGEh2UCs
jIyU1f3E79TzILJz95sD5UIz7IO03XVpZMqT2SP3ayZY+U1md/3j2oSmlIwcCp/J
VbruJxrpDaDjYjkXvWpaEW3gt0A2Yqdy2DfudrxDTqegOUy20BK0uw==
-----END CERTIFICATE-----

</cert>
<key>
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAps6TGNjmYap09+qxX1lljwxIbeoGxIiVP+4Bm3qrBmYPgYYe
MdOcqz1jMVcx08Ql35niqAxwscL2km7xrPUqow7gwqeRB5Py89nAFWB/safKhJvv
XikPXQpgsINeHZ9lKtXuoin6V2GV/ubJMtAfYH6ierBCi+5Qraiy8E5RRN0XyaIK
rOefFXqDHdK0IYHiHsqYl62aixu+oBi2A1KsrFjKXvPuwIfmPfq+rhwxm0NbfxX3
nuPCGASrcZ36xO8GptV49wR35nQuBdvFlFkekpPOD29DR+ZZOT9nSxMoch5fDe9N
tvpukLWPttfrVt0zZQ1AWBzNhpe7HbbzRtQCzwIDAQABAoIBABSKP2bB3qyMFtco
WSsKkQzqUEjolmjBAM/ceOoyUrj4/FPQtgsgqZwUdRBwUjxnXNqJ0nUrAv2Aqmgh
rTTFA7kMbfTKOXubZkFMwPBg75hqtu9ZXEJWAARO8NULeB1hsU1zBm2FicQUyimX
NZNCOXriXROKfMdKUzjvGwmoOy2lcPfEjKCY2ue7BvGOa5Hg3em71yXWr+9Z16in
10sbly59OMp4EsNmOiG5Sa3ZkFRarbDY8TvnQS5Hmpvc1EiChYINWH3drUZKsp9U
GtU1kdbwDAX6QBCjSQ8brI69QkjJFtCqaNlQZdcSqMGRTWXSrNKuTyxRYZsC79c3
sJfXuoECgYEA3DFp/aa4kDvdtMFC6SwZ/uonQADv+Wp02iqMakupZSWONXpmWaJP
lNimyH6p3zjFkVdnH4XvyGsdQo6F/pwhF++FnelIoTU/L3mgtzNqq4O3+4mTosph
axv5W90+4XsCD80NS/+hEyLsRqSYW0iefFg0WjOTtYjEJp58BopAEiECgYEAwe61
qL6yqI7wqKglCvcKoVSvf98St1UAQRKnQu6TknPO+23ye/J5nhoqCyZFZaywm4m/
p4kLal5FV+aZ2hxHvvzByQ9xAD/lNxOJFpW0bXVz3oNf+LbEYnWxRu1b9Aqbp/UH
Aq04xfg1BS2XVvunQG3BTVj8oRVY6gQI/+lsVu8CgYAsJshe8RAu86IX/WyCPrKT
t7XZEpcLxvnZSRDQu40i1+308S8WqAIXEX4X07YSKVsMMp9d5chXwoqibtuVWw8T
spZzPHSwxnF9/oBoW6n27Dl2+XYd/UCdboWIkwtpwPV/35jb9U0B/k2sOJLIMv58
Zl9Q+uiSTPMv3zV1RkFkoQKBgQCscXmg0ekFTw0Zu2Is7NzL9gSUHKSE1pWCR3bp
YkFgkY+0LODYbBTOjA9kmKROs47az1LXQ1oePDNG5StbMZhucExUX2GoyigkoD9f
EME+L5lXe9RD0SixMFvxaLBCQYiFgbC5JZR9HKbwssiGtQDUnoOrJnyFM/k7JVln
TYVjiwKBgF+VBPOmgpBFXj/tmweyyzkw8SFuF5X4M72sJaGTnhpg3mkqLsntWoF2
hhC55ODZTmu3ApIqM8AyK+OFj0N5BefyYoOLcTQ+ttb2LkaB765JxQBIYJ6NA/pO
HAhnCawZ3hux5U89FGJmTEusXpNqq4XjOvc9gGyuJUwZwgH7X6Iy
-----END RSA PRIVATE KEY-----

</key>
",
               'flag' => $nas->flag,
               'selected' => false,
             ];
         }

        $server_lists[$key]['selected'] = true;


        return response()->json(['status' => true,'result' => $server_lists
        ]);
    }
    public function get_notifications(Request $request){
        if(!$request->version){
            return response()->json(['status' => false, 'result' => 'Bad request'],400);
        }


        if(!in_array($request->version,$this->ANDROID_AVAILABLE_VERSIONS)){
            return response()->json(['status' => true, 'result' =>
                [
                    'update'=> true,
                    'link' => 'https://www.arta20.xyz/download/last.apk',
                ]
            ],200);
        }

        if(!$request->token){
            return response()->json(['status' => true, 'result' => [
                'login'=> true,
                'message' => 'Invalid token',
            ]]);
        }
        $token = new Tokens();
        $check = $token->checkToken($request->token);
        if(!$check){
            return response()->json(['status' => true, 'result' => [
                'login'=> true,
                'message' => 'Invalid token',
            ]
            ]);
        }
        $findUser = User::where('id',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['status' => false, 'result' =>[
                'login'=> true,
                'message' => 'کاربر یافت نشد',
            ]],404);
        }
        if(!$findUser->is_enabled){
            return response()->json(['status' => false, 'result' =>[
                'login'=> true,
                'message' => 'اکانت شما غیرفعال شده است لطفا جهت رفع مشکل با مدیریت تماس بگیرید',
            ]],403);
        }

        $notif_count = Blog::where('show_for','mobile')->where('published',1);
        if($request->notif_date){
            $notif_count->where('created_at','>',Carbon::parse($request->notif_date));
        }

        $count_not_read = $notif_count->get();
        $lists = [];

        foreach ($count_not_read as $row){
            $lists[] = [
              'id' => $row->id,
              'title' => $row->title,
              'content' => $row->content,
              'j_date' => Jalalian::forge($row->created_at)->format('%B %d، %Y'),
              'date' =>  Carbon::parse($row->created_at)->format('Y-m-d H:i:s')
            ];
        }

        return response()->json(['status'=> true,'result' => $lists]);
    }

    public function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return the server IP if the client IP is not found using this method.
    }
    public function get_ip(Request $request){
        return response()->json(['ip' => $this->getIp()]);
    }
}
