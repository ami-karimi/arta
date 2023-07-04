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
    public function index(){


        $users = AcctSaved::where('created_at','<=',Carbon::now('Asia/Tehran')->addDays(20))->get();

        $count = 0;
        foreach ($users as $row){
          $find = User::where('username',$row->username)->first();
           if(!$find){
               $user = new User();
               $user->username = $row->username;
               $user->group_id = 1;
               $user->password = $row->password;
                   $user->expire_date = Carbon::parse($row->created_at)->addMinutes(43800);
                   $user->first_login = Carbon::parse($row->created_at);
                   $user->expire_set = 1;

               $user->creator =  $row->creator;
               $user->max_usage =  @round((((int) 100 *1024) * 1024) * 1024 );
               $user->multi_login = 2;
               $user->expire_type = 'month';
               $user->expire_value = 1;
               $user->Save();
               $count++;
           }
        }

        echo $count;
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
