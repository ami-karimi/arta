<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMetas extends Model
{
    use HasFactory;

    protected $table = 'user_metas';

    protected $guarded = ['id'];
}
