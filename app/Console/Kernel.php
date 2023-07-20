<?php

namespace App\Console;

use App\Models\RadAcct;
use App\Models\Ras;
use App\Models\User;
use App\Models\UserGraph;
use App\Utility\Mikrotik;
use App\Utility\SaveActivityUser;
use App\Utility\WireGuard;
use App\Models\WireGuardUsers;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        $schedule->call(function () {
            $data =  RadAcct::where('acctstoptime','!=',NULL)->selectRaw('sum(acctoutputoctets) as upload_sum, sum(acctinputoctets) as download_sum, sum(acctinputoctets + acctoutputoctets) as total_sum,username,radacctid')->groupBy('username')->limit(1000)->get();

            foreach ($data as $item){
                $findUser = User::where('username',$item->username)->first();
                if($findUser) {
                    $findOrCreateTotals = UserGraph::where('user_id', $findUser->id)->where('date',Carbon::now()->format('Y-m-d'))->first();
                    if ($findOrCreateTotals) {
                        $findOrCreateTotals->rx += $item->download_sum;
                        $findOrCreateTotals->tx += $item->upload_sum;
                        $findOrCreateTotals->total += $item->total_sum;
                        $findOrCreateTotals->save();
                    } else {
                        UserGraph::create([
                            'date' => Carbon::now()->format('Y-m-d'),
                            'user_id' => $findUser->id,
                            'rx' => $item->download_sum,
                            'tx' => $item->upload_sum,
                            'total' => $item->download_sum + $item->upload_sum,
                        ]);
                    }
                    RadAcct::where('username',$item->username)->where('acctstoptime','!=',NULL)->delete();

                }

            }


        })->everyFiveMinutes();
        $schedule->call(function () {
            $API        = new Mikrotik();
            $API->debug = false;
            $Servers = Ras::select(['ipaddress','l2tp_address','id','name'])->where('server_type','l2tp')->where('is_enabled',1)->get();
            $user_list = [];
            foreach ($Servers as $sr) {
                if ($API->connect($sr->ipaddress, 'admin', 'Amir@###1401')) {

                    $BRIDGEINFO = $API->comm('/ppp/active/print', array(
                        "?encoding" => "",
                        "?service" => "ovpn"
                    ));

                    foreach ($BRIDGEINFO as $user) {
                        $user_list[] = $user;
                        RadAcct::where('username',$user['name'])->delete();
                        $API->comm('/ppp/active/remove', array(
                            ".id" => $user['.id'],
                        ));
                    }

                }
            }

        })->everyTenMinutes();
        $schedule->call(function () {

            $now = Carbon::now()->format('Y-m-d');
            $findWgExpired = User::where('service_group','wireguard')->whereDate('expire_date',$now)->where('expired',0)->get();

            foreach ($findWgExpired as $row){
                if($row->wg){
                    $mik = new WireGuard($row->wg->server_id,'null');
                    $peers = $mik->getUser($row->wg->public_key);

                    if($peers['status']){
                       $status =  $mik->ChangeConfigStatus($row->wg->public_key,1);
                       if($status['status']) {
                           SaveActivityUser::send($row->id, 2, 'active_status', ['status' => 0]);
                           $row->expired = 1;
                           $row->save();
                       }
                    }
                }
            }
        })->everyMinute();


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
