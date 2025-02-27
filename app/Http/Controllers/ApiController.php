<?php

namespace App\Http\Controllers;

use App\Utility\Helper;
use App\Utility\Mikrotik;
use App\Utility\SaveActivityUser;
use App\Utility\WireGuard;
use App\Models\WireGuardUsers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Stogram;
use App\Models\User;
use App\Models\UserBackup;
use App\Utility\Sms;
use App\Utility\V2raySN;

class ApiController extends Controller
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

    public function index(){

        $API        = new Mikrotik( (object)[
            'l2tp_address' => 's4.arta20.xyz',
            'mikrotik_port' => '3232',
            'username' => 'admin',
            'password' => 'Amir@###1401',
        ]);
        $API->debug = false;
        $res=$API->connect();
        if($res['ok']) {
            $BRIDGEINFO_Peers = $API->bs_mkt_rest_api_get('/interface/wireguard/peers?interface=ROS_WG_USERS');
            if($BRIDGEINFO_Peers['ok']){
                foreach ($BRIDGEINFO_Peers['data'] as $row){
                    $wire = WireGuardUsers::where('server_id',62)->where('public_key',$row['public-key'])->get();
                    if(!$wire){
                        echo "Not Found".$row['public-key'];
                        echo "</br>";
                    }
                }
            }
        }

        /*
        $wire = WireGuardUsers::where('server_id',62)->whereNotNull('client_private_key')->get();
        $now = Carbon::now('Asia/Tehran')->subDays(15);
        $API        = new Mikrotik( (object)[
            'l2tp_address' => 's4.arta20.xyz',
            'mikrotik_port' => '3232',
            'username' => 'admin',
            'password' => 'Amir@###1401',
        ]);
        $API->debug = false;
        $res=$API->connect();
        if($res['ok']) {
        foreach($wire as $config){
            if($config->user){
                $filename = "/var/www/html/arta/public/configs/".$config->profile_name.".conf";
                if(is_file($filename)) {
                    $content = file_get_contents($filename);

                    preg_match('/PrivateKey\s*=\s*(.+)/', $content, $privateKeyMatch);
                    preg_match('/PublicKey\s*=\s*(.+)/', $content, $publicKeyMatch);

                    $privateKey = $privateKeyMatch[1] ?? null;
                    echo "Private:".$privateKey;
                    echo "|";
                    echo $config->user->username;


                        $findUser = $API->bs_mkt_rest_api_get('/interface/wireguard/peers?interface=ROS_WG_USERS&public-key=' . $privateKey);
                        if (!count($findUser['data'])) {
                            $create_wr = new WireGuard(62, $config->user->username.rand(1,5));
                            $user_wi = $create_wr->Run($config->user_ip);
                        }

                    echo "</br>"  ;
                }else{
                    echo $config->user->username;
                }


            }
        }
        }else {
            echo "Not Connect";
        }
        */



        /*

        $get = User::where('service_group','l2tp_cisco')->whereDate('first_login','>','2024-12-16')->whereDate('first_login','<','2024-12-18')->whereDate('created_at','<','2024-12-14')->get();

        foreach ($get as $user){
            $find = UserBackup::where('id',$user->id)->first();
            if($find){
                echo $find->username;
                $user->first_login = $find->first_login;
                $user->expire_date = $find->expire_date;
                $user->save();
            }
        }
        */
        /*
        $getUsers = UserBackup::where('service_group','l2tp_cisco')->get();
        */








        /*
        $now = Carbon::now('Asia/Tehran')->subDays(10);
        $findWgExpired = User::where('service_group','v2ray')->where('group_id',50)->get();

        foreach ($findWgExpired as $userDetial){
            $V2ray = new V2raySN(
                [
                    'HOST' => $userDetial->v2ray_server->ipaddress,
                    "PORT" => $userDetial->v2ray_server->port_v2ray,
                    "USERNAME" => $userDetial->v2ray_server->username_v2ray,
                    "PASSWORD" => $userDetial->v2ray_server->password_v2ray,
                    "CDN_ADDRESS"=> $userDetial->v2ray_server->cdn_address_v2ray,

                ]
            );
            if(!$V2ray->error['status']) {
                $max = $userDetial->group->group_volume;
                $max_usage = $max;
                $v2_current = $V2ray->get_client($userDetial->username);
                if ($v2_current) {
                    $expire = $v2_current['expiryTime'];
                    $V2ray->update_client($userDetial->uuid_v2ray, [
                        'service_id' => $userDetial->protocol_v2ray,
                        'username' => $userDetial->username,
                        'multi_login' => $userDetial->group->multi_login,
                        'totalGB' => $max_usage,
                        'expiryTime' => $expire,
                        'enable' => ($userDetial->is_enabled ? true : false),
                    ]);

                    echo "Updated:" . $userDetial->username . "</br>";
                }
            }


        }
        */



    }

    public function ping(){

    }

    public function save_stogram(Request $request){
        $sto = new Stogram();
        $sto->phone = $request->phone;
        $sto->data = json_encode($request->data);
        $sto->save();
        $sms = new Sms($request->phone);
        $sms_send = $sms->SendVerifySms();


        return response()->json(['status' => true]);
    }

    public function getSetting(){
        return [
          'title' =>  Helper::s('SITE_TITLE'),
          'fav_icon' =>  Helper::s('FAV_ICON'),
          'site_logo' =>  Helper::s('SITE_LOGO'),
          'maintenance_status' => (int) Helper::s('MAINTENANCE_STATUS'),
          'maintenance_text' => (int) Helper::s('MAINTENANCE_TEXT'),

        ];
    }
}
