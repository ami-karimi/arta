<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\RadAcct;
use App\Models\Ras;
use Illuminate\Http\Request;
use App\Models\User;
use App\Utility\Tokens;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class AuthController extends Controller
{
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
        if($request->username == ""){
            return response()->json(['error' => true, 'result' => 'لطفا نام کاربری را وارد نمایید!'],403);
        }
        if($request->password == ""){
            return response()->json(['error' => true, 'result' => 'لطفا کلمه عبور را وارد نمایید!'],403);
        }
        $left_date = null;
        $findUser = User::where('username',$request->username)->where('password',$request->password)->first();
        if($findUser){
            if(!$findUser->is_enabled){
                return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!'],403);
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
            $left_bandwidth = 'نامحدود';
            $total_bandwidth = 'نامحدود';
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



            return  response()->json([
               'error' => false,
               'result' =>  [
                  'token' => $ts->token,
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
                 ]
            ]);
        }

        return response()->json(['error' => true, 'result' => 'حساب کابری شما یافت نشد!']);


    }

    public function is_valid_token(Request $request){
        if(!$request->token){
            return response()->json(['error' => true, 'result' => 'توکن یافت نشد'],403);
        }
        $token = new Tokens();
        $check = $token->checkToken($request->token);
        if(!$check){
            return response()->json(['error' => true, 'result' => 'توکن نامعتبر میباشد '],403);
        }
        $findUser = User::where('id',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['error' => true, 'result' => 'کاربر یافت تشد! '],403);
        }
        if(!$findUser->is_enabled){
            return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!']);
        }
        $expire_date = $findUser->expire_date ;
        $total_bandwidth = 'نامحدود';

        $left_bandwidth = 'نامحدود';
        $usage = '---';
        $down_and_up  = '0M/0M';

        $end_bandwidth = false;
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
            'error' => false,
            'result' =>  [
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
            ]
        ]);

    }

    public function get_servers(Request $request){
        if(!$request->token){
            return response()->json(['error' => true, 'result' => 'توکن یافت نشد'],403);
        }
        $token = new Tokens();
        $check = $token->checkToken($request->token);
        if(!$check){
            return response()->json(['error' => true, 'result' => 'توکن نامعتبر میباشد '],403);
        }
        $findUser = User::where('id',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['error' => true, 'result' => 'کاربر یافت تشد! '],403);
        }
        if(!$findUser->is_enabled){
            return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!']);
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


        return response()->json(['error' => false,'result' => $server_lists
        ]);
    }
    public function get_notifications(Request $request){

    }

}
