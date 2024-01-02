<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\RadPostAuth;
use App\Models\Ras;
use App\Models\Settings;
use App\Models\WireGuardUsers;
use App\Utility\Helper;
use App\Utility\SaveActivityUser;
use App\Utility\WireGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Utility\V2rayApi;
use App\Utility\Mikrotik;
use App\Models\Stogram;
use App\Models\User;
use App\Models\backUsers;
use App\Models\UserGraph;
use App\Models\Activitys;
use App\Models\AcctSaved;
use App\Utility\Sms;
use phpseclib3\Net\SSH2;
use phpseclib3\Exception\UnableToConnectException;
use App\Utility\SshServer;
use App\Utility\V2raySN;
use App\Utility\Ftp;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Admin\MonitorigController;

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


        $v2ray_users = User::where('service_group','v2ray')->get();
        foreach ($v2ray_users as $row){
            $login = new V2raySN(
                [
                    'HOST' => $row->v2ray_server->ipaddress,
                    "PORT" => $row->v2ray_server->port_v2ray,
                    "USERNAME" => $row->v2ray_server->username_v2ray,
                    "PASSWORD" => $row->v2ray_server->password_v2ray,
                    "CDN_ADDRESS"=> $row->v2ray_server->cdn_address_v2ray,
                ]
            );
            if($login->error['status']){
                continue;
            }
            $v2_current = $login->get_client($row->username);
            $expire_time = ((int) $v2_current['expiryTime'] > 0 ? (int) $v2_current['expiryTime'] /1000 : 0);
            if(!$expire_time){
                $create_date =  $left = Carbon::now()->diffInDays($row->created_at, false);

                echo $create_date;
            }
        }

        //header('Content-Type: application/json; charset=utf-8');

       // echo json_encode($V2ray->get_user(2,'mywsp'));

       // Helper::get_db_backup();
       // Helper::get_backup();

        /*
        $monitorin = new MonitorigController();
        $re = $monitorin->KillUser((object) ['l2tp_address' => 's2.arta20.xyz'],'amirtld');

        print_r($re);
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
