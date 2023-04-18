<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class RadAuthAcctCollection extends ResourceCollection
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
                   'username' => $item->username,
                   'reply' => $item->reply,
                   'created_at' =>  Jalalian::forge($item->created_at)->__toString()
                ];
            })
        ];
    }
}
