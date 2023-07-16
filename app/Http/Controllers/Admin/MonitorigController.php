<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utility\Mikrotik;
use App\Models\Ras;

class MonitorigController extends Controller
{
    public function index(){
        $Servers = Ras::select(['ipaddress','l2tp_address','id','name'])->where('server_type','l2tp')->where('is_enabled',1)->get();
        return response()->json($Servers);
    }

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


    public function ether($ip){
        $API        = new Mikrotik();
        $API->debug = false;
        $servers = [];
        if($API->connect($ip, 'admin', 'Amir@###1401')){

            $API->write('/interface/print',false);
            $API->write("?type=" .'ether', true);
            $READ = $API->read(false);
            $etherDatas = $API->parseResponse($READ);

            $etherName = $etherDatas[0]['name'];

            $API->write('/interface/monitor-traffic',false);
            $API->write("?interface=" .$etherName, false);
            $API->write("=once");
            $READ = $API->read(false);
            $etherData = $API->parseResponse($READ);


            $servers = [
                'status' => true,
                'result' => [
                    'ether' => $etherData,
                    'rx_byte' => (isset($etherData[0]) ? $this->formatBytes($etherData[0]['rx-bits-per-second'],2) : 0),
                    'tx_byte' => (isset($etherData[0]) ?  $this->formatBytes($etherData[0]['tx-bits-per-second'],2) : 0),
                ]
            ];
            $API->disconnect();


        }else{
            $servers = [
                'status' => false,
                'result' => [

                ]
            ];
        }

        return response()->json($servers);
    }


    public function KillUser($server,$user){
        $API        = new Mikrotik();
        $API->debug = false;
        if($API->connect($server, 'admin', 'Amir@###1401')){
            $BRIDGEINFO = $API->comm('/ppp/active/print', array(
                ".proplist" => ".id",
                "?name" => "$user"
            ));

            $API->comm('/ppp/active/remove',  array(
                ".id"=>$BRIDGEINFO[0]['.id'],
            ));
            return true;
        }

        return false;
    }
}
