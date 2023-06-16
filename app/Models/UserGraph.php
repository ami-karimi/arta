<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class UserGraph extends Model
{
    use HasFactory;

    protected $table = 'user_graph';

    protected $guarded = ['id'];

    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }

    public function scopeEndBandwidth(Builder $query){
        $query->whereHas('user',function($query){
            $query->select(['id','username','max_usage','is_enabled'])->where('is_enabled',1)->havingRaw('max_usage <= ?',['total_usage']);
        });
    }
}
