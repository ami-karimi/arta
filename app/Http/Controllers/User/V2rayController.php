<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Financial;
use App\Models\User;
use App\Utility\SaveActivityUser;
use App\Utility\SendNotificationAdmin;
use Illuminate\Http\Request;
use App\Utility\V2rayApi;

class V2rayController extends Controller
{
   public function buy_volume(Request $request){
       if(!$request->value){
           return response()->json(['message' => 'خطا در داده ارسالی!'],403);
       }
       $value = (int) $request->value;
       if($value < 5 || $value > 100){
           return response()->json(['message' => 'خطا در داده ارسالی!'],403);
       }

       $total_price = $value * 1200;
       $minus_income = Financial::where('for', auth()->user()->creator)->where('approved', 1)->whereIn('type', ['minus'])->sum('price');
       $icom_user = Financial::where('for', auth()->user()->creator)->where('approved', 1)->whereIn('type', ['plus'])->sum('price');
       $incom = $icom_user - $minus_income;
       if($incom < $total_price){
           return response()->json(['message' => 'عدم موجودی کافی!'],403);
       }
       $findUser = User::where('id',auth()->user()->id)->first();

       if($findUser->v2ray_server){
           $login_s =new V2rayApi($findUser->v2ray_server->ipaddress,$findUser->v2ray_server->port_v2ray,$findUser->v2ray_server->username_v2ray,$findUser->v2ray_server->password_v2ray);
           if($login_s) {
               $v2ray_user =  $login_s->list(['port' => (int) $findUser->port_v2ray]);
               if(count($v2ray_user)) {
                   $base = log($v2ray_user['total'], 1024);
                   $result = pow(1024, $base - floor($base));
                   $last_total = (int) round($result, 2);
                   $new_total = $value + $last_total;
                   $login_s->update((int) $findUser->port_v2ray,['total' => $new_total]);

                   SaveActivityUser::send($findUser->id,auth()->user()->id,'by_volume_v2ray',['last' => $login_s->formatBytes($last_total,2),'new' => $login_s->formatBytes($value,2) ]);


                   $financial  = new Financial();
                   $financial->creator = $findUser->creator;
                   $financial->for = auth()->user()->id;
                   $financial->description = 'خرید حجم';
                   $financial->type = 'minus';
                   $financial->approved = 1;
                   $financial->price = $total_price;
                   $financial->save();


                   return response()->json(['message' => 'خریداری شد!'],200);

               }
           }
       }
       return response()->json(['message' => 'در حال حاضر امکان خرید حجم اضافه بر روی لوکیشن درخواستی فعلی وجود ندارد!'],403);


   }

   public function update_config(Request $request){

   }
}
