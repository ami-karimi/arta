<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utility\Ftp;
use App\Utility\Helper;
use App\Models\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{

    public function getSettings(){
        $setting = Settings::get()->toArray();

        return response()->json([
            'status' => true,
            'ftp' => Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'ftp';})),
            'general' => Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'general';})),
            'maintenance' => Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'maintenance';})),
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
        if($request->maintenance){
            foreach ($request->maintenance as $key => $value){
                if($key == 'loading'){
                    continue;
                }
                Settings::updateOrCreate([
                    'key' => $key,
                    'group' => 'maintenance',
                ],[
                    'key' => $key,
                    'group' => 'maintenance',
                    'value' => $value,
                    'type' => 'public'
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
        $allow_General = ['QR_WATRMARK','SITE_TITLE','FAV_ICON','SITE_LOGO'];
        if($request){
            foreach ($request->all() as $key => $value) {
                if (in_array($key, $allow_General)) {

                    if ($key == "FAV_ICON") {
                        if ($request->has('FAV_ICON')) {

                            if ($request->file('FAV_ICON')) {
                                $imageName = "FAV_".time() . '.' . $request->FAV_ICON->extension();
                                $request->FAV_ICON->move(public_path('general'), $imageName);
                                $value = url("/general/$imageName");

                            }

                        }
                    }
                    if ($key == "SITE_LOGO") {
                        if ($request->has('SITE_LOGO')) {

                            if ($request->file('SITE_LOGO')) {
                                $imageName = "SITE_LOGO_".time() . '.' . $request->SITE_LOGO->extension();
                                $request->SITE_LOGO->move(public_path('general'), $imageName);
                                $value = url("/general/$imageName");

                            }

                        }
                    }
                    Settings::updateOrCreate([
                        'key' => $key,
                        'group' => 'general',
                    ], [
                        'key' => $key,
                        'group' => 'general',
                        'value' => $value,
                        'type' => 'public'
                    ]);
                }
            }
        }


        Cache::forget('settings');

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
