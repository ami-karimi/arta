<?php

namespace App\Http\Resources\Api;

use App\Utility\V2raySN;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use \Morilog\Jalali\Jalalian;
use App\Models\RadAcct;
use App\Models\Groups;
use App\Models\User;
use App\Models\Ras;
use App\Models\UserGraph;
use App\Utility\V2rayApi;

class UserCollection extends ResourceCollection
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
        return [
            'groups' => Groups::select('name','id','price_reseler')->get(),
            'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
            'v2ray_server' => Ras::select(['id','server_location','name'])->where('server_type','v2ray')->where('is_enabled',1)->get(),
            'data' => $this->collection->map(function($item){
                $v2ray_user = false;
                $usage = 0;
                if($item->group){
                    if($item->group->group_type == 'volume'){
                        $usage = $item->usage;
                    }
                }
                $total = $item->max_usage;

                if($item->service_group == 'v2ray') {
                    $v2ray_user = true;
                    $V2ray = new V2raySN(
                        [
                            'HOST' => $item->v2ray_server->ipaddress,
                            "PORT" => $item->v2ray_server->port_v2ray,
                            "USERNAME" => $item->v2ray_server->username_v2ray,
                            "PASSWORD" => $item->v2ray_server->password_v2ray,
                        ]
                    );

                    if(!$V2ray->error['status']){
                        $client = $V2ray->get_client($item->username);
                        if($client){
                            $usage = $client['up'] +  $client['down'];
                            $total = $client['total'];
                            $v2ray_user = $client;
                            $v2ray_user['url'] = $item->v2ray_config_uri;
                            $v2ray_user['url_encode'] = urlencode($item->v2ray_config_uri);

                        }
                    }
                }



                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'service_group' => $item->service_group,
                    'username' => $item->username,
                    'usage' => $usage,
                    'usage_format' => $this->formatBytes($usage,2),
                    'total' => $total,
                    'total_format' => $this->formatBytes($total,2),
                    'creator' => $item->creator,
                    'multi_login' => $item->multi_login,
                    'v2ray_detail' => $v2ray_user,
                    'creator_detial' => ($item->creator_name ? ['name' => $item->creator_name->name,'role' =>$item->creator_name->role ,'id' =>$item->creator_name->id] : [] ) ,
                    'password' => $item->password,
                    'group' => ($item->group ? $item->group->name : '---'),
                    'group_id' => $item->group_id,
                    'group_type' => ($item->group ? $item->group->group_type : false),
                    'expire_date' => ($item->expire_date !== NULL ? Jalalian::forge($item->expire_date)->__toString() : '---'),
                    'time_left' => ($item->expire_date !== NULL ? Carbon::now()->diffInDays($item->expire_date, false) : false),
                    'status' => ($item->isOnline ? 'online': 'offline'),
                    'first_login' =>($item->first_login !== NULL ? Jalalian::forge($item->first_login)->__toString() : '---'),
                    'is_enabled' => $item->is_enabled ,
                    'created_at' => Jalalian::forge($item->created_at)->__toString(),
                ];
            }),

        ];
    }
}
