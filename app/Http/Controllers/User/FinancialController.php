<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utility\SendNotificationAdmin;
use App\Models\Financial;
use App\Http\Resources\Api\UserFinancialCollection;

class FinancialController extends Controller
{

   public function list(Request $request){
       $financial =  Financial::where('for',auth()->user()->id);
       if($request->type){
           if($request->type == 'plus'){
               $financial->where('type','plus');
           }
           if($request->type == 'minus'){
               $financial->where('type','minus');
           }
           if($request->type == 'plus_amn'){
               $financial->where('type','plus_amn');
           }

       }
       if($request->approved){
           if($request->approved == 'awaiting'){
               $financial->where('type',0);
           }
           if($request->approved == 'rejected'){
               $financial->where('type',2);
           }
           if($request->approved == 'approved'){
               $financial->where('type',1);
           }

       }
       $per_page = 10;
       if($request->per_page){
           $per_page = (int) $request->per_page;
       }

       return new UserFinancialCollection($financial->orderBy('id','DESC')->paginate($per_page));
   }


   public function create(Request $request){
       if(!$request->price){
           return response()->json(['message' => 'لطفا هزینه پرداختی را وارد نمایید'],403);
       }

       if((int) $request->price < 5000){
           return response()->json(['message' => 'ثبت هزینه پرداختی زیر 5000 تومان قابل قبول نمیباشد!'],403);
       }
       if(!$request->file('attachment')){
           return response()->json(['message' => 'لطفا رسید پرداخت را انتخاب نمایید!'],403);
       }

       $creator = auth()->user()->creator;
       $attachment = false;
       $imageName = '';
       if($request->has('attachment')){
           if($request->file('attachment')){
               $imageName = time().'.'.$request->attachment->extension();
               $attachment =  $request->attachment->move(public_path('attachment/payment'), $imageName);
           }
       }

       $financial  = new Financial();
       $financial->creator = $creator;
       $financial->for = auth()->user()->id;
       $financial->description = $request->description;
       $financial->type = 'plus';
       $financial->attachment = $attachment;
       if($attachment){
           $financial->attachment = '/attachment/payment/'.$imageName;
       }
       $financial->approved = 0;
       $financial->price = $request->price;
       $financial->save();

       SendNotificationAdmin::send(auth()->user()->id,'user_send_financial',['for' => $creator ,'price' => $request->price]);

       return response()->json(['message' => 'سند پرداختی با موفقیت ثبت شد، پس از تایید به موجودی شما اضافه خواهد شد.']);

   }
}
