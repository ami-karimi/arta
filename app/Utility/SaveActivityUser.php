<?php

namespace App\Utility;

use App\Models\Activitys;

class SaveActivityUser
{

   public static $by =  NULL;
   public static $user_id =  NULL;
   public static $data =  [];
   public static $agent_view =  0;
   public static $admin_view =  0;

   public static  function send($user_id,$by,$type,$data = []){
       self::$user_id = $user_id;
       self::$by = $by;
       self::$data = $data;

       if($type == 'change_group'){
       }
       if($type == 'change_owner'){
       }
       if($type == 'active_status'){
           self::ChangeStatusAccount();
       }
       if($type == 'delete'){
       }
       if($type == 'recharge'){
       }
       if($type == 'delete_session'){
       }
       if($type == 'change_password'){
           self::ChangePassword();
       }
   }

   public static function ChangeStatusAccount(){
       $status = (self::$data['status'] == 1 ? '(فعال)' : '(غیرفعال)');
       $content = 'وضعیت اکانت به : '.$status.' تغییر کرد.';
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function ChangePassword(){
       $content = 'کلمه عبور از '.self::$data['last']." به ".self::$data['new']."  تغییر کرد.";
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }

}
