<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class backUsers extends Model
{
    use HasFactory;


    protected $table = 'back_users';

    protected $guarded = ['id'];
}
