<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendShopRejectOrderEmailJob implements ShouldQueue
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
        EmailService::sendTemplate('order_reject_payment', $this->order->email, [
            'name' => $this->order->name,
            'price' => $this->payment->payment_price,
            'reason_reject' => $this->payment->closed_reason,
        ]);
    }
}
