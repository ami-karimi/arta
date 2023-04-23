<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RasResources extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function($item){
                return [
                    'id' => $item->id,
                    'ipaddress' => $item->ipaddress,
                    'is_enabled' => $item->is_enabled,
                    'created_at' => $item->created_at,
                    'secret' => $item->secret,
                    'name' => $item->name,
                    'online_count' => $item->getUsersOnline()->count()

                ];
            }),
        ];
    }
}
