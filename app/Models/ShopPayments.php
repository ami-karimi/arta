<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopPayments extends Model
{
    use HasFactory;
    protected $table = 'order_payment';
    protected $guarded = ['id'];

    public function order(){
        return $this->hasOne(ShopOrders::class,'id','order_id');
    }
    public function pay_type(){
        return $this->hasOne(ShopPaymentMethods::class,'payment_type','payment_type');
    }
}
