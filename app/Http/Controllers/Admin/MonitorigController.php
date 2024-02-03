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
        $FindRas = Ras::where('ipaddress',$ip)->first();
        $API        = new Mikrotik((object)[
            'l2tp_address' => $FindRas->mikortik_domain,
            'mikrotik_port' => $FindRas->mikrotik_port,
            'username' => $FindRas->mikrotik_username,
            'password' => $FindRas->mikrotik_password,
        ]);
        $connect = $API->connect();
        if(!$connect['ok']){
            return response()->json([
                'status' => false,
                'result' => [

                ]
            ]);
        }
        $servers = [];

            $ras = $API->bs_mkt_rest_api_get('/interface?type=ether');
            if(!$ras['ok']){
                return response()->json([
                    'status' => false,
                    'result' => [

                    ]
                ]);
            }
            $etherName = $ras['data'][0]['name'];
             $etherData = $API->bs_mkt_rest_api_post('/interface/monitor-traffic',array(
                "interface" => $etherName,
                "duration" => '1s',
            ))['data'];


            $servers = [
                'status' => true,
                'result' => [
                    'ether' => $etherData[0],
                    'rx_byte' => (isset($etherData[0]) ? $this->formatBytes($etherData[0]['rx-bits-per-second'],2) : 0),
                    'tx_byte' => (isset($etherData[0]) ?  $this->formatBytes($etherData[0]['tx-bits-per-second'],2) : 0),
                ]
            ];




        return response()->json($servers);
    }


    public function KillUser($server,$user){

        $API        = new Mikrotik((object)[
            'l2tp_address' => $server->mikortik_domain,
            'mikrotik_port' => $server->mikrotik_port,
            'username' => $server->mikrotik_username,
            'password' => $server->mikrotik_password,
        ]);
        $API->debug = false;
        if($API->connect()['ok']){
            $BRIDGEINFO = $API->bs_mkt_rest_api_get('/ppp/active?.proplist=.id&name='.$user);
            if(!$BRIDGEINFO['ok']){
                return false;
            }
            foreach ($BRIDGEINFO['data'] as $row) {
                $API->bs_mkt_rest_api_del("/ppp/active/" . $row['.id']);

            }
            return true;
        }

        return false;
    }
}
