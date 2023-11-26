<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WireGuardUsers;
use Illuminate\Http\Request;
use App\Models\Ras;
use App\Models\Groups;
use App\Utility\WireGuard;
use App\Utility\SaveActivityUser;

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

    public function update($id,Request $request){
        $find = WireGuardUsers::where('id',$id)->first();
        if(!$find){
            return response()->json(['status' => false,'message' => 'کانفیگ یافت نشد!'],403);
        }
        if(!$find->user){
            return response()->json(['status' => false,'message' => 'کاربر یافت نشد!'],403);
        }

        $server_id = $find->server_id;
        $public_key = $find->public_key;
        // Change Server
        if($find->server_id !== $request->server_id){
            $removeLastConfig = new WireGuard($find->server_id,'user');
            $removeLastConfig->removeConfig($find->public_key);

            SaveActivityUser::send($find->user->id,auth()->user()->id,'change_wg_server',['last'=> $find->server->name,'new' => Ras::where('id',$request->server_id)->first()->name ]);

            $createNew =  new WireGuard($request->server_id,$find->user->username);
            $user_wi = $createNew->run();
            if($user_wi['status']) {
                $server_id = $request->server_id;
                $find->profile_name = $user_wi['config_file'];
                $find->user_id = $find->user->id;
                $find->server_id = $server_id;
                $find->public_key = $user_wi['client_public_key'];
                $find->client_private_key = $user_wi['client_private_key'];
                $public_key = $user_wi['client_public_key'];
                $find->user_ip = $user_wi['ip_address'];
                $find->save();
                exec('qrencode -t png -o /var/www/html/arta/public/configs/'.$user_wi['config_file'].".png -r /var/www/html/arta/public/configs/".$user_wi['config_file'].".conf");
            }
            $find->server_id = $request->server_id;

        }

        // Disable Or Enable User
        if($find->is_enabled !== (!$request->is_disabled  ? 1 : 0)) {
            $find->is_enabled = (!$request->is_disabled  ? 1 : 0);
            SaveActivityUser::send($find->user->id,auth()->user()->id,'active_status',['status' => (!$request->is_disabled  ? 1 : 0)]);
            $wireGuard = new WireGuard($server_id,$find->profile_name);
            $wireGuard->ChangeConfigStatus($public_key, ($request->is_disabled  ? 0 : 1));
        }

        $find->save();
        return response()->json(['status' => false,'message' => 'کانفیگ با موفقیت ویرایش شد!']);

    }
    public function delete($id){
        $find = WireGuardUsers::where('id',$id)->first();
        if(!$find){
            return response()->json(['status' => false,'message' => 'کانفیگ یافت نشد!'],403);
        }
        if(!$find->user){
            return response()->json(['status' => false,'message' => 'کاربر یافت نشد!'],403);
        }
        $removeLastConfig = new WireGuard($find->server_id,'user');
        $removeLastConfig->removeConfig($find->public_key);
        $find->delete();
        return  response()->json(['status' => true,'message' => 'DeletedConfig']);
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
