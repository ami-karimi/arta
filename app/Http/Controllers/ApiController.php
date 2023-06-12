<?php

namespace App\Http\Controllers;

use App\Models\Ras;
use App\Models\WireGuardUsers;
use App\Utility\WireGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Utility\V2rayApi;
use App\Utility\Mikrotik;

class ApiController extends Controller
{
    public function index(){
        $ras = Ras::get();

        foreach ($ras as $server){
            $mik = new WireGuard($server->id,'null');
            $peers = $mik->getAllPears();
            if($peers['status']){
                $peers = $peers['peers'];
                foreach ($peers as $peer){
                    $findWireIn = WireGuardUsers::where('user_ip',str_replace('/32','',$peer['allowed-address']))->where('server_id',$server->id)->first();
                    if($findWireIn){
                        if($findWireIn->user->expire_set == 0 && isset($peer['last-handshake'])){
                            $findWireIn->user->expire_set = 1;
                            $findWireIn->user->first_login = Carbon::now();
                            $findWireIn->user->expire_date = Carbon::now()->addMinutes($findWireIn->user->exp_val_minute);
                            $findWireIn->user->save();
                        }
                    }
                }
            }

        }

    }
}
