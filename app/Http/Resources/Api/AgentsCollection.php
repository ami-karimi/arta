<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class AgentsCollection extends ResourceCollection
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
                    'name' => $item->name,
                    'role' => $item->role,
                    'role_name' => ($item->role === 'admin' ? 'مدیر کل' : 'نماینده') ,
                    'email' => $item->email,
                    'is_enabled' => $item->is_enabled,
                    'incom' => $item->incom,
                    'created_at' => Jalalian::forge($item->created_at)->__toString()
                ];
            })
        ];
    }
}
