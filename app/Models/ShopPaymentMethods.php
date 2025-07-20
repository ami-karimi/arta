<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopPaymentMethods extends Model
{
    use HasFactory;
    public $table = 'shop_payment_methods';
    protected $guarded = ['id'];
}
