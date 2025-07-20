<?php

namespace App\Jobs;

use App\Utility\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShopCreateMultiAccount implements ShouldQueue
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


        Helper::SaveEventOrder(
            $this->order->id,
            vsprintf('اشتراک با نام کاربری (%s) ، کلمه عبور (%s) ساخته شد.',[$this->user->username,$this->user->password])
        );
        $this->order->user_id = $this->user->id;
        $this->order->order_status = 'completed';
        $this->order->save();
        SendShopCreatedMultiAccountEmailJob::dispatch($this->user,$this->order);

    }
}
