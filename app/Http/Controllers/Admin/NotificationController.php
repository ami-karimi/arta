<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notifications;
use App\Http\Resources\Api\AdminNotificationCollection;

class NotificationController extends Controller
{
    public function dashboard(Request $request){
        $notificationList = Notifications::where('for','admin')->where('view',0)->orderBy('id','DESC')->limit(5)->get();


        return new AdminNotificationCollection($notificationList);

    }
    public function list(Request $request){
        $notificationList = Notifications::where('for','admin')->orderBy('id','DESC')->paginate(30);
        return new AdminNotificationCollection($notificationList);
    }

    public function read(){
        $notificationList = Notifications::where('for','admin')->where('view',0)->orderBy('id','DESC')->limit(5)->update(['view' => 1]);

    }
}
