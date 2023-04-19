<?php

namespace App\Http\Resources\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class AgentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'detail' => [
                'id' => $this->id,
                'name' => $this->name,
                'role' => $this->role,
                'role_name' => ($this->role === 'admin' ? 'مدیر کل' : 'نماینده') ,
                'email' => $this->email,
                'is_enabled' => $this->is_enabled,
                'incom' => $this->incom,
                'created_at' => Jalalian::forge($this->created_at)->__toString()
            ],
            'users' => $this->when($this->agent_users !== null,  new UserCollection($this->agent_users()->paginate(10)))  ,
            'all_users_active' =>  $this->when($this->agent_users !== null, $this->agent_users()->where('is_enabled',1)->count()),
            'all_users' =>  $this->when($this->agent_users !== null, $this->agent_users->count()),
            'all_users_expire' =>  $this->when($this->agent_users !== null, $this->agent_users->where('expire_date','!=',NULL)->where('expire_date','<=',Carbon::now('Asia/Tehran'))->count()),

        ];
    }
}
