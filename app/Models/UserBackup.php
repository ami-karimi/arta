<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBackup extends Model
{
    use HasFactory;

    protected $table = 'users_backup';

    protected $guarded = ['id'];


}
