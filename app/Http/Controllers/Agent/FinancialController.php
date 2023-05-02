<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FinancialCollection;
use App\Models\Financial;
use Illuminate\Http\Request;
use App\Utility\SendNotificationAdmin;

class FinancialController extends Controller
{
    public function index(Request $request){
        $perpage = ($request->per_page && $request->per_page < 100 ? (int)  $request->per_page : 4);
        $financial =  Financial::where('for',auth()->user()->id)->orWhere('creator',auth()->user()->id);
        if($request->approved){
           if($request->approved == 'approved'){
               $financial->where('approved',1);
           }
            if($request->approved == 'awaiting'){
                $financial->where('approved',0);
            }
            if($request->approved == 'rejected'){
                $financial->where('approved',2);
            }
        }

        if($request->type){
            if($request->type == 'plus'){
                $financial->where('type','plus');
            }
            if($request->type == 'plus_amn'){
                $financial->where('type','plus_amn');
            }
            if($request->type == 'minus'){
                $financial->where('type','minus');
            }
        }
        return new FinancialCollection($financial->orderBy('id','DESC')->paginate($perpage));
    }

    public function create(Request $request){
        if(!$request->price){
            return response()->json([
                'message' => 'لطفا مبلغ پرداختی را وارد نمایید'
            ],403);
        }
        if((int) $request->price < 50000){
            return response()->json([
                'message' => 'مبلغ پرداختی نباید کمتر از 50،000 تومان باشد'
            ],403);
        }
        if(!$request->has('attachment')){
            return response()->json([
                'message' => 'لطفا رسید پرداخت را انتخاب نمایید'
            ],403);
        }
        if(strlen($request->description) > 2000){
            return response()->json([
                'message' => 'توضیحات پرداخت نباید بیشتر از 2000 کاراکتر باشد'
            ],403);
        }

        $attachment = false;
        $allowmimie = ['image/jpg','image/jpeg','image/png'];
        if($request->file('attachment')){
            $file = $request->file('attachment');
            $mimie = $file->getClientMimeType();
            if(!in_array($mimie,$allowmimie)){
                return response()->json([
                    'message' => 'لطفا یک تصویر با فرمت jpg,jpeg,png انتخاب نمایید!'
                ],403);
            }
            $imageName = time().'.'.$request->attachment->extension();
            $attachment =  $request->attachment->move(public_path('attachment/payment'), $imageName);
        }
        $save = new Financial();

        $save->creator = auth()->user()->id;
        $save->for = auth()->user()->id;
        $save->description = $request->description;
        $save->type = 'plus';
        $save->approved = 0;
        $save->price = $request->price;
        if($attachment) {
            $save->attachment = '/attachment/payment/'.$imageName;
        }
        $save->save();

        SendNotificationAdmin::send(auth()->user()->id,'financial_create',['price' => $request->price ]);

        return response()->json([
            'message' => 'سند پرداختی با موفقیت ثبت شد بعد از تایید مدیریت به موجودی پنل اضافه خواهد شد!'
        ]);

    }

    public function edit(Request $request,$id){

        $save =  Financial::where('id',$id)->where('approved','!=',1)->first();
        if(!$save){
            return response()->json([
                'message' => 'سند پرداختی یافت نشد یا در وضعیت تایید شده میباشد'
            ],403);
        }
        if($save->for_user){
            if($save->for_user->role == 'agent'){
                if($save->for !== auth()->user()->id){
                    return response()->json([
                        'message' => 'سند پرداختی یافت نشد یا در وضعیت تایید شده میباشد'
                    ],403);
                }
            }
        }else{
            return response()->json([
                'message' => 'سند پرداختی یافت نشد یا در وضعیت تایید شده میباشد'
            ],403);
        }

        if(!$request->price){
            return response()->json([
                'message' => 'لطفا مبلغ پرداختی را وارد نمایید'
            ],403);
        }
        if((int) $request->price < 50000){
            return response()->json([
                'message' => 'مبلغ پرداختی نباید کمتر از 50،000 تومان باشد'
            ],403);
        }

        if(strlen($request->description) > 2000){
            return response()->json([
                'message' => 'توضیحات پرداخت نباید بیشتر از 2000 کاراکتر باشد'
            ],403);
        }

        $attachment = false;
        $allowmimie = ['image/jpg','image/jpeg','image/png'];
        if($request->file('attachment')){
            $file = $request->file('attachment');
            $mimie = $file->getClientMimeType();
            if(!in_array($mimie,$allowmimie)){
                return response()->json([
                    'message' => 'لطفا یک تصویر با فرمت jpg,jpeg,png انتخاب نمایید!'
                ],403);
            }
            $imageName = time().'.'.$request->attachment->extension();
            $attachment =  $request->attachment->move(public_path('attachment/payment'), $imageName);
        }

        $save->description = $request->description;
        $save->type = 'plus';
        if($save->for_user->role == 'agent') {
            $save->approved = 0;
        }
        if($save->for_user->role == 'user') {
            $save->approved =  ($request->approved === 'true' ? 1 : 0);
        }
        $save->price = $request->price;
        if($attachment) {
            $save->attachment = '/attachment/payment/'.$imageName;
        }



        $save->save();
        SendNotificationAdmin::send(auth()->user()->id,'financial_edit',['id' => $save->id ]);


        return response()->json([
            'message' => 'سند با موفقیت بروزرسانی شد و در وضعیت در انتظار قرار گرفت!'
        ]);

    }
}
