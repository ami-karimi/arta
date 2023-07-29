<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgentDetailResource;
use App\Models\Groups;
use App\Models\PriceReseler;
use App\Models\ReselerMeta;
use App\Utility\Helper;
use App\Utility\SendNotificationAdmin;
use Illuminate\Http\Request;
use App\Http\Resources\Api\AgentsCollection;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AgentsController extends Controller
{
    public function index(Request $request){
        $users = User::where('creator',auth()->user()->id)->where('role','agent');


        return new AgentsCollection($users->paginate(10));
    }

    public function create(Request $request){
        if(!$request->name){
            return response()->json(['message' => 'لطفا نام زیر نماینده را وارد نمایید!'],403);
        }
        if(!$request->password){
            return response()->json(['message' => 'لطفا کلمه عبور زیر نماینده را وارد نمایید!'],403);
        }
        if(strlen($request->password) < 4){
            return response()->json(['message' => 'کلمه عبور حداقل 4 کاراکتر میباشد'],403);
        }

        if(!filter_var($request->email, FILTER_VALIDATE_EMAIL)){
            return response()->json(['message' => 'لطفا ایمیل  نماینده را وارد نمایید!'],403);
        }

        $findLast = User::where('email',$request->email)->first();
        if($findLast){
            return response()->json(['message' => 'ایمیل در سیستم موجود میباشد!'],403);
        }

        $user = new User();
        $user->name = $request->name;
        $user->role = 'agent';
        $user->creator = auth()->user()->id;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['message' => 'زیر نماینده با موفقیت ایجاد شد!']);


    }


    public function edit(Request $request,$id){
        $agent = User::where('creator',auth()->user()->id)->where('role','agent')->where('id',$id)->first();
        if(!$agent){
            return response()->json(['message' => 'زیر نماینده یافت نشد!'],403);
        }
        if(!$request->name){
            return response()->json(['message' => 'لطفا نام زیر نماینده را وارد نمایید!'],403);
        }
        if(!filter_var($request->email, FILTER_VALIDATE_EMAIL)){
            return response()->json(['message' => 'لطفا ایمیل  نماینده را وارد نمایید!'],403);
        }
        if($request->change_password){
            if(!$request->password){
                return response()->json(['message' => 'لطفا کلمه عبور زیر نماینده را وارد نمایید!'],403);
            }
            if(strlen($request->password) < 4){
                return response()->json(['message' => 'کلمه عبور حداقل 4 کاراکتر میباشد'],403);
            }
            $agent->password = Hash::make($request->password);
        }

        if($request->email !== $agent->email){
            $find = User::where('email',$request->email)->where('id','!=',$id)->first();
            if($find){
                return response()->json(['message' => ' نماینده ای با این ایمیل موجود است!'],403);
            }
        }
        $agent->name = $request->name;
        $agent->email = $request->email;
        $agent->is_enabled = ($request->is_enabled == true ? 1 : 0);
        $agent->save();

        return response()->json(['message' => 'اطلاعات زیر نماینده با موفقیت بروزرسانی شد!']);

    }
    public function view($id){
        $agent = User::where('creator',auth()->user()->id)->where('role','agent')->where('id',$id)->first();
        if(!$agent){
            return response()->json(['message' => 'زیر نماینده یافت نشد!'],403);
        }



        return  new AgentDetailResource($agent);
    }

    public function save_custom_price($group_id,Request $request){

        $agent = User::where('id',$request->agent_id)->first();
        if(!$agent){
            return response()->json(['message' => 'نماینده مورد نظر یافت نشد!']);

        }


        $findGroup = Helper::GetReselerGroupList('one',$group_id,$agent->id);

        if(!$findGroup){
            return response()->json(['message' => 'گروه مورد نظر یافت نشد!']);
        }

        ReselerMeta::updateOrCreate([
            'reseler_id' => $agent->creator_name->id,
            'key' => 'disabled_group_'.$findGroup['id']."_for_".$request->agent_id,
        ],
            [
                'reseler_id' => $agent->creator_name->id,
                'key' => 'disabled_group_'.$findGroup['id']."_for_".$request->agent_id,
                'value' => $request->item['status'],
            ]);


        ReselerMeta::updateOrCreate([
            'reseler_id' => $agent->creator_name->id,
            'key' => 'price_for_reseler_'.$findGroup['id']."_for_".$request->agent_id,
        ],
            [
                'reseler_id' =>$agent->creator_name->id,
                'key' => 'price_for_reseler_'.$findGroup['id']."_for_".$request->agent_id,
                'value' => $request->item['price_for_reseler'],
            ]);



        return response()->json([
            'data' => $findGroup,
            'message' => 'بروزرسانی گروه '.$findGroup['name']." با موفقیت انجام شد.",
        ]);

        /*
        $find_admin = User::where('id',$id)->where('creator',auth()->user()->id)->first();
        if(!$find_admin){
            return response()->json([
                'status' => false,
                'message'=> 'نماینده یافت نشد!'
            ],403);
        }
        foreach ($request->price_list as $row){
            $findGroup = Groups::where('id',$row['id'])->first();
            if($findGroup){
                if($row['price_for_reseler']){
                    ReselerMeta::updateOrCreate([
                        'reseler_id' => auth()->user()->id,
                        'key' => 'reseler_price_'.$findGroup['id']."_for_".$find_admin->id,
                    ],
                        [
                            'reseler_id' => auth()->user()->id,
                            'key' => 'price_for_reseler_'.$findGroup['id']."_for_".$find_admin->id,
                            'value' => (int) $row['price_for_reseler'],
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
}
