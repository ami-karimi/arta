<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopOrders;
use App\Models\ShopPayments;
use App\Utility\Helper;
use App\Utility\WireGuard;
use Illuminate\Http\Request;
use App\Models\ShopCategory;
use App\Models\ShopCategoryChild;
use App\Http\Resources\Admin\ShopCategoryResource;
use App\Http\Resources\Admin\ShopCategoryChildResource;
use App\Http\Resources\Admin\ShopOrderResource;
use App\Http\Resources\Admin\ShopPaymentsMethodResource;
use App\Http\Resources\Admin\PaymentsListResource;
use function Termwind\renderUsing;
use App\Models\ShopPaymentMethods;
use App\Jobs\SendShopAcceptOrderEmailJob;
use App\Jobs\SendShopRejectOrderEmailJob;
use App\Jobs\ShopCreateWireguardAccount;
use App\Jobs\ShopCreateV2rayAccount;
use App\Jobs\ShopCreateMultiAccount;
use App\Models\User;


class ShopController extends Controller
{
    public function index(Request $request){
        $shop_cat = new ShopCategory();

        return ShopCategoryResource::collection($shop_cat->paginate(5));
    }

    public function create_category(Request $request){
        $cat = new ShopCategory();
        $cat->name = $request->name;
        $cat->description = $request->description;
        $cat->account_type = $request->account_type;
        if($request->is_enabled){
            $cat->is_enabled = true;
        }
        $cat->save();

        return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد.']);
    }

    public function update($id,Request $request){
        $find = ShopCategory::where('id',$id)->first();
        if(!$find){
            return response()->json(['status' => true,'message' => 'خطا! یافت نشد!'],403);
        }
        $find->name = $request->name;
        $find->description = $request->description;
        $find->account_type = $request->account_type;
        $find->is_enabled = ($request->is_enabled ? 1 : 0);
        $find->save();
        return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد.']);
    }

    public function get_config($id){
        $find = ShopCategory::where('id',$id)->first();
        if(!$find){
            return response()->json(['status' => true,'message' => 'خطا! یافت نشد!'],403);
        }

        $find_childs = ShopCategoryChild::where('category_id',$find->id)->get();

        return response()->json([
            'status' => true,
            'parent' => new ShopCategoryResource($find),
            'childs' =>  ShopCategoryChildResource::collection($find_childs),
        ]);

    }

    public function create_sub_category(Request $request){
        $findParent = ShopCategory::where('id',$request->category_id)->first();
        if(!$findParent){
            return response()->json(['status' => true,'message' => 'خطا! یافت نشد!'],403);
        }

        ShopCategoryChild::create([
            'category_id' => $request->category_id,
            'description' => $request->description,
            'group_id' => $request->group_id,
            'is_enabled' => ($request->is_enabled ? 1 : 0),
        ]);

        return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد.']);
    }

    public function update_sub_category($id,Request $request){
        $find = ShopCategoryChild::where('id',$request->id)->first();
        if(!$find){
            return response()->json(['status' => true,'message' => 'خطا! یافت نشد!'],403);
        }
        if($find->group_id !== $request->group_id){
            $findLast = ShopCategoryChild::where('group_id',$request->group_id)->first();
            if($findLast){
                return response()->json(['status' => true,'message' => 'امکان تغییر گروه وجود ندارد زیرا بر روی گروه دیگری فعال است!'],403);
            }
        }
        $find->category_id = $request->category_id;
        $find->description = $request->description;
        $find->is_enabled = ($request->is_enabled ? 1 : 0);
        $find->group_id = $request->group_id;
        $find->save();
        return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد.']);
    }

    public function edit_payment(Request $request){

            ShopPaymentMethods::updateOrCreate([
                'payment_type' => $request->payment_type
            ],[
                'payment_type' => $request->payment_type,
                'data' => json_encode($request->data),
                'is_enabled' => ($request->data['is_enabled'] ? 1 : false)
            ]);

            return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد.']);
    }

    public function get_payments_method(){

        return  ShopPaymentsMethodResource::collection(ShopPaymentMethods::get());
    }

    public function payments_list(Request $request){
        $payments = new ShopPayments();
           if($request->order_id){
               $payments = $payments->where('order_id',$request->order_id);
           }
        return PaymentsListResource::collection($payments->orderBy('updated_at','DESC')->paginate(5));
    }

    public function get_order($id){
        $order = ShopOrders::where('id',$id)->first();
        if(!$order){
            return response()->json(['status' => true,'message' => 'سفارش یافت نشد!'],404);
        }

        return new ShopOrderResource($order);
    }

    public function all_category_get(){
        $all_cat = ShopCategory::select('name','id')->get();
        return response()->json($all_cat);
    }
    public function category_sub_get($parent_id){
        $sub_cat = ShopCategoryChild::where('category_id',$parent_id)->get();

        return  ShopCategoryChildResource::collection($sub_cat);
    }

    public function update_payment($id,Request $request){
        $find = ShopPayments::where('id',$id)->first();
        if(!$find){
            return response()->json(['status' => true,'message' => 'رسید یافت نشد!'],404);
        }
        $last_status = $find->status;
        $find->payment_price = $request->payment_price;
        $find->status = $request->status;
        $find->closed_reason = $request->closed_reason;
        $find->save();

        if($request->status !== $last_status){
            if($request->status == 'approved'){
                SendShopAcceptOrderEmailJob::dispatch($find,$find->order);
            }
            if($request->status == 'closed'){
                SendShopRejectOrderEmailJob::dispatch($find,$find->order);
            }
        }


        return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد!']);

    }


    public function create_account($id,Request $request){
        $Order = ShopOrders::where('id',$id)->first();
        if(!$Order){
            return response()->json(['status' => true,'message' => 'رسید یافت نشد!'],404);
        }

        $ServiceType = [
          'wireguard' => 'wireguard',
          'l2tp' => 'l2tp_cisco',
          'v2ray' => 'v2ray',
        ];

        $request_all = $request->all();
        $username = $request_all['user']['username'];
        $password = $request_all['user']['password'];
        $service_type = $ServiceType[$Order->category->account_type];
        $group_id = $Order->plan->group_id;
        $name = $Order->name;
        $phonenumber = $Order->phone_number;

        $user_list = [];
        $user_list[] = [
          'username' => $username,
          'password' => $password,
        ];
        $data = [];

        if($service_type == 'wireguard'){
            $create_wr = new WireGuard($request_all['wg_server_id'],$username);
            if(!$create_wr->findAvailableIp()){
                return response()->json(['status' => true,'message' => 'سرور مورد نظر ظرفیت ندارد!'],404);
            }

            $data = [
                'wg_server_id' => $request_all['wg_server_id']
            ];
        }
        if($service_type == 'v2ray'){
             $data = [
                 'protocol_v2ray' => $request_all['protocol_v2ray'],
                 'v2ray_location' => $request_all['v2ray_location'],
             ];
        }



        $AccountDetail = Helper::AccountConfig($service_type,$user_list,$group_id,$name,$phonenumber,auth()->user()->id,$data);
        Helper::SaveEventOrder(
            $Order->id,
            vsprintf('سفارش در صف ایجاد اکانت قرار گرفت.',[])
        );

        $user = User::create($AccountDetail['result']);

        if($service_type == 'wireguard'){
            ShopCreateWireguardAccount::dispatch($Order,$user);
        }
        if($service_type == 'v2ray'){
            ShopCreateV2rayAccount::dispatch($Order,$user);
        }

        if($Order->category->account_type == "l2tp"){
            ShopCreateMultiAccount::dispatch($Order,$user);
        }

        return response()->json([
            'status' => true,
            'message'=> 'با موفقیت در صف ایجاد قرار گرفت.'
        ]);
    }

    public function order_list(Request $request){
        $per_page = $request->item_per_page ?? 15;
        $shop_cat = new ShopOrders();

        $shop_cat = $shop_cat->orderBy('id','DESC')->paginate($per_page);

        return ShopOrderResource::collection($shop_cat);
    }

}
