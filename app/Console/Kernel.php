<?php

namespace App\Console;

use App\Models\RadAcct;
use App\Models\RadPostAuth;
use App\Models\Ras;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserGraph;
use App\Utility\Helper;
use App\Utility\Mikrotik;
use App\Utility\SaveActivityUser;
use App\Utility\WireGuard;
use App\Models\WireGuardUsers;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Utility\SmsSend;
use App\Utility\Ftp;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {

            $data = User::whereHas('group',function ($query){
                $query->where('group_type','volume');
            })->where('service_group','l2tp_cisco')->where('limited',0)->get();
            foreach ($data as $item){
                $findUser = DB::table('radacct')
                    ->where('saved',0)
                    ->where('acctstoptime','!=',NULL)
                    ->where('username',$item->username)->get();
                $download =  $findUser->sum('acctoutputoctets');
                $upload =  $findUser->sum('acctinputoctets');

                if(count($findUser) && ($upload + $download) > 0) {
                    $item->usage += $download+ $upload;
                    $item->download_usage +=  $download;
                    $item->upload_usage += $upload;
                    if($item->usage >= $item->max_usage ){
                        $item->limited = 1;
                    }
                    $item->save();
                    RadAcct::where('username',$item->username)->where('saved',0)->update(['saved' => 1]);
                }


            }

        })->everyMinute();

        $schedule->call(function () {
            RadAcct::where('saved',1)->delete();

            Helper::get_db_backup();


        })->everyFiveMinutes();
        // Backup system
        $schedule->call(function () {
            $Servers = Ras::select(['ipaddress','l2tp_address','id','name'])->where('server_type','l2tp')->where('is_enabled',1)->get();
            $user_list = [];
            foreach ($Servers as $sr) {
                $API = new Mikrotik($sr);
                $API->connect();

                $BRIDGEINFO = $API->bs_mkt_rest_api_get("/ppp/active?encoding&service=ovpn");
                if($BRIDGEINFO['ok']){
                    foreach ($BRIDGEINFO['data'] as $row){
                        RadAcct::where('username',$row['name'])->delete();
                        $API->bs_mkt_rest_api_del("/ppp/active/" . $row['.id']);
                    }
                }
            }


        })->everyTenMinutes();
        // Backup DB
        /*
        $schedule->call(function () {
            $users = User::whereHas('group',function($query){
                return $query->where('group_type','volume');
            })->get();

            foreach($users as $user){
                $rx = UserGraph::where('user_id',$user->id)->get()->sum('rx');
                $tx = UserGraph::where('user_id',$user->id)->get()->sum('tx');
                $total_use = $rx + $tx;
                if($total_use > 0) {
                    $usage = $user->usage + $total_use;
                    if ($usage >= $user->max_usage) {
                        $user->limited = 1;
                    }

                    $user->usage += $total_use;
                    $user->download_usage += $rx;
                    $user->upload_usage += $tx;
                    $user->save();
                    UserGraph::where('user_id', $user->id)->delete();
                }
            }
        })->everyTwoHours();
        */
        $schedule->call(function(){
            Helper::get_backup();


            $now = Carbon::now()->format('Y-m-d');
            $findWgExpired = User::where('service_group','wireguard')->whereDate('expire_date','<=',$now)->where('expired',0)->get();

            foreach ($findWgExpired as $row){
                foreach($row->wgs as $row_wg) {
                    $mik = new WireGuard($row_wg->server_id, 'null');
                    $peers = $mik->getUser($row_wg->public_key);
                    $row_wg->is_enabled = 0;
                    $row_wg->save();
                    if ($peers['status']) {
                        $status = $mik->ChangeConfigStatus($row_wg->public_key, 0);
                        if ($status['status']) {
                            SaveActivityUser::send($row->id, 2, 'active_status', ['status' => 0]);
                            $row->expired = 1;
                            $row->save();
                        }
                    }
                }

            }
        })->everyFourHours();
        $schedule->call(function () {


            $data_no = User::whereHas('group',function ($query){
                $query->where('group_type','expire');
            })->where('service_group','l2tp_cisco')->get();

            foreach ($data_no as $item){
                RadAcct::where('username',$item->username)->where('acctstoptime','!=',NULL)->delete();
                RadPostAuth::where('username',$item->username)->delete();
            }



            $users = User::where('phonenumber','!=',null)->where('expire_set',1)->where('expire_date','<=',Carbon::now('Asia/Tehran')->addDay(3))->where('expire_date','>=',Carbon::now('Asia/Tehran')->subDays(3))->get();
            foreach ($users as $user){
                if($user->expire_date) {
                    $sms = new SmsSend($user->phonenumber);
                    $sms->SendSmsExpire(Carbon::now()->diffInDays($user->expire_date, false));
                }
            }
        })->everySixHours();


    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
