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


    public function download($image){
        if(file_exists(public_path("/configs/$image"))){
            $file = public_path("/configs/$image");
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        }

        abort(404);
    }
}
