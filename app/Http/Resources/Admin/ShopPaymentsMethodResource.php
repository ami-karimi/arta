<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopPaymentsMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'payment_type' => $this->payment_type,
            'data' => json_decode($this->data),
            'is_enabled' => (bool)$this->is_enabled
        ];
    }
}
