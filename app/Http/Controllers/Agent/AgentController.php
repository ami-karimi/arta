<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Api\AgentDetailResource;
use App\Utility\Helper;
use App\Models\ReselerMeta;

class AgentController extends Controller
{
    public function index(){
        return new AgentDetailResource(auth()->user());
    }

    public function GetGroups(){
        $ar = Helper::GetReselerGroupList('list',false,auth()->user()->id );
        return response()->json([
               'can_agent' => (auth()->user()->creator ? false : true),
               'data' => array_filter($ar,function($item){
                   return $item['status'] == true;
               }),
            ]);
    }
    public function edit(Request $request,$group_id){
        $findGroup = Helper::GetReselerGroupList('one',$group_id);

        if(!$findGroup){
            return response()->json(['message' => 'گروه مورد نظر یافت نشد!']);
        }

        ReselerMeta::updateOrCreate([
            'reseler_id' => auth()->user()->id,
            'key' => 'group_price_'.$findGroup['id'],
        ],
        [
            'reseler_id' => auth()->user()->id,
            'key' => 'group_price_'.$findGroup['id'],
            'value' => $request->price,
        ]);


        ReselerMeta::updateOrCreate([
            'reseler_id' => auth()->user()->id,
            'key' => 'disabled_group_'.$findGroup['id'],
        ],
            [
                'reseler_id' => auth()->user()->id,
                'key' => 'disabled_group_'.$findGroup['id'],
                'value' => $request->status,
            ]);


        ReselerMeta::updateOrCreate([
            'reseler_id' => auth()->user()->id,
            'key' => 'price_for_reseler_'.$findGroup['id'],
        ],
            [
                'reseler_id' => auth()->user()->id,
                'key' => 'price_for_reseler_'.$findGroup['id'],
                'value' => $request->price_for_reseler,
            ]);



        return response()->json([
               'data' => $findGroup,
               'message' => 'بروزرسانی گروه '.$findGroup['name']." با موفقیت انجام شد.",
            ]);
    }
}
