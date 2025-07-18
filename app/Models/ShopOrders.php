<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrders extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = 'shop_orders';

    public function category(){
        return $this->hasOne(ShopCategory::class,'id','category_id');
    }

    public function plan(){
        return $this->hasOne(ShopCategoryChild::class,'id','plan_id');
    }
    public function payments(){
        return $this->hasMany(ShopPayments::class,'order_id','id');
    }

}
