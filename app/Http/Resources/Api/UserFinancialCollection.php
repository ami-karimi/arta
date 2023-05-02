<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;
use App\Models\Financial;
use App\Models\CardNumbers;

class UserFinancialCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ballance = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','plus')->get()->sum('price');
        $ballance_minus = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','minus')->get()->sum('price');

        $credit = $ballance - $ballance_minus;
        if($credit <= 0){
            $credit = 0;
        }


        $cart_numbers = CardNumbers::where('for',auth()->user()->creator)->where('is_enabled',1)->first();
        if(!$cart_numbers){
            $cart_numbers = CardNumbers::where('for',0)->where('is_enabled',1)->first();
        }


        return [
            'cart_number' => $cart_numbers,
            'credit' => $credit,
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
          })
        ];
    }
}
