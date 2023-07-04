<?php

namespace App\Http\Controllers;

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
use App\Models\Activitys;
use App\Utility\Sms;

class ApiController extends Controller
{
    public function index(){


        $users = User::where('service_group','l2tp_cisco')->whereHas('group',function($query) {
            $query->where('group_type','expire');
        })->all();

        foreach ($users as $row){
            $row->max_usage = @round((((int) 100 *1024) * 1024) * 1024 ) ;
            $row->max_usage *= $row->multi_login;
            $row->save();
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
