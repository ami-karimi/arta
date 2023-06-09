<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Ras;
use App\Models\Groups;

class WireGuardController extends Controller
{
    public function index(){


        return response()->json([
            'status' => true,
            'ras' => Ras::select(['name','id','ipaddress','server_location','unlimited'])->where('is_enabled',1)->get(),
            'groups' => Groups::select(['group_type','name','multi_login','id'])->get(),
            'admins' => User::select('name','id')->where('role','!=','user')->where('is_enabled','1')->get(),
        ]);

    }
}
