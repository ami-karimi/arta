<?php

namespace App\Http\Resources\Api;

use App\Models\Financial;
use App\Models\Groups;
use App\Models\PriceReseler;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class AdminCollection extends ResourceCollection
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
                $minus_income = Financial::where('for',$item->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
                $icom_user = Financial::where('for',$item->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');


                $incom  =  $icom_user - $minus_income;

                return [
                  'id' => $item->id,
                  'name' => $item->name,
                  'creator' => ($item->creator_name !== NULL ? $item->creator_name->only(['id','name']) : []),
                  'role' => $item->role,
                  'role_name' => ($item->role === 'admin' ? 'مدیر کل' : 'نماینده') ,
                  'email' => $item->email,
                  'is_enabled' => $item->is_enabled,
                  'incom' => $incom,
                  'created_at' => Jalalian::forge($item->created_at)->__toString()
                ];
            })
        ];
    }
}
