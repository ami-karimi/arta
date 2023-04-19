<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Api\AdminCollection;
use App\Http\Resources\Api\UserCollection;
use App\Http\Resources\Api\AgentDetailResource;
use App\Models\User;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\EitAdminRequest;
use Illuminate\Support\Facades\Hash;
use Morilog\Jalali\Jalalian;

class AdminsController extends Controller
{
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


}
