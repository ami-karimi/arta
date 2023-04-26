<?php

namespace App\Http\Resources\Api;

use App\Models\RadAcct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Utility\Ping;
class GetServerCollection extends ResourceCollection
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
                  'l2tp_address' => $item->l2tp_address,
                   'secret' => 'vpn@123#',
                   'server_location' => $item->server_location,
                   'onlines'=> RadAcct::where('nasipaddress',$item->ipaddress)->where('acctstoptime',NULL)->count(),
               ];
            })
        ];
    }
}
