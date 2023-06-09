<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WireGuardUsers extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'wireguard_users';
}
