<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Utility\V2raySN;

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
                $status = $item->is_enabled;
                $online_count = $item->getUsersOnline()->count();
                if($item->server_type == 'v2ray'){
                    $V2ray = new V2raySN([
                        'HOST' => $item->ipaddress,
                        "PORT" => $item->port_v2ray,
                        "USERNAME" => $item->username_v2ray,
                        "PASSWORD" => $item->password_v2ray,
                        "CDN_ADDRESS"=> $item->cdn_address_v2ray,
                    ]);
                    if($V2ray->error['status']){
                        $status = 2;
                    }else{
                        $online_count = count($V2ray->getOnlines());
                    }

                }
                return [
                    'id' => $item->id,
                    'ipaddress' => $item->ipaddress,
                    'server_location' => $item->server_location,
                    'mikrotik_server' => $item->mikrotik_server,
                    'mikrotik_domain' => $item->mikrotik_domain,
                    'mikrotik_port' => $item->mikrotik_port,
                    'mikrotik_username' => $item->mikrotik_username,
                    'mikrotik_password' => $item->mikrotik_password,
                    'server_type' => $item->server_type,
                    'l2tp_address' => $item->l2tp_address,
                    'server_location_id' => $item->server_location_id,
                    'password_v2ray' => $item->password_v2ray,
                    'port_v2ray' => $item->port_v2ray,
                    'username_v2ray' => $item->username_v2ray,
                    'cdn_address_v2ray' => $item->cdn_address_v2ray,
                    'openvpn_profile' => $item->openvpn_profile,
                    'is_enabled' => $item->is_enabled,
                    'created_at' => $item->created_at,
                    'secret' => $item->secret,
                    'can_recharge' => ($item->can_recharge ? true : false),
                    'in_app' => $item->in_app,
                    'config' => $item->config,
                    'flag' => $item->flag,
                    'unlimited' => $item->unlimited,
                    'name' => $item->name,
                    'online_count' => $online_count

                ];
            }),
        ];
    }
}
