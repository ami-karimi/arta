<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsData extends Model
{
    use HasFactory;

    protected $table = 'jobs_data';
    protected $guarded = ['id'];
}
