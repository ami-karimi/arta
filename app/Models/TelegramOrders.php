<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramOrders extends Model
{
    use HasFactory;

    protected $table = 'tg_service_orders';
    protected $guarded = ['id'];

    public function service(){
        return $this->HasOne(ServiceGroup::class,'id','service_id');
    }
    public function child(){
        return $this->HasOne(ServiceChilds::class,'id','child_id');
    }
    public function server(){
        return $this->HasOne(Ras::class,'id','server_id');
    }
}
