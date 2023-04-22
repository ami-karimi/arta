<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activitys extends Model
{
    use HasFactory;

    protected $table = 'users_activity';

    protected $guarded = ['id'];

    public function from(){
        return $this->hasOne(User::class,'id','by');
    }
}
