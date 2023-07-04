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


        $Backed = Activitys::where('content','اکانت شارژ شد!')->where('created_at','<=',Carbon::now('Asia/Tehran')->addDay(22))->get();



        $add = 0;
        foreach ($Backed as $row){
            $find = User::where('id',$row->user_id)->where('expire_set',1)->where('expire_date','<=',Carbon::now('Asia/Tehran')->addDay(1))->first();
            if($find){
                $find->expire_date = Carbon::parse($row->created_at)->addMinutes($find->exp_val_minute);
                $find->first_login = Carbon::parse($row->created_at);
                $find->save();
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
