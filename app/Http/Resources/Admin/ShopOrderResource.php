<?php

namespace App\Http\Resources\Admin;

use App\Models\ShopCategoryChild;
use App\Utility\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class ShopOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $random_username =  null;
        $random_password = null;
        $account_text = "";
        if($this->user){
            $username = $this->user->username;
            $password = $this->user->password;
            $expireDate = ($this->user->expire_date ?   Jalalian::forge($this->user->expire_date)->format('Y-m-d H:i')  : 'بعد از اولین اتصال');
            $plan = $this->user->group->name;

            $account_text = sprintf(
                "کاربر گرامی، اطلاعات اکانت خریداری شده شما به شرح زیر است:\n\nنام کاربری: %s\nکلمه عبور: %s\nتاریخ انقضا: %s\nپلن: %s",
                $username,
                $password,
                $expireDate,
                $plan
            );
        }

        if($this->order_status !== 'completed') {
            $su = substr($this->email, 0, 2);
            $random_username = Helper::generateUsername($su);
            $random_password = str_pad(rand(0, 99999), 6, '0', STR_PAD_LEFT);
        }

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_method' => $this->payment_method,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'category_id' => $this->category_id,
            'category' => ($this->category ? new ShopCategoryResource($this->category) : false),
            'plan_id' => $this->plan_id,
            'plan' => ($this->plan ? new ShopCategoryChildResource($this->plan) : false),
            'order_status' => $this->order_status,
            'expired_orderin' => Helper::check_expired($this->expired_orderin),
            'expired_date' => Jalalian::forge($this->expired_orderin)->format('Y-m-d H:i'),
            'ip_address' => $this->ip_address,
            'device' => $this->device,
            'user'=> [
              'username' => $random_username,
              'password' => $random_password,
            ],
            'created_user' => ($this->user
                ? [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'password' => $this->user->password,
                    'account_text' => $account_text,
                  ]
                : false),

            'created_at' => Jalalian::forge($this->created_at)->format('Y-m-d H:i'),
            'updated_at' => Jalalian::forge($this->updated_at)->format('Y-m-d H:i'),
        ];
    }
}
