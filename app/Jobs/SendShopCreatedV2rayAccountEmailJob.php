<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Morilog\Jalali\Jalalian;

class SendShopCreatedV2rayAccountEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected  $user;
    protected  $order;
    /**
     * Create a new job instance.
     */
    public function __construct($user,$order)
    {
        $this->user = $user;
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        EmailService::sendTemplate('create_v2ray_account', $this->order->email, [
            'name' => $this->order->name,
            'username' => $this->user->username,
            'password' => $this->user->password,
            'expire_date' => Jalalian::forge($this->user->expire_date)->format('Y-m-d H:i:s'),
            'expire_left' => Jalalian::forge($this->user->expire_date)->ago(),
            'total_traffic' => $this->user->group->group_volume,
            'v2ray_link' => $this->user->v2ray_config_uri,
            'site_url' => "https://arta20.top",
        ]);
    }
}
