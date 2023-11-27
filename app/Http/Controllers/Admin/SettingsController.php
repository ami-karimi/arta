<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utility\Ftp;
use App\Utility\Helper;
use App\Models\Settings;

class SettingsController extends Controller
{

    public function getSettings(){
        $setting = Settings::get()->toArray();

        return response()->json([
            'status' => true,
            'ftp' => Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'ftp';})),
            'FTP_backupservers' => Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'ftp_backup_servers';})),
        ]);
    }

    public function save_setting(Request $request){
        if($request->ftp){
            foreach ($request->ftp as $key => $value){
                Settings::updateOrCreate([
                    'key' => $key,
                    'group' => 'ftp',
                ],[
                    'key' => $key,
                    'group' => 'ftp',
                    'value' => $value,
                    'type' => 'private'
                ]);
            }
        }

        if($request->ftp_servers){
            Settings::updateOrCreate([
                'key' => 'FTP_backup_server',
                'group' => 'ftp_backup_servers',
            ],[
                'key' => 'FTP_backup_server',
                'group' => 'ftp_backup_servers',
                'value' => json_encode($request->ftp_servers),
                'type' => 'private'
            ]);
        }
    }
    public function test_ftp(Request $request){
        $ftp = new Ftp([
            'ip' => $request->FTP_ip,
            'port' => $request->FTP_port,
            'username' => $request->FTP_username,
            'password' => $request->FTP_password,
        ]);

        return response()->json([
            'status' => true,
            'active' => $ftp->test_connection()
        ]);
    }
}
