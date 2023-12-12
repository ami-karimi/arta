<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Ras;
use App\Utility\V2raySN;
use Illuminate\Http\Request;

class V2rayController extends Controller
{
    public function get_services($server_id){
        $find_ras = Ras::where('id',$server_id)->first();
        if(!$find_ras){
            return response()->json(['status' => false,'message' => 'سرور یافت نشد!'],404);
        }
        $V2ray = new V2raySN([
            'HOST' =>  $find_ras->ipaddress,
            "PORT" =>  $find_ras->port_v2ray,
            "USERNAME" => $find_ras->username_v2ray,
            "PASSWORD"=> $find_ras->password_v2ray,
            "CDN_ADDRESS"=> $find_ras->cdn_address_v2ray,

        ]);
        if(!$V2ray->error['status']){
            $list = $V2ray->InBoandList();
            $re = [];
            foreach ($list['obj'] as $key => $row){
                $re[] = [
                    'id' => $row['id'],
                    'port' => $row['port'],
                    'protocol' => $row['protocol'],
                    'remark' => $row['remark'],
                    'tag' => $row['tag'],
                ];
            }
            return response()->json($re);

        }else{
            return response()->json(['status' => false,'message'=> $V2ray->error['message']],502);
        }

    }

}
