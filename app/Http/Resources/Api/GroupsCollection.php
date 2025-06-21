<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GroupsCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function($item) {
                return [
                   'id' => $item->id,
                   'group_volume' => $item->group_volume,
                   'charge_id' => $item->charge_id,
                   'group_type' => $item->group_type,
                   'price' => $item->price,
                   'price_reseler' => $item->price_reseler,
                   'name' => $item->name,
                   'expire_type' => $item->expire_type,
                   'expire_value' => $item->expire_value,
                   'multi_login' => $item->multi_login,
                   'is_enabled' => ($item->is_enabled ? true : false),

                ];
            }),
        ];
    }
}
