<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FinancialCollection;
use App\Models\Groups;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\AddFinancialRequest;
use App\Models\Financial;
use App\Models\PriceReseler;

class FinancialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request){
        $financial = new Financial();
        if($request->for){
            $financial = $financial->where('for',$request->for);
        }


        return new FinancialCollection($financial->orderBy('id','DESC')->paginate(4));
    }
    public function create(Request $request){

        if(!$request->price){
            return response()->json([
                'status' => true,
                'message'=> 'لطفا مبلغ را وارد نمایید!'
            ],403);
        }
        if(!$request->type){
            return response()->json([
                'status' => true,
                'message'=> 'لطفا نوع را انتخاب نمایید!'
            ],403);
        }
        $attachment = false;
        if($request->has('attachment')){
            if($request->file('attachment')){
                $imageName = time().'.'.$request->attachment->extension();
                $attachment =  $request->attachment->move(public_path('attachment/payment'), $imageName);
            }
        }

        $new =  new Financial;
        $new->type = $request->type;
        $new->price = $request->price;
        if($attachment){
            $new->attachment = '/attachment/payment/'.$imageName;
        }
        $new->for = $request->admin_id;
        $new->creator = auth()->user()->id;
        $new->approved = ($request->approved ? 1 : 0);
        if($request->description) {
            $new->description = $request->description;
        }
        $new->save();

        return response()->json([
            'status' => true,
            'message'=> 'با موفقیت ثبت شد!'
            ]);
    }
    public function edit(Request $request,$id){
        $new =   Financial::where('id',$id)->first();
        if(!$new){
            return response()->json([
                'status' => true,
                'message'=> 'رسید یافت نشد'
            ],403);
        }
        if(!$request->price){
            return response()->json([
                'status' => true,
                'message'=> 'لطفا مبلغ را وارد نمایید!'
            ],403);
        }
        if(!$request->type){
            return response()->json([
                'status' => true,
                'message'=> 'لطفا نوع را انتخاب نمایید!'
            ],403);
        }
        $attachment = false;
        if($request->has('attachment')){
            if($request->file('attachment')){
                $imageName = time().'.'.$request->attachment->extension();
                $attachment =  $request->attachment->move(public_path('attachment/payment'), $imageName);
            }
        }

        $new->type = $request->type;
        $new->price = $request->price;
        if($attachment){
            $new->attachment = '/attachment/payment/'.$imageName;
        }
        $new->for = $request->admin_id;
        $new->creator = auth()->user()->id;
        $new->approved = ($request->approved ? 1 : 0);
        if($request->description) {
            $new->description = $request->description;
        }
        $new->save();

        return response()->json([
            'status' => true,
            'message'=> 'با موفقیت ثبت شد!'
            ]);
    }

    public function save_custom_price(Request $request,$id){
        $find_admin = User::where('id',$id)->first();
        if(!$find_admin){
            return response()->json([
                'status' => false,
                'message'=> 'نماینده یافت نشد!'
            ],403);
        }
        PriceReseler::where('reseler_id',$id)->delete();
        foreach ($request->price_list as $row){
            $findGroup = Groups::where('id',$row['id'])->first();
            if($findGroup){
                if($row['price_for']){
                    PriceReseler::create([
                        'group_id' => $findGroup->id,
                        'reseler_id' => $id,
                        'price' => $row['price_for'],
                    ]);
                }
            }
        }
        return response()->json([
            'status' => true,
            'message'=> 'بروزرسانی با موفقت انجام شد!'
        ]);
    }

}
