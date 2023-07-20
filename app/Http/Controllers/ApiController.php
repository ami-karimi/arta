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
        $users = User::where('service_group','wireguard')->whereNull('expire_date')->get();
        foreach ($users as $user){
            if($user->expire_date == null){
                $req_all = [];
                if ($user->group->expire_type !== 'no_expire') {
                    if ($user->group->expire_type == 'minutes') {
                        $req_all['exp_val_minute'] = $user->group->expire_value;

                    } elseif ($user->group->expire_type == 'month') {
                        $req_all['exp_val_minute'] = floor($user->group->expire_value * 43800);
                    } elseif ($user->group->expire_type == 'days') {
                        $req_all['exp_val_minute'] = floor($user->group->expire_value * 1440);

                    } elseif ($user->group->expire_type == 'hours') {
                        $req_all['exp_val_minute'] = floor($user->group->expire_value * 60);

                    } elseif ($user->group->expire_type == 'year') {
                        $req_all['exp_val_minute'] = floor($user->group->expire_value * 525600);
                    }
                }

                $user->expire_value = $user->group->expire_value;
                $user->expire_type = $user->group->expire_type;
                $user->expire_date = Carbon::parse($user->created_at)->addMinutes($req_all['exp_val_minute']);
                $user->first_login = Carbon::parse($user->created_at);
                $user->expire_set = 1;
                $user->expired = 0;
                $user->save();
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
