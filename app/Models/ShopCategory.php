<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    use HasFactory;

    protected $table = 'shop_category';
    protected $guarded = ['id'];

    public function child_category(){
        return $this->hasMany(ShopCategoryChild::class,'category_id','id');
    }
}
