<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserGraph extends Model
{
    use HasFactory;

    protected $table = 'user_graph';

    protected $guarded = ['id'];

    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }

    public function scopeEndBandwidth(Builder $query){
        $query->join('users','users.id','=','user_graph.user_id')->where('users.is_enabled',1)->where("users.max_usage","<=","total_usage");
    }
}
