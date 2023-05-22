<?php

namespace App\Console;

use App\Models\RadAcct;
use App\Models\User;
use App\Models\UserGraph;
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
            $data =  RadAcct::where('acctstoptime','!=','NULL')->selectRaw('sum(acctoutputoctets) as download_sum, sum(acctinputoctets) as upload_sum, sum(acctinputoctets + acctoutputoctets) as total_sum,username,radacctid')->limit(1000)->get();

            foreach ($data as $item){
                $findUser = User::where('username',$item->username)->first();
                if($findUser) {
                    $findOrCreateTotals = UserGraph::where('user_id', $findUser->id)->where('date',Carbon::now()->format('Y-m-d'))->first();
                    if ($findOrCreateTotals) {
                        $findOrCreateTotals->rx += $item->download_sum;
                        $findOrCreateTotals->tx += $item->upload_sum;
                        $findOrCreateTotals->total += $item->download_sum + $item->upload_sum;
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
                }
                RadAcct::where('username',$item->username)->where('acctstoptime','!=',NULL)->delete();

            }


        })->everyMinute()->emailOutputOnFailure('takfashomal@gmail.com');;
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
