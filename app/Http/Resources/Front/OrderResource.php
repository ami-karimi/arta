<?php

namespace App\Http\Resources\Front;

use App\Http\Resources\Admin\ShopCategoryResource;
use App\Models\ShopPaymentMethods;
use App\Utility\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

          $account_data = false;
          $payment_detail = false;
          if($this->user){
               if($this->user->service_group == 'v2ray'){
                   $account_data = [
                      'location' =>  $this->user->v2ray_server->server_location,
                      'flag' =>  $this->user->v2ray_server->flag,
                      'url' =>  $this->user->v2ray_config_uri,
                      'url_encode' => urlencode($this->user->v2ray_config_uri),
                      'sub_link' => url('/sub/'.base64_encode($this->user->username)),
                      'volume' =>  $this->user->group->group_volume,
                   ];
               }
               if($this->user->service_group == 'wireguard'){
                   $account_data = [
                       'location' =>  $this->user->wg->server->server_location,
                       'servername' =>  $this->user->wg->server->name,
                       'flag' =>  $this->user->wg->server->flag,
                       'config_file' => url("/configs/".$this->user->wg->profile_name.".conf"),
                       'config_download_patch' => url("/api/download/".$this->user->wg->profile_name.".conf"),
                   ];
               }
          }

          if($this->order_status !== "completed"){
              $GetDetailPaymentMethod = ShopPaymentMethods::where('payment_type',$this->payment_method)->first();
              $payment_detail = json_decode($GetDetailPaymentMethod->data,true);
          }

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_method' => $this->payment_method,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'category' => ($this->category ? $this->category->name : false),
            'account_type' => ($this->category ? $this->category->account_type : false),
            'plan' => ($this->plan ? $this->plan->group->name : false),
            'plan_price' => ($this->plan ? $this->plan->group->price : 0),
            'expired_orderin' => Helper::check_expired($this->expired_orderin),
            'created_user' => ($this->user
                ? [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'password' => $this->user->password,
                    'expired_date' => ($this->user->expire_date ? Jalalian::forge($this->user->expire_date)->format('Y-m-d H:i') : false)   ,
                ]
                : false),
            'account_data' => $account_data,
            'order_status' => $this->order_status,
            'payment_detail' => $payment_detail,
            'payments' => ($this->payments ? OrderPaymentResource::collection($this->payments->sortByDesc('id')->values()) : []),
            'created_at' => Jalalian::forge($this->created_at)->format('Y-m-d H:i'),
            'updated_at' => Jalalian::forge($this->updated_at)->format('Y-m-d H:i'),
        ];
    }
}
