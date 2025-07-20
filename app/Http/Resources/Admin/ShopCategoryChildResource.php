<?php

namespace App\Http\Resources\Admin;

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
            'description_line' => explode("\n",$this->description),
            'description' => $this->description,
            'group_id' => $this->group_id,
            'group' => ($this->group ? new GroupResource($this->group) : false),
            'is_enabled' => (bool)$this->is_enabled
        ];
    }
}
