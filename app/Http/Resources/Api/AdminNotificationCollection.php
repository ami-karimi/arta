<?php

namespace App\Http\Resources\Api;

use App\Models\Notifications;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Morilog\Jalali\Jalalian;

class AdminNotificationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'not_read' => Notifications::where('for','admin')->where('view',0)->orderBy('id','DESC')->count(),
            'data' => $this->collection->map(function($item){
            return [
               'id' => $item->id,
               'from' => ($item->sender ? ['id' => $item->sender->id,'role' => $item->sender->role,'name' => ($item->sender->name ? $item->sender->name : $item->sender->username )] : false),
               'content' => $item->content,
               'url' => $item->url,
               'for' => $item->for,
               'view' => $item->view,
               'sender' => $item->sender,
               'time_ago' => $item->created_at->diffForHumans(),
               'created_at' => Jalalian::forge($item->created_at)->__toString()

            ];
        })
        ];
    }
}
