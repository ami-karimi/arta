<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financial extends Model
{
    use HasFactory;

    protected $table = 'financial';
    protected $guarded = ['id'];

    public function creator_by(){
        return $this->hasOne(User::class,'id','creator');
    }

    public function for_user(){
        return $this->hasOne(User::class,'id','for');
    }
}
