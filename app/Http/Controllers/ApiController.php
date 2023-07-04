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
use App\Utility\Sms;

class ApiController extends Controller
{
    public function index(){


        $Backed = backUsers::all()->toArray();



        $add = 0;
        foreach ($Backed as $row){
            $find = User::where('id',$row->id)->first();
            if(!$find){
                User::create($row);
                $add++;
            }
        }
        echo $add;

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
