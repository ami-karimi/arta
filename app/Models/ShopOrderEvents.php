<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopOrderEvents extends Model
{
    use HasFactory;
    protected $table = 'shop_order_events';
    protected $guarded = ['id'];

}
