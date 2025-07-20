<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopCategoryChild extends Model
{
    use HasFactory;
    protected $table = 'shop_category_child';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function group(){
        return $this->hasOne(Groups::class,'id','group_id');
    }

}
