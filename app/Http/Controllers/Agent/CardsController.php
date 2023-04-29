<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CardNumbers;
use App\Http\Resources\Api\CardsCollection;
use App\Utility\SendNotificationAdmin;

class CardsController extends Controller
{
    public function list(Request $request){
        $cards = CardNumbers::orderBy('id','DESC')->where('for',auth()->user()->id)->paginate(10);


        return new CardsCollection($cards);
    }
    public function delete($id){
        CardNumbers::where('id',$id)->where('for',auth()->user()->id)->delete();
        return response()->json(['message' => 'آیتم با موفقیت حذف شد']);
    }

    public function create(Request $request){
        if(!$request->card_number_name){
            return response()->json(['message' => 'لطفا نام صاحب کارت را وارد نمایید'],403);
        }
        if(!$request->card_number){
            return response()->json(['message' => 'لطفا شماره کارت را وارد نمایید'],403);
        }
        if(!$request->card_number_bank){
            return response()->json(['message' => 'لطفا نام بانک را وارد نمایید'],403);
        }

        $req_all = $request->all();
        $req_all['for'] = auth()->user()->id;
        $req_all['is_enabled'] = ($request->is_enabled ? 1 : 0);
        $saved = CardNumbers::create($req_all);
        SendNotificationAdmin::send(auth()->user()->id,'created_cart_agent',['id' => $saved->id ]);

        return response()->json(['message' => 'شماره حساب با موفقیت ثبت شد!']);
    }
    public function edit(Request $request,$id){
        $find = CardNumbers::where('id',$id)->first();
        if(!$find){
            return response()->json(['message' => 'اطلاعات حساب یافت نشد!'],403);
        }

        if(!$request->card_number_name){
            return response()->json(['message' => 'لطفا نام صاحب کارت را وارد نمایید'],403);
        }
        if(!$request->card_number){
            return response()->json(['message' => 'لطفا شماره کارت را وارد نمایید'],403);
        }
        if(!$request->card_number_bank){
            return response()->json(['message' => 'لطفا نام بانک را وارد نمایید'],403);
        }

        $req_all = $request->all();
        $req_all['for'] = auth()->user()->id;
        $req_all['is_enabled'] = ($request->is_enabled ? 1 : 0);
        $find->update($req_all);
        SendNotificationAdmin::send(auth()->user()->id,'edit_cart_agent',['id' => $find->id ]);

        return response()->json(['message' => 'شماره حساب با موفقیت بروزرسانی شد!']);
    }
}
