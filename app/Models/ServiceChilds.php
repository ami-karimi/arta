<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceChilds extends Model
{
    use HasFactory;
    protected $table = 'service_childs';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function parent(){
        return $this->hasOne(ServiceGroup::class,'id','group_id');
    }
}
