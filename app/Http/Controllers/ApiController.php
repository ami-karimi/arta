<?php

namespace App\Http\Controllers;

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
       $users = User::whereHas('group',function($query){
            return $query->where('group_type','volume');
        })->get();

       foreach($users as $user){
           $rx = UserGraph::where('user_id',$user->id)->get()->sum('rx');
           $tx = UserGraph::where('user_id',$user->id)->get()->sum('tx');
           $total_use = $rx + $tx;
           if($total_use > 0) {
               $usage = $user->usage + $total_use;
               if ($usage >= $user->max_usage) {
                   $user->limited = 1;
               }

               $user->usage += $total_use;
               $user->download_usage += $rx;
               $user->upload_usage += $tx;
               $user->save();
               UserGraph::where('user_id', $user->id)->delete();
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
