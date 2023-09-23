<?php

namespace App\Http\Resources\Api;

use App\Models\Financial;
use App\Utility\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;
use App\Models\Groups;
use App\Models\PriceReseler;

class AgentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $minus_income = Financial::where('for',$this->id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',$this->id)->where('approved',1)->whereIn('type',['plus'])->sum('price');

        $amn_price = Financial::where('for',$this->id)->where('approved',0)->whereIn('type',['plus_amn'])->sum('price');
        $minus_price = Financial::where('for',$this->id)->where('approved',1)->whereIn('type',['minus_amn'])->sum('price');
        if($minus_price){
            $amn_price = $amn_price -  $minus_price;
        }

        $listGroup = Groups::all();
        $map_price = $listGroup->map(function($item){
            $findS = PriceReseler::where('group_id',$item->id)->where('reseler_id',$this->id)->first();
            if(auth()->user()->role == 'admin'){
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price_reseler,
                    'price_for' => ($findS ? $findS->price : $item->price_reseler),
                ];
            }
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price_for_reseler' => $item->price_reseler,
                'reseler_price' => ($findS ? $findS->price : $item->price_reseler),
            ];
        });

        $block = $amn_price;
        $block = ($block < 0 ? 0 : $block);

        $incom  =  $icom_user - $minus_income;


        $priceList = Helper::GetReselerGroupList('list',false,$this->id) ;

        if(auth()->user()->role == 'agent') {
            if (auth()->user()->creator) {
                $priceList = array_filter($priceList, function ($item) {
                    return $item['status_code'] !== "2" && $item['status_code'] !== "0";
                });
            } else {
                $priceList = array_filter($priceList, function ($item) {
                    return $item['status_code'] !== "3"  && $item['status_code'] !== "0";
                });
            }
        }

        return [
            'price_lists' => $priceList,
            'can_agent' => (!$this->creator ? true : false),
            'detail' => [
                'id' => $this->id,
                'name' => $this->name,
                'role' => $this->role,
                'role_name' => ($this->role === 'admin' ? 'مدیر کل' : 'نماینده') ,
                'email' => $this->email,
                'is_enabled' => $this->is_enabled,
                'incom' => $this->incom,
                'created_at' => Jalalian::forge($this->created_at)->__toString()
            ],
            'users' => $this->when($this->agent_users !== null,  new UserCollection($this->agent_users()->paginate(10)))  ,
            'all_users_active' =>  $this->when($this->agent_users !== null, $this->agent_users()->where('is_enabled',1)->count()),
            'all_users' =>  $this->when($this->agent_users !== null, $this->agent_users->count()),
            'all_users_expire' =>  $this->when($this->agent_users !== null, $this->agent_users->where('expire_date','!=',NULL)->where('expire_date','<=',Carbon::now('Asia/Tehran'))->count()),
            'agent_income' =>  number_format($incom),
            'block' =>  number_format($block),
            'price_list' => [],
        ];
    }
}
