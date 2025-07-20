<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopCategoryChildResource extends JsonResource
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
            'category_id' => $this->category_id,
            'description_line' => explode("\n",$this->description),
            'description' => $this->description,
            'is_enabled' => $this->is_enabled,
            'group' => ($this->group ? new GroupResource($this->group) : false),
        ];
    }
}
