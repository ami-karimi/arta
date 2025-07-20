<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class OrderPaymentResource extends JsonResource
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
            'status' => $this->status,
            'payment_type' => $this->payment_type,
            'closed_reason' => $this->closed_reason,
            'file' => url($this->file),
            'payment_price' => (int) $this->payment_price,
            'created_at' => Jalalian::forge($this->created_at)->format('Y-m-d H:i'),
            'updated_at' => Jalalian::forge($this->updated_at)->format('Y-m-d H:i'),
        ];
    }
}
