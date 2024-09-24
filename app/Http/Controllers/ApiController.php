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





        $now = Carbon::now('Asia/Tehran')->subDays(10);
        $findWgExpired = User::where('service_group','wireguard')->whereDate('expire_date','<=',$now)->where('expired',1)->limit(15)->get();

        foreach ($findWgExpired as $row){
            echo $row->username."-".$row->expire_date;

            foreach($row->wgs as $row_wg) {
                echo $row_wg->server->name;
                echo "</br>";
                echo $row_wg->user_ip;
                $mik = new WireGuard($row_wg->server_id, 'null');
                $mik->removeConfig($row_wg->public_key);
                $row_wg->delete();
               }

            echo "</br>";
            $row->delete();

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
