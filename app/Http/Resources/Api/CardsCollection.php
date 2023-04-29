<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CardsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['data' => $this->collection->map(function($item){
            return [
               'id' => $item->id,
               'for_name' => ($item->for == 0 ? ['id' => 0 ,'name' => 'system', 'role' => 'admin'] : ($item->creator ?  ['id' => $item->creator->id ,'name' => $item->creator->name, 'role' => $item->creator->role] : false)),
               'card_number_name' => $item->card_number_name,
               'for' => $item->for,
               'card_number' => $item->card_number,
               'card_number_bank' => $item->card_number_bank,
               'is_enabled' => $item->is_enabled,
            ];
        })];
    }
}
