<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FinancialCollection;
use App\Http\Resources\Api\AdminFinancialCollection;
use App\Models\Groups;
use App\Models\ReselerMeta;
use App\Models\User;
use App\Utility\Helper;
use Illuminate\Http\Request;
use App\Http\Requests\AddFinancialRequest;
use App\Models\Financial;
use App\Models\PriceReseler;
use App\Utility\SendNotificationAdmin;


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
        if($request->type){
            $financial = $financial->where('type',$request->type);
        }

        $per_page = 4;
        if($request->per_page){
            $per_page = (int) $request->per_page;
        }

        return new AdminFinancialCollection($financial->orderBy('id','DESC')->paginate($per_page));
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
        if($request->admin_id) {
            $new->for = $request->admin_id;
        }
        if($request->for) {
            $new->for = $request->for;
        }
        $new->creator = auth()->user()->id;
        $new->approved = ($request->approved === 'true' ? 1 : 0);
        if($request->description) {
            $new->description = $request->description;
        }
        $new->save();

        SendNotificationAdmin::send('admin', 'create_financial_admin', [
            'id' => $new->id,
            'for' => $new->for,
            'price' => $new->price,
            'type' => $new->type,
            'approved' => $new->approved,
            'description' => $request->description,
        ]);


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

        if($new->price !== $request->price){

              SendNotificationAdmin::send('admin', 'admin_change_price_factore', [
                  'id' => $new->id,
                  'price' => $new->price,
                  'new_price' => $request->price,
                  'for' => $new->for,
                  'description' => $request->description,
              ]);
        }
        $new->price = $request->price;
        if($attachment){
            $new->attachment = '/attachment/payment/'.$imageName;
        }
        $new->for = $request->admin_id;
        $new->creator = auth()->user()->id;

        if($new->approved !== ($request->approved === 'true' ? 1 : 0)) {
            if(($request->approved === 'true' ? 1 : 0)) {
                SendNotificationAdmin::send('admin', 'approved_financial_admin', [
                    'id' => $new->id,
                    'price' => $new->price,
                    'for' => $new->for,
                    'description' => $request->description,
                ]);

            }else{
                SendNotificationAdmin::send('admin', 'reject_financial_admin', [
                    'id' => $new->id,
                    'price' => $new->price,
                    'for' => $new->for,
                    'description' => $request->description,
                ]);
            }
        }


        $new->approved = ($request->approved === 'true' ? 1 : 0);



        if($request->description) {
            $new->description = $request->description;
        }
        $new->save();

        return response()->json([
            'status' => true,
            'message'=> 'با موفقیت ثبت شد!'
            ]);
    }

    public function save_custom_price(Request $request,$group_id){

        $agent = User::where('id',$request->agent_id)->first();
        if(!$agent){
            return response()->json(['message' => 'نماینده مورد نظر یافت نشد!']);

        }


        $findGroup = Helper::GetReselerGroupList('one',$group_id,$agent->id);

        if(!$findGroup){
            return response()->json(['message' => 'گروه مورد نظر یافت نشد!']);
        }


        if(!$agent->creator) {
            ReselerMeta::updateOrCreate([
                'reseler_id' => $agent->id,
                'key' => 'reseler_price_' . $findGroup['id'],
            ],
                [
                    'reseler_id' => $agent->id,
                    'key' => 'reseler_price_' . $findGroup['id'],
                    'value' => $request->item['reseler_price'],
                ]);


            ReselerMeta::updateOrCreate([
                'reseler_id' => $agent->id,
                'key' => 'disabled_group_' . $findGroup['id'],
            ],
                [
                    'reseler_id' => $agent->id,
                    'key' => 'disabled_group_' . $findGroup['id'],
                    'value' => (!$request->item['status'] ? 3 : 1),
                ]);


            ReselerMeta::updateOrCreate([
                'reseler_id' => $agent->id,
                'key' => 'price_for_reseler_' . $findGroup['id'],
            ],
                [
                    'reseler_id' => $agent->id,
                    'key' => 'price_for_reseler_' . $findGroup['id'],
                    'value' => $request->item['price_for_reseler'],
                ]);
        }else{
            ReselerMeta::updateOrCreate([
                'reseler_id' => $agent->creator,
                'key' => 'price_for_reseler_' . $findGroup['id']."_for_".$agent->id,
            ],
                [
                    'reseler_id' => $agent->creator,
                    'key' =>  'price_for_reseler_' . $findGroup['id']."_for_".$agent->id,
                    'value' => $request->item['reseler_price'],
                ]);

            ReselerMeta::updateOrCreate([
                'reseler_id' => $agent->creator,
                'key' => 'disabled_group_'.$findGroup['id']."_for_".$agent->id,
            ],
                [
                    'reseler_id' => $agent->creator,
                    'key' => 'disabled_group_'.$findGroup['id']."_for_".$agent->id,
                    'value' => $request->item['status'],
                ]);


        }



        return response()->json([
            'data' => $findGroup,
            'message' => 'بروزرسانی گروه '.$findGroup['name']." با موفقیت انجام شد.",
        ]);

        /*
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
                    SendNotificationAdmin::send('admin', 'admin_change_custom_price', [
                        'price' => $findGroup->price_reseler,
                        'new_price' => $row['price_for'],
                        'for' => $id,
                        'group_name' => $findGroup->name,
                    ]);


                }
            }
        }
        return response()->json([
            'status' => true,
            'message'=> 'بروزرسانی با موفقت انجام شد!'
        ]);
        */
    }

    public function destory($id){
        Financial::where('id',$id)->delete();

        return response()->json([
            'message' => 'عملیات با موفقیت انجام شد!',
        ]);
    }

}
