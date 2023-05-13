<?php

namespace App\Http\Resources\Api;

use App\Models\CardNumbers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class AdminFinancialCollection extends ResourceCollection
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
            'data' => $this->collection->map(function($item){
                return [
                    'id' => $item->id,
                    'creator' => $item->creator,
                    'creator_name' => ($item->creator_by ? $item->creator_by->name : '---'),
                    'for' => $item->for,
                    'for_name' => ($item->for_user ? $item->for_user->name : '---'),
                    'description' => $item->description,
                    'type' => $item->type,
                    'price' => $item->price,
                    'attachment' => ($item->attachment ? url($item->attachment) : false),
                    'approved' => $item->approved,
                    'created_at' =>  Jalalian::forge($item->created_at)->__toString(),
                ];
            }),
            'card_numbers' => CardNumbers::select(['card_number_name','card_number','card_number_bank'])->where('is_enabled',1)->where('for',0)->get(),
        ];
    }
}
