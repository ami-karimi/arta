<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReselerMeta extends Model
{
    use HasFactory;
    protected $table = 'rseler_meta';
    protected $guarded = ['id'];
}
