<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EmailService;

class SendShopAcceptOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $payment;
    protected  $order;
    /**
     * Create a new job instance.
     */
    public function __construct($payment,$order)
    {
        $this->payment = $payment;
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        EmailService::sendTemplate('order_accept_payment', $this->order->email, [
            'name' => $this->order->name,
            'price' => $this->payment->payment_price,
        ]);
    }
}
