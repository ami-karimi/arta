<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGraph extends Model
{
    use HasFactory;

    protected $table = 'user_graph';

    protected $guarded = ['id'];

}
