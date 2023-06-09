<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RasResources extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function($item){
                return [
                    'id' => $item->id,
                    'ipaddress' => $item->ipaddress,
                    'server_location' => $item->server_location,
                    'server_type' => $item->server_type,
                    'server_location_id' => $item->server_location_id,
                    'password_v2ray' => $item->password_v2ray,
                    'port_v2ray' => $item->port_v2ray,
                    'username_v2ray' => $item->username_v2ray,
                    'cdn_address_v2ray' => $item->cdn_address_v2ray,
                    'openvpn_profile' => $item->openvpn_profile,
                    'is_enabled' => $item->is_enabled,
                    'created_at' => $item->created_at,
                    'secret' => $item->secret,
                    'unlimited' => $item->unlimited,
                    'name' => $item->name,
                    'online_count' => $item->getUsersOnline()->count()

                ];
            }),
        ];
    }
}
