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

        return response()->json([
               'data' => Helper::GetReselerGroupList(),
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



        return response()->json([
               'data' => $findGroup,
               'message' => 'بروزرسانی گروه '.$findGroup['name']." با موفقیت انجام شد.",
            ]);
    }
}
