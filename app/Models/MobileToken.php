<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileToken extends Model
{
    use HasFactory;

    protected $table = 'mobile_tokens';

    protected $guarded = ['id'];



}
