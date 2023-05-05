<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Utility\V2rayApi;

class V2rayServersStatusCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['data' => $this->collection->map(function($item){
             $login = new V2rayApi($item->ipaddress,$item->port_v2ray,$item->username_v2ray,$item->password_v2ray);
             $status_data = ($login ?  $login->status() : false) ;
            return [
                 'server_id' => $item->id,
                 'server_ip' => $item->ipaddress,
                 'server_status' => ($login ? 'up' : 'down'),
                 'result' => $status_data,
            ];
        })];
    }
}
