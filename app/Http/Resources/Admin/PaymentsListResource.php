<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
use App\Utility\Helper;

class PaymentsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_type' => $this->payment_type,
            'cart_detail' => ($this->pay_type ? json_decode($this->pay_type->data) : false ),
            'order' => ($this->order ? [
                'id' => $this->order->id,
                'order_id' => $this->order->order_id,
                'status' => $this->order->order_status,
                'expired_orderin' => Helper::check_expired($this->order->expired_orderin),
            ] : false ),
            'file' => url($this->file),
            'payment_price' => $this->payment_price,
            'status' => $this->status,
            'closed_reason' => $this->closed_reason,
            'created_at' => Jalalian::forge($this->created_at)->format('Y-m-d H:i'),
            'updated_at' => Jalalian::forge($this->updated_at)->format('Y-m-d H:i'),
        ];
    }
}
