<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ras extends Model
{
    use HasFactory;
    protected $table = 'nas';
    protected $guarded = ['id'];
}
