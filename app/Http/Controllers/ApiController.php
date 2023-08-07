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
        $data =  RadAcct::where('acctstoptime','!=',NULL)->where('saved',0)->selectRaw('sum(acctoutputoctets) as upload_sum, sum(acctinputoctets) as download_sum, sum(acctinputoctets + acctoutputoctets) as total_sum,username,radacctid')->groupBy('username')->limit(1000)->get();

        print_r($data);
        /*
       $users = User::whereHas('group',function($query){
            return $query->where('group_type','volume');
        })->get();

       foreach($users as $user){

               $rx = $user->download_usage;
               $tx = $user->upload_usage;
               $total_use = $rx + $tx;
               if ($total_use > 0) {
                   $usage = $user->usage ;
                   if ($total_use >= $user->max_usage) {
                       $user->limited = 1;
                   }else{
                       $user->limited = 0;
                   }

                   $user->usage = $total_use;
                   $user->download_usage = $rx;
                   $user->upload_usage = $tx;
                   $user->save();
               }
           }

        */

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
