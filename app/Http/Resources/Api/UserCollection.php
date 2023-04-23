<?php

namespace App\Http\Resources\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use \Morilog\Jalali\Jalalian;
use App\Models\RadAcct;
use App\Models\Groups;
use App\Models\User;
class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'groups' => Groups::select('name','id','price_reseler')->get(),
            'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
            'data' => $this->collection->map(function($item){
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'username' => $item->username,
                    'creator' => $item->creator,
                    'multi_login' => $item->multi_login,
                    'creator_detial' => ($item->creator_name ? ['name' => $item->creator_name->name ,'id' =>$item->creator_name->id] : [] ) ,
                    'password' => $item->password,
                    'group' => ($item->group ? $item->group->name : '---'),
                    'group_id' => $item->group_id,
                    'expire_date' => ($item->expire_date !== NULL ? Jalalian::forge($item->expire_date)->__toString() : '---'),
                    'time_left' => ($item->expire_date !== NULL ? Carbon::now()->diffInDays($item->expire_date, false) : false),
                    'status' => ($item->isOnline ? 'online': 'offline'),
                    'first_login' =>($item->first_login !== NULL ? Jalalian::forge($item->first_login)->__toString() : '---'),
                    'is_enabled' => $item->is_enabled ,
                    'created_at' => Jalalian::forge($item->created_at)->__toString(),
                ];
            }),

        ];
    }
}