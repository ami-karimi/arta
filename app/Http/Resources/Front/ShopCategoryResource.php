<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopCategoryResource extends JsonResource
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
            'account_type' => $this->account_type,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
