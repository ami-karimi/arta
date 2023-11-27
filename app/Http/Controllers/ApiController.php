<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\RadPostAuth;
use App\Models\Ras;
use App\Models\Settings;
use App\Models\WireGuardUsers;
use App\Utility\Helper;
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

        $data = User::whereHas('group',function ($query){
            $query->where('group_type','volume');
        })->where('service_group','l2tp_cisco')->where('limited',0)->get();
        foreach ($data as $item){
            $findUser = DB::table('radacct')->where('acctstoptime','!=',NULL)->where('saved',0)->where('username',$item->username)->sum('acctoutputoctets')->sum('acctinputoctets');

            print_r($findUser);
            /*
            if(count($findUser)) {
                $item->usage += $findUser[0]['download_sum'] + $findUser[0]['upload_sum'];
                $item->download_usage += $findUser[0]['download_sum'];
                $item->upload_usage += $findUser[0]['upload_sum'];
                if($item->usage >= $item->max_usage ){
                    $item->limited = 1;
                }
                $item->save();
                RadAcct::where('username',$item->username)->where('saved',0)->update(['saved' => 1]);
            }
            */

        }

       // Helper::get_db_backup();
       // Helper::get_backup();



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
}
