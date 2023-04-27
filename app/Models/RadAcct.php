<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class RadAcct extends Model implements  JWTSubject
{

    use HasFactory;

    protected $table = 'radacct';
    protected $guarded = ['radacctid'];

    protected $primaryKey = 'radacctid';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    function servername(){
        return $this->hasOne(Ras::class,'ipaddress','nasipaddress');
    }


}
