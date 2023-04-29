<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CardNumbers;
use App\Http\Resources\Api\CardsCollection;
use App\Utility\SendNotificationAdmin;


class CardsController extends Controller
{
    public function list(Request $request){
        $cards = CardNumbers::orderBy('id','DESC')->paginate(10);


        return new CardsCollection($cards);
    }
    public function delete($id){
        CardNumbers::where('id',$id)->delete();
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
        $req_all['for'] = ($request->for ? $request->for : 0);
        $req_all['is_enabled'] = ($request->is_enabled ? 1 : 0);

        if($request->for) {
            SendNotificationAdmin::send('admin', 'admin_create_card', ['for' => $request->for]);
        }


        CardNumbers::create($req_all);

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
        $req_all['for'] = ($request->for ? $request->for : 0);
        $req_all['is_enabled'] = ($request->is_enabled ? 1 : 0);

        if($find->is_enabled !==  ($request->is_enabled ? 1 : 0)) {
            if(($request->is_enabled ? 1 : 0)) {
                SendNotificationAdmin::send('admin', 'admin_enabled_card', ['id' => $find->id,'for' => $find->for]);
            }else{
                SendNotificationAdmin::send('admin', 'admin_disabled_card', ['id' => $find->id,'for' => $find->for]);
            }
        }



        $find->update($req_all);

        return response()->json(['message' => 'شماره حساب با موفقیت بروزرسانی شد!']);
    }
}
