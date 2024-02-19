<?php

namespace App\Http\Controllers;

use App\Utility\Helper;
use App\Utility\SaveActivityUser;
use App\Utility\WireGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Stogram;
use App\Models\User;
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

        /*

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
            if($v2_current) {
                $expire_time = ((int)$v2_current['expiryTime'] > 0 ? (int)$v2_current['expiryTime'] / 1000 : 0);



                    $days = 30;
                    $tm = floor(microtime(true) * 1000);
                    $expiretime = $tm + (864000 * $days * 100) ;


                    $login->update_client($row->uuid_v2ray, [
                        'service_id' => $row->protocol_v2ray,
                        'username' => $row->username,
                        'multi_login' => $row->group->multi_login,
                        'totalGB' =>   @round((((int) $row->group->group_volume *1024) * 1024) * 1024 ),
                        'expiryTime' => $expiretime,
                        'enable' => ($row->is_enabled ? true : false),
                    ]);

            }
        }
        */

        //header('Content-Type: application/json; charset=utf-8');

       // echo json_encode($V2ray->get_user(2,'mywsp'));

       // Helper::get_db_backup();
       // Helper::get_backup();

        /*
        $monitorin = new MonitorigController();
        $re = $monitorin->KillUser((object) ['l2tp_address' => 's2.arta20.xyz'],'amirtld');

        print_r($re);
        */

        $now = Carbon::now()->format('Y-m-d');
        $findWgExpired = User::where('service_group','wireguard')->whereDate('expire_date','<=',$now)->where('expired',0)->get();

        foreach ($findWgExpired as $row){
            echo $row->username;
            foreach($row->wgs as $row_wg) {
                $mik = new WireGuard($row_wg->server_id, 'null');
                $peers = $mik->getUser($row_wg->public_key);
                $row_wg->is_enabled = 0;
                $row_wg->save();
                if ($peers['status']) {
                    $status = $mik->ChangeConfigStatus($row_wg->public_key, 0);
                    if ($status['status']) {
                        SaveActivityUser::send($row->id, 2, 'active_status', ['status' => 0]);
                        $row->expired = 1;
                        $row->save();
                    }
                }
            }

        }
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
