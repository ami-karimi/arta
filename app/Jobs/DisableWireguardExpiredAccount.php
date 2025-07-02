<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WireGuardUsers;
use App\Utility\SaveActivityUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Utility\WireGuard;
use App\Utility\Helper;

class DisableWireguardExpiredAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $server;
    protected $users;

    /**
     * Create a new job instance.
     */
    public function __construct($server,$users)
    {
        $this->server = $server;
        $this->users = $users;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mik = $this->server;

        foreach ($this->users as $user) {
            $user_id = (int) $user['user_id'];
            $public_key = $user['public_key'];
            $peers = $mik->getUser($public_key);
            $job = Helper::create_job('wireguard_expired',$user_id,'pending');
            if ($peers['status']) {
              // $status = $mik->ChangeConfigStatus($public_key, 0);
                $status['status'] = false;
                $status['message'] = "test";
                if ($status['status']) {
                    $find_user = User::where('id',$user_id)->first();
                    $find_config = WireGuardUsers::where('user_id',$user_id)->where('public_key',$public_key)->first();
                    SaveActivityUser::send($user_id, 2, 'active_status', ['status' => 0]);

                    $find_user->expired = 1;
                    $find_config->is_enabled = 0;
                    $find_config->save();
                    $find_user->save();
                    $job->status = 'completed';
                    $job->done_time = now();
                    $job->result = 'Disabled Account In Server: '.$find_config->server->name;
                    $job->save();

                }else{
                    $job->status = 'failed';
                    $job->result = $status['message'];
                    $job->save();
                }
            }else{
                $job->status = 'failed';
                $job->result = $peers['message'];
                $job->save();
            }
        }

    }
}
