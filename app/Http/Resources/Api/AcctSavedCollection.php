<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class AcctSavedCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function($item){
                return [
                   'id' => $item->id,
                   'creator' => $item->creator,
                   'groups' => $item->groups,
                    'item'=> $item,
                   'by' => ($item->by ? ['id'=> $item->by->id ,'name' => $item->by->name] : '---'),
                   'created_at' => Jalalian::forge($item->created_at)->__toString(),
                ];
            })
        ];
    }
}
