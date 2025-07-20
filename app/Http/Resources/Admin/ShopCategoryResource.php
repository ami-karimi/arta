<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
          'name' => $this->name,
          'description' => $this->description,
          'account_type' => $this->account_type,
          'logo' => $this->logo,
          'is_enabled' => ($this->is_enabled ? true : false),
          'created_at' =>  Jalalian::forge($this->created_at)->format('Y-m-d H:i')
        ];
    }
}
