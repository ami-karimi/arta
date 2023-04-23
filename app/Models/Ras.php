<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ras extends Model
{
    use HasFactory;
    protected $table = 'nas';
    protected $guarded = ['id'];

    public function getUsersOnline(){
        return $this->hasMany(RadAcct::class,'nasipaddress','ipaddress')->where('acctstoptime','=',NULL);
    }
}
