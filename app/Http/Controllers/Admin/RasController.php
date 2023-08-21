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
        $all = $request->all();
        $all['is_enabled'] = ( $request->is_enabled == 'true' ? 1 : 0);
        $all['unlimited'] = ( $request->unlimited  == 'true' ? 1 : 0);
        $all['in_app'] = ( $request->in_app == 'true' ? 1 : 0);
        $all['config'] =   ($request->config ? base64_encode($request->config) : '');

        $flag = null;
        if($request->has('flag')){
            if($request->file('flag')){
                $imageName = time().'.'.$request->flag->extension();
                $flag =  $request->flag->move(public_path('attachment/flag'), $imageName);
            }

            $all['flag'] = public_path('attachment/flag/'.$imageName);
        }


        Ras::create($all);

        return response()->json(['status' => true,'message' => 'سرور با موفقیت اضافه شد!']);
    }

    public function edit(EditRasRequest $request,$id){
        $find = Ras::where('id',$id)->first();
        if(!$find){
            return;
        }
        $req = $request->only(['name','secret','flag','config','ipaddress','in_app','unlimited','is_enabled','l2tp_address','server_location','password_v2ray','port_v2ray','username_v2ray','cdn_address_v2ray','server_location']);
        $req['is_enabled'] =( $request->is_enabled  == 'true' ? 1 : 0);
        $req['unlimited'] = ( $request->unlimited  == 'true' ? 1 : 0);
        $req['in_app'] = (  $request->in_app  == 'true' ? 1 : 0);
        $req['config'] =   ($request->config ? base64_encode($request->config) : '');
        $flag = null;
        if($request->has('flag')){
            if($request->file('flag')){
                $imageName = time().'.'.$request->flag->extension();
                $flag =  $request->flag->move(public_path('attachment/flag'), $imageName);
            }

            $req['flag'] = url('attachment/flag/'.$imageName);
        }


        $find->update($req);
        return response()->json(['status' => true,'message' => 'سرور با موفقیت بروزرسانی شد!']);
    }

}
