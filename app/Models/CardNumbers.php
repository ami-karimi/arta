<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CardNumbers extends Model
{
    use HasFactory;
    protected $table = 'card_numbers';
    protected $guarded = ['id'];

    public function creator(){
        return $this->hasOne(User::class,'id','for');
    }
}
