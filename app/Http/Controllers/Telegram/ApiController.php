<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceGroup;
use App\Models\ServiceChilds;
use App\Models\Ras;
use App\Models\TelegramOrders;
use App\Models\CardNumbers;
use App\Models\TelegramUsers;
use App\Models\TelegramUserService;

class ApiController extends Controller
{
    public function get_service(){
        $services = ServiceGroup::select(['name','type','id'])->where('is_enabled',1)->get();

        return response()->json(['status' => true,'result' => $services]);
    }
    public function get_service_child($id){
        $services = ServiceGroup::where('id',$id)->where('is_enabled',1)->first();
        if(!$services){
            return response()->json(['status' => true,'result' => 'Parent Not Found'],404);
        }
        $services_child = ServiceChilds::where('group_id',$id)->where('is_enabled',1)->get();

        return response()->json(['status' => true,'result' => $services_child,'parent' => $services]);
    }

    public function getServiceInfo($parent_id,$child_id){
        $services = ServiceGroup::where('id',$parent_id)->where('is_enabled',1)->first();
        if(!$services){
            return response()->json(['status' => true,'result' => 'Parent Not Found'],404);
        }
        $services_child = ServiceChilds::where('group_id',$parent_id)->where('id',$child_id)->where('is_enabled',1)->first();
        if(!$services_child){
            return response()->json(['status' => true,'result' => 'Child Not Found'],404);
        }
        return response()->json(['status' => true,'child' => $services_child,'parent' => $services]);
    }
    public function getServiceANDServer($parent_id,$child_id,$server_id){
        $services = ServiceGroup::where('id',$parent_id)->where('is_enabled',1)->first();
        if(!$services){
            return response()->json(['status' => true,'result' => 'Parent Not Found'],404);
        }
        $services_child = ServiceChilds::where('group_id',$parent_id)->where('id',$child_id)->where('is_enabled',1)->first();
        if(!$services_child){
            return response()->json(['status' => true,'result' => 'Child Not Found'],404);
        }
        $ras = Ras::select(['server_location','id'])->where('is_enabled',1)->WhereNotNull('server_location')->where('id',$server_id)->first();
        if(!$ras){
            return response()->json(['status' => true,'result' => 'Server Not Found'],404);
        }

        return response()->json(['status' => true,'child' => $services_child,'parent' => $services,'server' => $ras]);
    }

    public function get_server($type){
        $ras = Ras::select(['server_location','id'])->where('is_enabled',1)->WhereNotNull('server_location');
        if($type == 'v2ray'){
            $ras->where('server_type','v2ray');
        }elseif($type == 'wireguard'){
            $ras->where('unlimited',1);
        }else{
            $ras->where('server_type','v2ray');
        }

        return response()->json(['status' => true,'result' => $ras->get()]);


    }
    public function check_last_order($user_id){
        $find = TelegramOrders::where('user_id',$user_id)->whereIn('status',['pending_payment','pending_approved'])->first();
        $result = false;
        if($find){
            $name = "🔰";
            if($find->child->days > 0){
                $name .= $find->child->days." روزه ".($find->child->volume > 0 ? ' - ' : '');
            }
            if($find->child->volume){
                $name .= $find->child->volume." گیگ ".($find->child->days > 0 ? ' - ' : '');
            }
            if($find->child->name){
                $name =  $find->child->name." - ";
            }

            $result = [
              'order_id' => $find->id,
              'service_id' => $find->service_id,
              'service_name' => $find->service->name,
              'child_id' => $find->service_id,
              'child_name' => $name,
              'server_id' => $find->server_id,
              'server_location' => ($find->server_id ? $find->server->server_location : false),
              'price' =>   $find->price,
              'ng_price' =>  $find->ng_price,
              'status' =>  $find->status,
            ];
        }

        return response()->json(
            [
                'status' => true,
                'result' => $result
            ]
        );
    }
    public function place_order(Request $request){
        $find_user = TelegramUsers::where('user_id',$request->user_id)->first();

        if(!$find_user){
            TelegramUsers::create(
                [
                    'user_id' => $request->user_id,
                    'fullname' => $request->fullname,
                    'username' => $request->username,
                ]
            );
        }

        TelegramOrders::create($request->only(['user_id','fullname','service_id','child_id','server_id','price','ng_price']));



    }

    public function order_remove($user_id,$order_id){
        TelegramOrders::where('user_id',(string) $user_id)->where('id',$order_id)->where('status','pending_payment')->delete();
        return response()->json(
            [
                'status' => true,
                'result' => true
            ]
        );
    }

    public function get_cart_number(){
        $cart = CardNumbers::select(['card_number_name','card_number','card_number_bank'])->where('for',0)->where('is_enabled',1)->first();

        if($cart){
            $cart->card_number = str_replace('-','',$cart->card_number);
        }
       return response()->json(
           [
               'status' => true,
               'result' => $cart
           ]
       );
    }

    public function change_order_status($order_id,Request $request){
        if(!$request->status){
            return response()->json(['status' => false,'result' => 'Server Not Found'],502);
        }
        $find_order = TelegramOrders::where('id',$order_id)->first();
        if(!$find_order){
            return response()->json(['status' => false,'result' => 'Order Not Found'],404);
        }
        $find_order->status = $request->status;
        $find_order->save();
        return response()->json(['status' => true]);

    }
}
