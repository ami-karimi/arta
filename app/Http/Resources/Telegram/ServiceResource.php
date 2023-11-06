<?php

namespace App\Http\Resources\Telegram;

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
            'service_name' =>   $this->tg_group->parent->name,
            'group_data' => [
                'id' => $this->tg_group->id,
                'multi_login' => $this->tg_group->multi_login,
                'days' => $this->tg_group->days,
                'volume' => $this->tg_group->volume,
            ]
        ];

        if($this->service_group == 'wireguard'){
            $re['server_id'] = $this->wg->server->id;
            $re['server_location'] = $this->wg->server->server_location;
            $re['config_qr'] = url('/configs/'.$this->wg->profile_name.".png");
            $re['config_conf'] = url('/configs/'.$this->wg->profile_name.".conf");

        }

        return $re;
    }
}
