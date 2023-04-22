<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class ActivityCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($item){
                return [
                    'content' => $item->content,
                    'created_at' => Jalalian::forge($item->created_at)->__toString(),
                    'by' => $item->by,
                    'from' => ($item->from !== NULL ? ['name' => $item->from->name ,'role' => $item->from->role ,'id' => $item->from->id] : false)
                ];
            })
        ];
    }
}
