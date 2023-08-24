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

             $config_convert = $nas->config;
             $server_lists[] = [
               'name' =>   $nas->name,
               'id' => $nas->id,
               'load' => floor($end_tb),
               'location' =>   $nas->server_location,
               'config' => $config_convert,
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
