<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ras;
use App\Http\Resources\RasResources;
use App\Http\Requests\StoreRasRequest;
use App\Http\Requests\EditRasRequest;
class RasController extends Controller
{
    public function index(Request $request){
        return new RasResources(Ras::orderBy('id','DESC')->paginate(20));
    }

    public function create(StoreRasRequest $request){
        Ras::create($request->all());

        return response()->json(['status' => true,'message' => 'سرور با موفقیت اضافه شد!']);
    }

    public function edit(EditRasRequest $request,$id){
        $find = Ras::where('id',$id)->first();
        if(!$find){
            return;
        }
        $find->update($request->only(['name','secret','ipaddress','is_enabled']));
        return response()->json(['status' => true,'message' => 'سرور با موفقیت بروزرسانی شد!']);
    }

}
