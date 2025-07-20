<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Utility\Helper;
use App\Models\WireGuardUsers;
use App\Models\User;

class ShopCreateWireguardAccount implements ShouldQueue
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
       $create_account =  Helper::CreateWireguardAccount($this->user->wg_server_id,$this->user->username);
       if(!$create_account['status']){
           Helper::SaveEventOrder($this->order,'خطا در ایجاد اکانت '.$create_account['result']);
           $this->user->delete();
           return;
       }

        $config_file = $create_account['result']['config_file'];
        $client_private_key = $create_account['result']['client_private_key'];
        $client_public_key = $create_account['result']['client_public_key'];
        $ip_address = $create_account['result']['ip_address'];
        $server_name = $create_account['result']['server_name'];

        $create_peer  = new WireGuardUsers();
        $create_peer->profile_name = $config_file;
        $create_peer->user_id = $this->user->id;
        $create_peer->server_id = $this->user->wg_server_id;
        $create_peer->client_private_key  =  $client_private_key;
        $create_peer->public_key = $client_public_key;
        $create_peer->user_ip = $ip_address;
        $create_peer->save();

        Helper::SaveEventOrder(
            $this->order->id,
            vsprintf('کانفیگ با نام (%s) بر روی سرور (%s) ساخته شد.',[$config_file,$server_name])
        );
        $this->order->user_id = $this->user->id;
        $this->order->order_status = 'completed';
        $this->order->save();
        SendShopCreatedWgAccountEmailJob::dispatch($this->user,$this->order);

    }
}
