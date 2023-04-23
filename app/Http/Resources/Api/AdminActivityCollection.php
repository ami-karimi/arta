<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class AdminActivityCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'admins' => User::select(['name','id'])->where('role','!=','user')->get(),
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
