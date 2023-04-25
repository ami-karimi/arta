<?php

namespace App\Http\Resources\Api;

use App\Models\User;
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
                $creator_find = User::where('id',(int) $item->creator)->first();
                return [
                   'id' => $item->id,
                   'creator' => (int) $item->creator,
                   'username' =>  $item->username,
                   'password' => $item->password,
                   'groups' => $item->groups,
                   'by' => ($creator_find ? ['id'=> $creator_find->id ,'name' => $creator_find->name] : '---'),
                   'created_at' => Jalalian::forge($item->created_at)->__toString(),
                ];
            })
        ];
    }
}
