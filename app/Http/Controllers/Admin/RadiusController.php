<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RadAcct;
use App\Models\RadPostAuth;
use App\Http\Resources\Api\RadAcctCollection;
use App\Http\Resources\Api\RadAuthAcctCollection;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class RadiusController extends Controller
{
    public function radlog(Request $request){

        $radLog =  new RadAcct();
        if($request->username){
            $radLog = $radLog->where('username',$request->username);
        }
        if($request->is_online){
            $radLog = $radLog->where('acctstoptime',NULL);

        }

        return new RadAcctCollection($radLog->orderBY('radacctid','DESC')->paginate(10));
    }
    public function radauth(Request $request){
        $radLog =  new RadPostAuth();
        if($request->username){
            $radLog = $radLog->where('username',$request->username);
        }
        return new RadAuthAcctCollection($radLog->orderBY('id','DESC')->paginate(5));

    }
    public function radUserReport(Request $request){

        $username = $request->username;
        $report = [
            'online' => ['total' => 0, 'today' => 0]  ,
            'online_count' => ['count' => 0, 'last_date' => '---']  ,
            'last_connect' => ['name' => '---', 'ip' => '---']  ,
        ];

        if(!$username){
            return response()->json($report);
        }

        $online_total = RadPostAuth::where('username',$username)->where('reply','Access-Accept')->get()->count();
        $online_total_today = RadPostAuth::where('username',$username)->where('reply','Access-Accept')->where('created_at',Carbon::today('Asia/Tehran')->toDateString())->get()->count();
        $report['online']['total'] =  $online_total;
        $report['online']['today'] = $online_total_today;


        $getOnlineLAstDate = RadAcct::where('username',$username)->where('acctstoptime','!=',NULL)->orderBY('radacctid','DESC')->first();
        $last_date = '---';
        if($getOnlineLAstDate){
            $last_date = Jalalian::forge($getOnlineLAstDate->acctstoptime)->__toString();
        }
        $report['online_count']['count'] = RadAcct::where('username',$username)->where('acctstoptime',NULL)->get()->count();
        $report['online_count']['last_date'] = $last_date;

        $lastConnectIP = RadAcct::where('username',$username)->orderBY('radacctid','DESC')->first();
        $last_ip = '---';
        if($lastConnectIP){
            $last_ip = $lastConnectIP->nasipaddress;
        }
        $report['last_connect']['name'] =  ($lastConnectIP ? ($lastConnectIP->servername ? $lastConnectIP->servername->name : '---') : '---');
        $report['last_connect']['ip'] = $last_ip;

        return response()->json($report);
    }

}
