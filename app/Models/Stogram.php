<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stogram extends Model
{
    use HasFactory;

    protected $table = 'stogram';

    protected $guarded = ['id'];
}
