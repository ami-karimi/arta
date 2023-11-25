<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\RadPostAuth;
use App\Models\Ras;
use App\Models\WireGuardUsers;
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

        $V2ray = new V2raySN([
            'HOST' =>  "185.162.235.203",
            "PORT" => "2084",
            "USERNAME" => 'amirtld',
            "PASSWORD"=> 'Amir102040',
        ]);
        if(!$V2ray->error['status']){
            print_r($V2ray->get_user(4,'v2test')['user']);

        }else{
            return response()->json(['status' => false,'message'=> $V2ray->error['message']]);
        }


    }

    public function ping(){
        $wg  = WireGuardUsers::where('is_enabled',1)->get();
        foreach ($wg as $row){
            $getWg_user = new WireGuard($row->server_id,'user');
            $peers = $wg->getUser($getWg_user->wg->public_key);
            if(!$peers['status']){
               echo $row->user->name;
               echo "-";
               echo $wg->server->name;
               echo "</br>";

            }
        }
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
}
