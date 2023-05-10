<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Financial;
use App\Models\RadAcct;
use Illuminate\Http\Request;
use App\Http\Resources\Api\AdminCollection;
use App\Http\Resources\Api\UserCollection;
use App\Http\Resources\Api\AgentDetailResource;
use App\Models\User;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\EitAdminRequest;
use Illuminate\Support\Facades\Hash;
use Morilog\Jalali\Jalalian;
use App\Utility\V2rayApi;
use App\Http\Resources\Api\V2rayServersStatusCollection;
use App\Models\Ras;
use Carbon\Carbon;

class AdminsController extends Controller
{

    public function getDashboard(){
        $total_month = 0;
        $total_last_month = 0;
        $total_day = 0;
        $total_online = 0;


        $AllOnlineCount = RadAcct::where('acctstoptime',NULL)->get()->count();

        $total_month = Financial::whereIn('type',['plus','minus_amn'])->where('approved',1)->whereMonth('created_at', Carbon::now()->month)->get()->sum('price');
        $total_day = Financial::whereIn('type',['plus','minus_amn'])->where('approved',1)->whereDay('created_at', Carbon::now()->day)->get()->sum('price');



        return response()->json([
           'total_month'  => $total_month,
           'total_day'  => $total_day,
           'total_last_month'  => 0,
           'total_online'  => $AllOnlineCount,
        ]);
    }

    public function index(Request $request){
        $list = User::where('role','!=','user');
        if($request->SearchText){
            $list->where('name', 'LIKE', "%$request->SearchText%")
                ->orWhere('email', 'LIKE', "%$request->SearchText%");
        }

        return new AdminCollection($list->orderBy('id','DESC')->paginate(20));
    }
    public function view($id){
        $find = User::where('id',$id)->where('role','agent')->first();
        if(!$find){
            return  response()->json([
                'status' => false,
                'message' => 'حساب کاربری یافت نشد!'
            ],403);
        }

        return new AgentDetailResource($find);
    }
    public function create(CreateAdminRequest $request){

        $reqall = $request->all();
        $reqall['is_enabled'] = ($request->is_enabled ? 1 : 0);
        $reqall['password'] = Hash::make($request->password);
        User::create($reqall);

        return response()->json([
            'status' => true,
            'message' => 'حساب کاربری با موفقیت ایجاد شد'
        ]);
    }
    public function edit(EitAdminRequest $request,$id){
        $find = User::where('id',$id)->where('role','!=','user')->first();
        if(!$find){
            return  response()->json([
                'status' => false,
                'message' => 'حساب کاربری یافت نشد!'
            ],403);
        }

        if($request->change_password){
            if(!$request->password){
                return  response()->json([
                    'status' => false,
                    'message' => 'لطفا کلمه عبور را وارد نمایید!'
                ],403);
            }
            if(strlen($request->password) < 6){
                return  response()->json([
                    'status' => false,
                    'message' => 'کلمه عبور حداقل میتواند 6 کارکتر باشد!'
                ],403);
            }
        }
        $enabled = 1;
        if(!$request->is_enabled){
            $enabled = 0;
        }
        $find->update($request->only(['name','email','role']));
        $find->is_enabled = $enabled;
        if($request->change_password){
            $find->password = Hash::make($request->password);
        }

        $find->save();

        return response()->json([
            'status' => true,
            'message' => 'حساب کاربری با موفقیت بروزرسانی شد'
        ]);
    }
    public function GetRealV2rayServerStatus(Request $request){
        $gets = Ras::where('server_type','v2ray')->where('is_enabled',1)->paginate(5);

        return new V2rayServersStatusCollection($gets);
    }

}
