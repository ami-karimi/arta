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

        $now = Carbon::now()->format('Y-m-d');
        $findWgExpired = User::where('service_group','wireguard')->whereDate('expire_date','<=',$now)->where('expired',0)->get();

        foreach ($findWgExpired as $row){
            foreach($row->wgs as $row_wg) {
                echo $row_wg->public_key;
                echo $row->expire_date;
                echo $row_wg->server_id;
                echo $row->username;
                echo "</br>";
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
