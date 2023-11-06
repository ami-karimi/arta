<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Financial;
use App\Models\User;
use App\Models\WireGuardUsers;
use App\Utility\WireGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ServiceGroup;
use App\Models\ServiceChilds;
use App\Models\Ras;
use App\Models\TelegramOrders;
use App\Models\CardNumbers;
use App\Models\TelegramUsers;
use App\Models\TelegramUserService;
use Morilog\Jalali\Jalalian;
use App\Http\Resources\Telegram\ServiceCollection;


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
        $find_user = User::where('role','telegram_user')->where('tg_user_id',$request->user_id)->first();

        $user_id = false;
        if(!$find_user){
            $us = User::create(
                [
                    'tg_user_id' => $request->user_id,
                    'name' => $request->fullname,
                    'username' => $request->username.rand(1,999),
                    'password' => rand(1,9999999),
                    'role' => 'telegram_user',
                    'service_group' => 'telegram',

                ]
            );
            $user_id = $us->id;
        }else{
            $user_id = $find_user->id;
        }


        $order = new TelegramOrders();
        $order->user_id = $request->user_id;
        $order->fullname = $request->fullname;
        $order->service_id = $request->service_id;
        $order->child_id = $request->child_id;
        $order->server_id = $request->server_id;
        $order->price = $request->price;
        $order->ng_price = $request->ng_price;
        $order->sync_id = $user_id;
        $order->save();

        return response()->json([
            'status' => true,
            'result'  => [
                'order_id' => $order->id,
                'sync_id' => $user_id,
            ]
        ]);
    }

    public function order_remove($user_id,$order_id){
        TelegramOrders::where('user_id',(string) $user_id)->where('id',$order_id)->whereIn('status',['pending_payment','pending_approved'])->delete();
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

    public function accept_order($order_id,Request $request){
        $find_order = TelegramOrders::where('id',$order_id)->whereIn('status',['pending_payment','pending_approved'])->first();
        if(!$find_order){
            return response()->json(['status' => false,'result' => 'Order Not Found'],404);
        }
        $service_type = $find_order->service->type;


        if($service_type == 'wireguard'){
            $days = $find_order->child->days;
            $req_all = [];
            $req_all['exp_val_minute'] = floor($days * 1440);
            $req_all['expire_date'] = Carbon::now()->addMinutes($req_all['exp_val_minute']);
            $req_all['first_login'] = Carbon::now();
            $req_all['expire_set'] = 1;
        }else{
            $req_all['expire_set'] = 0;
        }

        $req_all['expire_value'] = $find_order->child->days;
        $req_all['expire_type'] = 'days';
        $req_all['multi_login'] = $find_order->child->multi_login;
        $req_all['service_group'] = $find_order->service->type;
        $req_all['tg_user_id'] = $find_order->user_id;
        $req_all['username'] = "tg".time();
        $req_all['password'] = random_int(4444,9999999999);
        $req_all['name'] = $find_order->fullname;
        $req_all['role'] = 'user';
        $req_all['tg_group_id'] = $find_order->child->id;
        $user = User::create($req_all);

        if($user && $service_type == 'wireguard') {
            $create_wr = new WireGuard($find_order->server_id, $req_all['username']);

            $user_wi = $create_wr->Run();
            if($user_wi['status']) {
                $saved = new  WireGuardUsers();
                $saved->profile_name = $user_wi['config_file'];
                $saved->user_id = $user->id;
                $saved->server_id = $find_order->server_id;
                $saved->public_key = $user_wi['client_public_key'];
                $saved->user_ip = $user_wi['ip_address'];
                $saved->save();
                exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");

            }else{
                return response()->json(['status' => false,'result' => 'cant Create Account In Server']);
            }
        }

        $new =  new Financial;
        $new->type = 'plus';
        $new->price = $find_order->price;
        $new->approved = 1;
        $new->description = 'افزایش موجودی حساب از طریق تلگرام';
        $new->creator = 2;
        $new->for = $find_order->sync_id;
        $new->save();

        $new =  new Financial;
        $new->type = 'minus';
        $new->price = $find_order->price;
        $new->approved = 1;
        $new->description = 'کسر بابت ایجاد اکانت از طریق تلگرام '.$req_all['username'];
        $new->creator = 2;
        $new->for = $find_order->sync_id;
        $new->save();

        $find_order->status = 'order_complate';
        $find_order->build_id = $user->id;
        $find_order->save();
        $response_result = [
            'username' => $req_all['username'],
            'password' => $req_all['password'],
            'service' => $service_type,
            'expire_date' => ($service_type == 'wireguard' ? Jalalian::forge($req_all['expire_date'])->__toString($req_all['expire_date'])  : false ),
            'time_left' => ($req_all['expire_set'] == 1 ? Carbon::now()->diffInDays($req_all['expire_date'], false) + 1 : false),
        ];

        if($service_type == 'wireguard' ) {
            $response_result['config_qr'] = url('/configs/'.$user_wi['config_file'].".png");
            $response_result['config_file'] = url('/configs/'.$user_wi['config_file'].".conf");
        }


        return  response()->json(
            [
                'status' => true,
                'result' => $response_result,

            ]
        );


    }


    public function manage_service($user_id){
        $find_service = User::where('tg_user_id',$user_id)->where('role','user')->get();

        if(!$find_service){
            return  response()->json(
             [
                 'data' =>    [
                     'status' => false,
                     'result' => 'No Active Service',

                 ]
             ]
            );
        }


        return new ServiceCollection($find_service);


    }
}