<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WireGuardUsers extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'wireguard_users';

    public function server()
    {
        return $this->hasOne(Ras::class,'id','server_id');
    }


    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
}
