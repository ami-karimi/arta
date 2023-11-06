<?php

namespace App\Http\Resources\Telegram;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class ServiceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => true,
            'result' => $this->collection->map(function($item){
                $childName = "";
                $left =  ( $item->expire_set ? Jalalian::forge($item->first_login)->__toString() : false);
                $expired =  ($left ? ($left <= 0 ? 1 : 0 ): 0);
                $re = [
                    'id' => $item->id,
                    'username' => $item->username,
                    'password' => $item->password,
                    'status_account' => ($expired ? 2 : $item->is_enabled),
                    'service_group' => $item->service_group,
                    'expire_set' => $item->expire_set,
                    'expire_date' => ( $item->expire_set ? Jalalian::forge($item->expire_date)->__toString()  : false ),
                    'time_left' => $left,
                    'first_login' =>  ( $item->expire_set ? Jalalian::forge($item->first_login)->__toString() : false),
                    'service_id' =>   $item->tg_group->parent->id,
                    'expired' =>  $expired,
                    'service_name' =>   $item->tg_group->parent->name,
                    'group_data' => [
                        'id' => $item->tg_group->id,
                        'multi_login' => $item->tg_group->multi_login,
                        'days' => $item->tg_group->days,
                        'volume' => $item->tg_group->volume,
                    ]
                ];

                if($item->service_group == 'wireguard'){
                    $re['server_id'] = $item->wg->server->id;
                    $re['server_location'] = $item->wg->server->server_location;
                    $re['config_qr'] = url('/configs/'.$item->wg->profile_name.".png");
                    $re['config_conf'] = url('/configs/'.$item->wg->profile_name.".png");

                }
                return $re;
            }),
            'active_count' => User::where('tg_user_id',$request->user_id)->where('expire_date','>',Carbon::now('Asia/Tehran'))->count(),
            'expired_count' => User::where('tg_user_id',$request->user_id)->where('expire_date','<',Carbon::now('Asia/Tehran'))->count(),
        ];
    }
}
