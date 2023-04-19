<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Groups;
use App\Http\Resources\Api\GroupsCollection;
use App\Http\Requests\StoreGroupRequest;
class GroupsController extends Controller
{
    public function index(){

        return new GroupsCollection(Groups::orderBy('id','DESC')->paginate(10));

    }
    public function create(StoreGroupRequest $request){
        Groups::create($request->all());

        return response()->json(['status' => true,'message' => 'گروه با موفقیت اضافه شد!']);
    }
    public function edit(StoreGroupRequest $request,$id){
        $find = Groups::where('id',$id)->first();
        if(!$find){
            return;
        }
        $find->update($request->only(['name','expire_type','expire_value','multi_login','price','price_reseler']));
        return response()->json(['status' => true,'message' => 'گروه با موفقیت بروزرسانی شد!']);
    }
}
