<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcctSaved extends Model
{
    use HasFactory;

    protected $table = 'acct_saved';
    protected $guarded = ['id'];

    public function by(){
        return $this->hasOne(User::class,'creator','id');
    }

}
