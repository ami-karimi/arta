<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramVerifyCode extends Model
{
    use HasFactory;

    protected $table = 'tg_verify_code';
    protected $guarded = ['id'];
    public $timestamps = false;
}
