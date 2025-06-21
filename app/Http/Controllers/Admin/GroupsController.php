<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Groups;
use App\Http\Resources\Api\GroupsCollection;
use App\Http\Requests\StoreGroupRequest;
class GroupsController extends Controller
{
    public function index(Request $request){

        return new GroupsCollection(Groups::orderBy('id','DESC')->paginate(10));

    }
    public function create(StoreGroupRequest $request){

        $all = $request->all();

        $all['is_enabled'] = ($request->is_enabled ? 1 : 0);
        Groups::create($all);

        return response()->json(['status' => true,'message' => 'گروه با موفقیت اضافه شد!']);
    }
    public function edit(StoreGroupRequest $request,$id){
        $find = Groups::where('id',$id)->first();
        if(!$find){
            return;
        }
        $req = $request->only(['name','expire_type','expire_value','multi_login','price','price_reseler','group_type','group_volume']);
        $req['is_enabled'] = ($request->is_enabled ? 1 : 0);
        $find->update($req);
        return response()->json(['status' => true,'message' => 'گروه با موفقیت بروزرسانی شد!']);
    }
}
