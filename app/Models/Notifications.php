<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    use HasFactory;
    protected $table = 'notifications';

    protected $guarded = ['id'];


    public function sender(){
        return $this->hasOne(User::class,'id','from');
    }
}
