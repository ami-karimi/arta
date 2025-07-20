<?php

namespace App\Jobs;

use App\Models\WireGuardUsers;
use App\Utility\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShopCreateV2rayAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $user;
    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct($order,$user)
    {
        $this->order = $order;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $create_account =  Helper::CreateV2rayAccount($this->user,$this->user->v2ray_server,$this->user->username,$this->user->group);
        if(!$create_account['status']){
            Helper::SaveEventOrder($this->order,'خطا در ایجاد اکانت '.$create_account['result']);
            $this->user->delete();
            return;
        }

        $this->user->v2ray_config_uri = $create_account['v2ray_config_uri'];
        $this->user->uuid_v2ray = $create_account['uuid_v2ray'];
        $this->user->save();

        Helper::SaveEventOrder(
            $this->order->id,
            vsprintf('اشتراک با نام (%s) بر روی سرور (%s) ساخته شد.',[$this->user->username,$this->user->v2ray_server->name])
        );
        $this->order->user_id = $this->user->id;
        $this->order->order_status = 'completed';
        $this->order->save();
        SendShopCreatedV2rayAccountEmailJob::dispatch($this->user,$this->order);

    }
}
