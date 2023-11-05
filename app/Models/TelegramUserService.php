<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUserService extends Model
{
    use HasFactory;

    protected $table = 'tg_users_service_id';

    public $timestamps = false;
}
