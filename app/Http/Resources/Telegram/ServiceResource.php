<?php

namespace App\Http\Resources\Telegram;

use App\Utility\V2raySN;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function formatBytes(int $size,int $format = 2, int $precision = 2) : string
    {
        $base = log($size, 1024);

        if($format == 1) {
            $suffixes = ['بایت', 'کلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت']; # Persian
        } elseif ($format == 2) {
            $suffixes = ["B", "KB", "MB", "GB", "TB"];
        } else {
            $suffixes = ['B', 'K', 'M', 'G', 'T'];
        }

        if($size <= 0) return "0 ".$suffixes[1];

        $result = pow(1024, $base - floor($base));
        $result = round($result, $precision);
        $suffixes = $suffixes[floor($base)];

        return $result ." ". $suffixes;
    }


    public function toArray(Request $request): array
    {
        $childName = "";
        $left =  ( $this->expire_set ? Jalalian::forge($this->first_login)->__toString() : false);
        $expired =  ($left ? ($left <= 0 ? 1 : 0 ): 0);
        $re = [
            'status' => true,
            'id' => $this->id,
            'status_account' => ($expired ? 2 : $this->is_enabled),
            'username' => $this->username,
            'password' => $this->password,
            'service_group' => $this->service_group,
            'expire_set' => $this->expire_set,
            'expire_date' => ( $this->expire_set ? Jalalian::forge($this->expire_date)->__toString()  : false ),
            'time_left' => ( $this->expire_set ? Carbon::now()->diffInDays($this->expire_date, false)  : false),
            'first_login' =>  ( $this->expire_set ? Jalalian::forge($this->first_login)->__toString() : false),
            'service_id' =>   $this->tg_group->parent->id,
            'expired' =>  $expired,
            'service_parent_id' =>   $this->tg_group->parent->id,
            'group_name' =>   $this->group->name,
            'service_name' =>   $this->tg_group->parent->name,
            'group_data' => [
                'id' => $this->tg_group->id,
                'multi_login' => $this->tg_group->multi_login,
                'days' => $this->tg_group->days,
                'volume' => $this->tg_group->volume,
                'base_price' => $this->tg_group->price,
            ]
        ];
        if($this->service_group == 'v2ray'){
            $login = new V2raySN(
                [
                    'HOST' => $this->v2ray_server->ipaddress,
                    "PORT" => $this->v2ray_server->port_v2ray,
                    "USERNAME" => $this->v2ray_server->username_v2ray,
                    "PASSWORD" => $this->v2ray_server->password_v2ray,
                    "CDN_ADDRESS"=> $this->v2ray_server->cdn_address_v2ray,
                ]
            );
            $re['server_id'] = $this->v2ray_server->id;
            $re['server_location'] = $this->v2ray_server->server_location;
            $v2_current = $login->get_user($this->protocol_v2ray,$this->username);
            $re['config_qr'] = $v2_current['user']['url_encode'];
            $re['config_link'] = $v2_current['user']['url'];
            $v2_online = $login->getOnlines();
            $re['v2ray_protocol'] = $v2_current['inbound']['protocol'];
            $client = $login->get_client($this->username);

            $total = $client['total'];
            $Usage = $client['total']  - ($client['up'] + $client['down']);
            $re['v2_total'] = $this->formatBytes($total);
            $re['v2_usage'] = $this->formatBytes($Usage);

            $re['online'] = (in_array($this->username,$v2_online) ? true : false);
        }
        if($this->service_group == 'wireguard'){
            $re['server_id'] = $this->wg->server->id;
            $re['server_location'] = $this->wg->server->server_location;
            $re['config_qr'] = url('/configs/'.$this->wg->profile_name.".png");
            $re['config_conf'] = url('/configs/'.$this->wg->profile_name.".conf");
        }

        return $re;
    }
}
