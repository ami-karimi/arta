<?php

namespace App\Http\Resources\Api;

use App\Models\Financial;
use App\Models\PriceReseler;
use App\Models\Ras;
use App\Utility\Helper;
use App\Utility\V2rayApi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use \Morilog\Jalali\Jalalian;
use App\Models\RadAcct;
use App\Models\Groups;
use App\Models\User;
class AgentUserCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $minus_income = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',auth()->user()->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');


        $listGroup = Groups::all();
        $map_price = $listGroup->map(function($item){
            $findS = PriceReseler::where('group_id',$item->id)->where('reseler_id',auth()->user()->id)->first();
            return [
                'group_id' => $item->id,
                'name' => $item->name,
                'price' => $item->price_reseler,
                'price_for' => ($findS ? $findS->price : $item->price_reseler),
            ];
        });


        $incom  = $icom_user - $minus_income;
        $priceList = Helper::GetReselerGroupList('list',false,auth()->user()->id);

        return [
            'groups' => Groups::select('name','id','price_reseler')->get(),
            'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
            'credit' => $incom,
            'map_price' => $priceList,
            'v2ray_servers' => Ras::select(['id','server_type','name','server_location'])->where('server_type','v2ray')->where('is_enabled',1)->get(),
            'data' => $this->collection->map(function($item){
                $v2ray_user = false;
                $usage = 0;
                $total = 0;
                if($item->service_group == 'v2ray'){

                    if($item->v2ray_server) {
                        $login_s =new V2rayApi($item->v2ray_server->ipaddress,$item->v2ray_server->port_v2ray,$item->v2ray_server->username_v2ray,$item->v2ray_server->password_v2ray);
                        if($login_s) {
                            $usage = false;
                            $v2ray_user =  $login_s->list(['port' => (int) $item->port_v2ray]);
                            if($v2ray_user) {
                                if (!$item->v2ray_id) {
                                    $item->v2ray_id = $v2ray_user['id'];
                                    $item->save();
                                }
                                $usage = $login_s->formatBytes($v2ray_user['usage'],2);
                                $total = $login_s->formatBytes($v2ray_user['total'],2);
                            }
                        }
                    }
                }
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'service_group' => $item->service_group,
                    'v2ray_detail' => $v2ray_user,
                    'username' => $item->username,
                    'usage' => $usage,
                    'total' => $total,
                    'creator' => $item->creator,
                    'multi_login' => $item->multi_login,
                    'creator_detial' => ($item->creator_name ? ['name' => $item->creator_name->name ,'id' =>$item->creator_name->id] : [] ) ,
                    'password' => $item->password,
                    'group' => ($item->group ? $item->group->name : '---'),
                    'group_id' => $item->group_id,
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
