<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use  HasFactory, Notifiable;

    protected $guarded = ['id'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public function setPasswordAttribute($password){
        $this->attributes['password'] = $password;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    function group(){
        return $this->hasOne(Groups::class,'id','group_id');
    }
    function creator_name(){
        return $this->hasOne(User::class,'id','creator');
    }

    function isOnline(){
        return $this->hasOne(RadAcct::class,'username','username')->where('acctstoptime','=',NULL);
    }
    function raddacct(){
        return $this->hasOne(RadAcct::class,'username','username');
    }
    function agent_users(){
        return $this->hasMany(User::class,'creator','id');
    }
    function v2ray_server(){
        return $this->hasOne(Ras::class,'id','v2ray_location');
    }
    function wg(){
        return $this->hasOne(WireGuardUsers::class,'user_id','id');
    }

    function tg_group(){
        return $this->hasOne(ServiceChilds::class,'id','tg_group_id');
    }

}
