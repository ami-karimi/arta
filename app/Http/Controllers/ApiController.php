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
use App\Utility\Sms;

class ApiController extends Controller
{
    public function index(){
        $Users = User::where('service_group','wireguard')->where('expire_set',0)->get();
        foreach ($Users as $row){
            $row->expire_set = 1;
            $row->first_login = Carbon::parse($row->created_at);
            $row->expire_date = Carbon::parse($row->created_at)->addMinutes($row->exp_val_minute);
            $row->save();
            print_r($row);
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
