<?php

namespace App\Utility;

use Carbon\Carbon;
use App\Models\Financial;
use App\Models\Groups;
use App\Models\ReselerMeta;
use App\Models\UserMetas;
use App\Models\PriceReseler;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Models\Settings;
use Illuminate\Support\Facades\DB;
use App\Models\JobsData;
use App\Models\ShopOrders;
use Morilog\Jalali\Jalalian;


class Helper
{


    public static function generateOrderCode(string $prefix): string
    {
        do {
            $number = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $orderCode = $prefix . $number;
        } while (ShopOrders::where('order_id', $orderCode)->exists());

        return $orderCode;
    }

    public static function generateUsername(string $prefix): string
    {
        do {
            $number = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $username = $prefix . $number;
        } while (User::where('username', $username)->exists());

        return $username;
    }


    public static function toArray($array = [],$keys = 'key' ,$values = 'value') {

        $re = [];
        foreach ($array as $value){
            $re[$value[$keys]] = $value[$values];
        }

        return $re;
    }

    public static function getGroupPriceReseler($type = 'list',$group_id = false,$seller_price = false)
    {
        $metas = UserMetas::select(['key', 'value'])->where('user_id', auth()->user()->id)->get();
        $full_meta = Helper::toArray($metas);


        $group_lists = Groups::get();

        $RsMtFull = false;
        if (auth()->user()->creator) {
            $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', auth()->user()->creator)->get();
            $RsMtFull = Helper::toArray($Reselermetas);
        }


        $group_re = [];

        foreach ($group_lists as $row) {

            if (isset($RsMtFull['disabled_group_' . $row->id])) {
                continue;
            }

            if (isset($full_meta['disabled_group_' . $row->id])) {
                continue;
            }

            $price = $row->price;

            if (isset($RsMtFull['group_price_' . $row->id])) {
                $price = (int)$RsMtFull['group_price_' . $row->id];
            }

            if (isset($full_meta['group_price_' . $row->id])) {
                $price = (int)$full_meta['group_price_' . $row->id];
            }

            $re_price = false;
            if($seller_price){
                $re_price = $row->price_reseler;
                $reseler_p = PriceReseler::where('group_id',$row->id)->where('reseler_id',auth()->user()->creator)->first();
                if($reseler_p){
                    $re_price = $row->price;
                }
            }
            $group_re[] = [
                'id' => $row->id,
                'name' => $row->name,
                'seller_price' => $re_price,
                'selected' => (auth()->user()->group_id === $row->id ? true : false),
                'price' => $price,
                'multi_login' => $row->multi_login,
            ];


        }

        if($type == 'list'){
            return $group_re;
        }


        if($group_id){
            foreach ($group_re as $row){
                if((int) $row['id'] == (int) $group_id){
                    return $row;
                }
            }

            return false;
        }


        return [];

    }


    public static function getMePrice($group_id =  false,$for = false,$un = false,$res = false){

        if(!$group_id){
            return 0;
        }
        $group= Groups::where('id',$group_id)->first();

        if(!$group){
            return false;
        }

        $creator = ($for ? $for : auth()->user()->id);



        /*
        if(!$res) {
            $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key', 'price_for_reseler_' . $group_id . "_for_" . $for)->first();
            if ($Reselermetas) {
                return (int)$Reselermetas->value;
            }

            if($for && $un){
                $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key','price_for_reseler_'.$group_id)->first();
                if($Reselermetas){
                    return (int) $Reselermetas->value;
                }
            }

        }
        */



        $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key','reseler_price_'.$group_id)->first();
        if($Reselermetas){
            return (int) $Reselermetas->value;
        }






        return $group->price_reseler;

    }

    public static function getMeStatus($group_id =  false,$for = false){

        if(!$group_id){
            return 0;
        }
        $group= Groups::where('id',$group_id)->first();

        if(!$group){
            return false;
        }

        $creator = auth()->user()->id;
        if(auth()->user()->creator){
            $creator  = auth()->user()->creator;
        }


        if($for){
            $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key','disabled_group_'.$group_id.'_for_'.$for)->first();
            if($Reselermetas){
                return (boolean) $Reselermetas->value;
            }
        }



        $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key','disabled_group_'.$group_id)->first();
        if($Reselermetas){
            return (boolean) $Reselermetas->value;
        }



        return true;
    }

    public static function GetReselerGroupList($type = 'list',$group_id = false,$for = false)
    {
        $group_lists = Groups::where('is_enabled',1)->get();


        $user = User::where('id',$for)->first();
        $sub_agent = null;
        if($user) {
            $sub_agent = ($user->creator ? $user->creator : $user->id);

        }


        $Reselermetas = ReselerMeta::select(['key', 'value']);
        $Reselermetas->where('reseler_id', ($for ? $sub_agent : auth()->user()->id));
        $Reselermetas =   $Reselermetas->get();


        $RsMtFull = Helper::toArray($Reselermetas);



        $group_re = [];

        foreach ($group_lists as $row) {
            $price = $row->price;
            $reseler_price =  self::getMePrice($row->id,$for,false,true);
            $re_sell_price =  self::getMePrice($row->id,$for,true);
            $enable = true;
            $dis_status = 1;

            if(isset($RsMtFull['reseler_price_' . $row->id])){
                $reseler_price =  $RsMtFull['reseler_price_' . $row->id];
            }


            if (isset($RsMtFull['group_price_' . $row->id])) {
                $price = (int)$RsMtFull['group_price_' . $row->id];
            }


            if (isset($RsMtFull['price_for_reseler_' . $row->id])) {
                $re_sell_price = (int)$RsMtFull['price_for_reseler_' . $row->id];
            }


            if(isset($RsMtFull['disabled_group_' . $row->id])){
                $dis_status = $RsMtFull['disabled_group_' . $row->id];
                if($RsMtFull['disabled_group_' . $row->id] == "1"){
                    $enable = true;
                }else{
                    $enable = false;
                }

            }

            if($for){
                if (isset($RsMtFull['price_for_reseler_' . $row->id."_for_".$for])) {
                    $re_sell_price = (int)$RsMtFull['price_for_reseler_' . $row->id."_for_".$for];
                }

                if(isset($RsMtFull['disabled_group_' . $row->id."_for_".$for])){
                    $dis_status = $RsMtFull['disabled_group_' . $row->id."_for_".$for];
                    if($RsMtFull['disabled_group_' . $row->id."_for_".$for] == "1"){
                        $enable = true;
                    }else{
                        $enable = false;
                    }
                }

                if($user->creator !== auth()->user()->id  && auth()->user()->creator && $user->creator || auth()->user()->role == 'admin' &&  $user->creator ){
                    $reseler_price  = $re_sell_price;
                }
            }



            $can_see = true;

            if(auth()->user()->creator){
                $can_see = false;
            }





            $group_re[] = [
                'id' => $row->id,
                'name' => $row->name,
                'multi_login' => $row->multi_login,
                'reseler_price' => $reseler_price,
                'price_for_reseler' => ($can_see ? $re_sell_price : 0),
                'cmorgh_price' => $row->price,
                'price' => $price,
                'status' => $enable,
                'status_code' => $dis_status,
            ];


        }

        if($type == 'list'){
            return $group_re;
        }


        if($group_id){
            foreach ($group_re as $row){
                if((int) $row['id'] == (int) $group_id){
                    return $row;
                }
            }

            return false;
        }


        return [];

    }


    public static function getIncome($user_id){
        $minus_income = Financial::where('for',$user_id)->where('approved',1)->whereIn('type',['minus'])->sum('price');
        $icom_user = Financial::where('for',$user_id)->where('approved',1)->whereIn('type',['plus'])->sum('price');
        $incom  = $icom_user - $minus_income;

        return $incom;
    }

    public static function GetSettings(){
        $value = Cache::rememberForever('settings', function () {
            return Settings::get()->toArray();
        });


        return $value;
    }


    public static function s($key){
        $setting = self::GetSettings();
        $key = array_search($key, array_column($setting, 'key'));

        if(!$key){
            return false;
        }

        return $setting[$key]['value'];
    }

    public static function SaveBackUpLog($array,$change){
        foreach ($array as $key => $row){
            if($row['ip'] == $change['ip']){
                if(!isset($change['log'])){
                    $array[$key] = $change;
                }
            }
        }
        $find = Settings::where('key','FTP_backup_server')->first();
        $find->value = json_encode($array);
        $find->save();
    }

    public static function get_backup(){
        $setting = Settings::get()->toArray();
        $backup_servers_l = Helper::toArray(array_filter($setting, function ($item) {
            return $item['group'] == 'ftp_backup_servers';
        }));
        $ftp_system =  Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'ftp';}));
        if(!count($ftp_system)){
            return false;
        }
        if($ftp_system['FTP_enabled'] !== '1'){
            return false;
        }

        $server_lists = json_decode($backup_servers_l['FTP_backup_server'],true);
        $count = 0;
        $count_server = count($server_lists);
        while ($count < $count_server){

               $server = $server_lists[$count];
                $count++;
                if($server['status_backup'] == "true"){

                    if($server['type'] === "mikrotik"){

                        // Save Mikrotik BackUp File
                        $API = new Mikrotik((object)[
                            'l2tp_address' => $server['mikrotik_domain'],
                            'mikrotik_port' => $server['mikrotik_port'],
                            'username' => $server['username'],
                            'password' => $server['password'],
                        ]);
                        if($API->connect()['ok']){
                            $filename = "ROS-".str_replace('.','_',$server['ip']).date('y-m-d_H-i');
                            $re = $API->bs_mkt_rest_api_post('/system/backup/save',array(
                                'name' => $filename
                            ));
                            // If Save OK
                            if($re['ok']){
                                $server['last_get_backup'] = date('Y-m-d H:i:s');
                                $server['last_backup_name'] = $filename.".backup";
                                $server['wait_download'] = "1";

                                // Run Save To Internal Strong
                                $ftp = new Ftp([
                                    'ip' => $server['ip'],
                                    'port' => $server['port'],
                                    'username' => $server['username'],
                                    'password' => $server['password'],
                                ]);

                                if($ftp->test_connection()) {
                                    $saved_file = $ftp->SaveFile($server['last_backup_name']);
                                    if($saved_file){
                                        $server['wait_download'] = "0";
                                        $server['saved_backup'] = 1;

                                        // Upload BackUp To Server

                                        $status = self::uploadBackupToServer($ftp_system,$server);
                                        if($status){
                                            $filename=  $server['last_backup_name'];
                                            $server['last_upload_server'] = date('Y-m-d H:i:s');
                                            $server['saved_backup'] = 0;
                                        }
                                    }
                                }


                            }
                        }
                    }


                    Helper::SaveBackUpLog($server_lists,$server);

                }

        }
    }

    public static function uploadBackupToServer($ftp_system,$server){
        $ftp = new Ftp([
            'ip' => $ftp_system['FTP_ip'],
            'port' => $ftp_system['FTP_port'],
            'username' => $ftp_system['FTP_username'],
            'password' => $ftp_system['FTP_password'],
        ]);
        if(!$ftp->test_connection()) {
           return false;
        }
        $upload =  $ftp->uploadFileToBackUp($server['last_backup_name'],$server['ip']);
        if($upload){
            return true;
        }

        return false;
    }

    public static function get_db_backup(){
        $setting = Settings::get()->toArray();

        $ftp_system =  Helper::toArray(array_filter($setting,function($item){ return $item['group'] == 'ftp';}));
        if(!count($ftp_system)){
            return false;
        }
        if($ftp_system['FTP_enabled'] !== '1'){
            return false;
        }

        $filename = "backupDB-" . date('Y-m-d_H_i') . ".gz";
        $save_path = public_path('backups/') . $filename;
        $command = "mysqldump --user=" . env('DB_USERNAME') ." --password=" . env('DB_PASSWORD')
            . " --host=" . env('DB_HOST') . " " . env('DB_DATABASE')
            ."  --ignore-table=".env('DB_DATABASE').".radpostauth --ignore-table=".env('DB_DATABASE').".radacct". "  | gzip > " . $save_path;

        $returnVar = NULL;
        $output  = NULL;

        exec($command, $output, $returnVar);


        self::uploadBackupToServer($ftp_system,[
            'last_backup_name' => $filename,
            'ip' => 'DB',
        ]);


        return true;
    }

    public static function get_expired_wg(){

        $expiredGrouped = DB::table('users')
            ->join('wireguard_users', 'users.id', '=', 'wireguard_users.user_id')
            ->where('users.expire_date', '<=', now())
            ->where('users.expired', '=', 0)
            ->select(
                'wireguard_users.server_id',
                DB::raw("GROUP_CONCAT(CONCAT(users.id, ':', wireguard_users.public_key)) as user_data"),
                DB::raw('count(*) as total')
            )
            ->groupBy('wireguard_users.server_id')
            ->get()
            ->map(function ($row) {
                return [
                    'server_id' => $row->server_id,
                    'count' => $row->total,
                    'user_data' => array_map(function ($item) {
                        [$userId, $publicKey] = explode(':', $item);
                        return [
                            'user_id' => $userId,
                            'public_key' => $publicKey,
                        ];
                    }, explode(',', $row->user_data)),
                ];
            });



        return $expiredGrouped;
    }


    public static function create_job($job_key = '',$job_target = 0,$status = 'pending',$result = null,$done_time = null){
        $createJob = new JobsData();
        $createJob->job_key = $job_key;
        $createJob->job_target = $job_target;
        $createJob->status = $status;
        $createJob->result = $result;
        if($done_time){
            $createJob->done_time = $done_time;
        }
         $createJob->save();
        return $createJob;
    }

    public static function check_expired($time){
        if (Carbon::now()->greaterThanOrEqualTo($time)) {
            return false;
        }


        return Jalalian::forge($time)->ago();
    }
}



