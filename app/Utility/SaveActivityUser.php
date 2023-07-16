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
           self::change_group();

       }
       if($type == 'change_owner'){
           self::ChangeOwner();

       }
       if($type == 'active_status'){
           self::ChangeStatusAccount();
       }
       if($type == 'create'){
           self::create();

       }
       if($type == 're_charge'){
           self::re_charge();

       }
       if($type == 'change_expire'){
           self::ChangeExpireRelative();
       }
       if($type == 'buy_new_volume'){
           self::buy_new_volume();
       }
       if($type == 'delete_session'){
       }
       if($type == 'change_username'){
           self::ChangeUsername();
       }
       if($type == 'change_password'){
           self::ChangePassword();
       }
       if($type == 'change_multi_login'){
           self::change_multi_login();
       }

       if($type == 'change_group_user'){
           self::change_group_user();
       }
       if($type == 'user_recharge_account'){
           self::user_recharge_account();
       }
       if($type == 'change_user_protocol'){
           self::change_user_protocol();
       }
       if($type == 'change_user_port'){
           self::change_user_port();
       }
       if($type == 'change_user_transmission'){
           self::change_user_transmission();
       }
       if($type == 'change_user_location'){
           self::change_user_location();
       }
       if($type == 'remark_v2ray'){
           self::remark_v2ray();
       }
       if($type == 'by_volume_v2ray'){
           self::by_volume_v2ray();
       }
       if($type == 'change_wg_server'){
           self::change_wg_server();
       }
       if($type == 'add_left_day'){
           self::add_left_day();
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
   public static function ChangeExpireRelative(){
       $content = vsprintf('انقضا کاربر بصورت دسترسی روی %s با مقدار %s تنظیم شد',[self::$data['type'],self::$data['value']]);
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function ChangeUsername(){
       $content = 'نام کاربری از '.self::$data['last']." به ".self::$data['new']."  تغییر کرد.";
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_group(){
       $content = ' کاربر از گروه '."(".self::$data['last'].")"." به "."(".self::$data['new'].")"."  تغییر کرد.";
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function ChangeOwner(){
       $content = ' کاربر از ایجاد کننده '."(".self::$data['last'].")"." به "."(".self::$data['new'].")"."  تغییر کرد.";
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_multi_login(){
       $content = ' کاربر از تعداد مجاز '."(".self::$data['last'].")"." به "."(".self::$data['new'].")"."  تغییر کرد.";
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function re_charge(){
       $content = 'اکانت شارژ شد!';
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function create(){
       $content = 'اکانت ایجاد شد!';
       Activitys::create([
           'by' => self::$by,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }

   public static function change_group_user(){
       $content = vsprintf('گروه کاربری کاربر از (%s) به (%s) تغییر کرد.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_user_protocol(){
       $content = vsprintf('پرتکل کاربر  از (%s) به (%s) تغییر کرد.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_user_port(){
       $content = vsprintf('پرت کاربر  از (%s) به (%s) تغییر کرد.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_user_transmission(){
       $content = vsprintf('transmission کاربر  از (%s) به (%s) تغییر کرد.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_user_location(){
       $content = vsprintf('لوکیشن کاربر  از (%s) به (%s) تغییر کرد.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function remark_v2ray(){
       $content = vsprintf('ریمارک کاربر  از (%s) به (%s) تغییر کرد.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function user_recharge_account(){
       $content = vsprintf('حساب کاربری شارژ شد!',[]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }

   public static function by_volume_v2ray(){
       $content = vsprintf('مقدار حجم %s خریداری شد. مانده قبلی: %s',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function buy_new_volume(){
       $content = vsprintf('مقدار حجم %s گیگ اضافه خریداری شد. مانده قبلی: %s گیگ',[self::$data['new'],self::$data['last']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function change_wg_server(){
       $content = vsprintf('کانفیگ کاربر از سرور (%s) حذف شد و بر روی سرور (%s) قرار گرفت.',[self::$data['last'],self::$data['new']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }
   public static function add_left_day(){
       $content = vsprintf('مقدار %s روز باقیمانده به دور بعد انتقال یافت.',[self::$data['day']]);
       Activitys::create([
           'by' => auth()->user()->id,
           'user_id' => self::$user_id,
           'content' => $content,
       ]);
   }

}
