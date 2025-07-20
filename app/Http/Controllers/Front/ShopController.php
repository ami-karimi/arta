<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Utility\Helper;
use Illuminate\Http\Request;
use App\Models\ShopCategory;
use App\Models\ShopCategoryChild;
use App\Models\ShopPayments;
use App\Http\Resources\Front\ShopCategoryChildResource;
use App\Http\Resources\Front\ShopCategoryResource;
use App\Models\ShopPaymentMethods;
use App\Http\Requests\Front\StoreOrderRequest;
use App\Http\Requests\Front\StoreFinalOrderRequest;
use App\Models\ShopOrders;
use function Termwind\renderUsing;
use Carbon\Carbon;
use App\Http\Resources\Front\OrderResource;

class ShopController extends Controller
{
    public function shop_package(){
        return response()->json([
           'category' => ShopCategoryResource::collection(ShopCategory::where('is_enabled',1)->get()),
           'plans' => ShopCategoryChildResource::collection(ShopCategoryChild::where('is_enabled',1)->get()),
        ]);
    }

    public function shop_payment_detail(){
        $payment = ShopPaymentMethods::select('payment_type')->where('is_enabled',1)->get();

        return response()->json($payment);
    }

    public function store_order(StoreOrderRequest $request){

        $ip = $request->getClientIp();
        $userAgent = request()->header('User-Agent');
        $order_code = Helper::generateOrderCode('CM-');


        $order = new ShopOrders();
        $order->order_id = $order_code;
        $order->name = $request->name;
        $order->phone_number = $request->phone;
        $order->email = $request->email;
        $order->payment_method = $request->payment_method;
        $order->category_id = $request->category_id;
        $order->plan_id = $request->plan_id;
        $order->ip_address = $ip;
        $order->order_status = 'created';
        $order->device = $userAgent;
        $order->expired_orderin = now()->addMinutes(20);
        $order->save();

        $GetDetailPaymentMethod = ShopPaymentMethods::where('payment_type',$request->payment_method)->first();

        return response()->json([
               'status' => false,
               'order' => [
                   'order_code' => $order_code,
                   'order_id' =>  $order->id,
                   'payment_detail' => json_decode($GetDetailPaymentMethod->data,true)
               ]
            ]);
    }

    public function update_order(Request $request){
        $order_code = $request->order_code;
        if (!preg_match('/^[A-Z]{2}-\d{5}$/', $order_code)) {
            abort(404);
        }


        $findShopOrder =  ShopOrders::where('order_id',$order_code)->first();
        if(!$findShopOrder){
            abort(404);
        }

        if($findShopOrder->order_status == 'created'){
            $expire =  Helper::check_expired($findShopOrder->expired_orderin);
            if(!$expire){
                return response()->json(['status' => false,'er_code' => 3,'message' => 'امکان دسترسی به سفارش وجود ندارد مهلت پرداخت منقضی شده است لطفا سفارش جدید ثبت کنید'],403);
            }
        }
        if($findShopOrder->order_status == 'completed'){
            return response()->json(['status' => false,'er_code' => 4,'message' => 'امکان بروزرسانی سفارش وجود ندارد!'],404);
        }


        $imageName = false;
        if($request->file('file')){
            $file = $request->file('file');
            $mimie = $file->getClientMimeType();
            $imageName = time().'.'.$request->file->extension();
            $request->file->move(public_path('attachment/offline'), $imageName);
        }
        if($findShopOrder->name !== $request->name){
            Helper::SaveEventOrder(
                $findShopOrder->id,
                vsprintf('نام از (%s) به (%s) تغییر کرد.',[$findShopOrder->name,$request->name])
            );
        }
        if($findShopOrder->email !== $request->email){
            Helper::SaveEventOrder(
                $findShopOrder->id,
                vsprintf('ایمیل از (%s) به (%s) تغییر کرد.',[$findShopOrder->email,$request->email])
            );
        }
        if($request->phone_number !== null) {
            if ($findShopOrder->phone_number !== $request->phone_number) {
                Helper::SaveEventOrder(
                    $findShopOrder->id,
                    vsprintf('شماره تماس از (%s) به (%s) تغییر کرد.', [$findShopOrder->phone_number, $request->phone_number])
                );
            }
        }


        $findShopOrder->name = $request->name;
        $findShopOrder->email = $request->email;
        $findShopOrder->phone_number = $request->phone_number;
        if($imageName){
            $store_payment =  new ShopPayments();
            $store_payment->payment_type = $findShopOrder->payment_method;
            $store_payment->order_id = $findShopOrder->id;
            $store_payment->payment_price = $findShopOrder->plan->group->price;
            $store_payment->file = '/attachment/offline/'.$imageName;
            $store_payment->status = 'pending';
            $store_payment->save();
            $findShopOrder->order_status = 'pending';
        }
        $findShopOrder->save();

        return response()->json(['status' => true,'message' => 'عملیات با موفقیت انجام شد!']);
    }

    public function final_order(StoreFinalOrderRequest $request){
        $findOrder = ShopOrders::where('order_id',$request->order_code)->where('order_status','created')->where('expired_orderin','>',now())->first();
        if(!$findOrder){
            return response()->json([
                'status' => false,
                'message' => 'سفارش فعالی با این کد سفارش یافت نشد یا ممکن است زمان ثبت اطلاعات به پایان رسیده باشد!',
            ],403);
        }

        $imageName = "";
        if($request->file('file')){
            $file = $request->file('file');
            $mimie = $file->getClientMimeType();
            $imageName = time().'.'.$request->file->extension();
            $request->file->move(public_path('attachment/offline'), $imageName);
        }

        $store_payment =  new ShopPayments();
        $store_payment->payment_type = $findOrder->payment_method;
        $store_payment->order_id = $findOrder->id;
        $store_payment->payment_price = $findOrder->plan->group->price;
        $store_payment->file = '/attachment/offline/'.$imageName;
        $store_payment->status = 'pending';
        $store_payment->save();

        $findOrder->order_status = 'pending';
        $findOrder->save();

        return response()->json([
            'status' => false,
        ],200);

    }

    public function get_order_detail(Request $request){
        $order_code = $request->order_code;
        if (!preg_match('/^[A-Z]{2}-\d{5}$/', $order_code)) {
            abort(404);
        }


        $findShopOrder =  ShopOrders::where('order_id',$order_code)->first();
        if(!$findShopOrder){
            abort(404);
        }

        if($findShopOrder->order_status == 'created'){
           $expire =  Helper::check_expired($findShopOrder->expired_orderin);
           if(!$expire){
               return response()->json(['status' => false,'er_code' => 3,'message' => 'امکان دسترسی به سفارش وجود ندارد مهلت پرداخت منقضی شده است لطفا سفارش جدید ثبت کنید'],403);
           }
        }
        if($findShopOrder->order_status == 'completed'){
            $createdDate = Carbon::parse($findShopOrder->created_at);
            $now = Carbon::now();
            $hoursDiff = $now->diffInHours($createdDate);
            if ($hoursDiff > 48) {
                return response()->json(['status' => false,'er_code' => 4,'message' => 'امکان دسترسی به سفارش پس از گذشت 48 ساعت وجود ندارد!'],404);
            }
        }


        return new OrderResource($findShopOrder);
    }
}
