<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Resources\Api\UserCollection;
use App\Models\User;
use App\Models\Groups;
use Illuminate\Http\Request;
use App\Models\AcctSaved;
use App\Http\Requests\StoreSingleUserRequest;
class UserController extends Controller
{
    public function index(){

        return new UserCollection(User::where('role','user')->orderBy('id','DESC')->paginate(10));
    }
    public function create(StoreSingleUserRequest $request){


        $req_all = $request->all();
        AcctSaved::create($request->only(['username','password','creator']));

        $findGroup = Groups::where('id',$request->group_id)->first();
        if($findGroup->expire_type !== 'no_expire'){
            if($findGroup->expire_type == 'minutes'){
                $req_all['exp_val_minute'] = $findGroup->expire_value;
            }elseif($findGroup->expire_type == 'month'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 43800);
            }elseif($findGroup->expire_type == 'days'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 1440);
            }elseif($findGroup->expire_type == 'hours'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 60);
            }elseif($findGroup->expire_type == 'year'){
                $req_all['exp_val_minute'] = floor($findGroup->expire_value * 525600);
            }
        }

        $req_all['multi_login'] = $findGroup->multi_login;
        $req_all['expire_value'] = $findGroup->expire_value;
        $req_all['expire_type'] = $findGroup->expire_type;
        $req_all['expire_set'] = 0;


        User::create($req_all);

        return response()->json(['status' => true,'message' => 'کاربر با موفقیت اضافه شد!']);
    }
    public function edit(StoreGroupRequest $request,$id){
        $find = User::where('id',$id)->first();
        if(!$find){
            return;
        }
        $find->update($request->only(['name','expire_type','expire_value','multi_login']));
        return response()->json(['status' => true,'message' => 'گروه با موفقیت بروزرسانی شد!']);
    }
}
