<?php

namespace App\Utility;

use App\Models\Notifications;

class SendNotificationAdmin
{

   public static $data =  [];
   public static $from = '';

   public static  function send($from,$type,$data = []){
       self::$data = $data;
       self::$from = $from;

       if($type == 'financial_create'){
           self::SendFinancial();
       }
       if($type == 'financial_edit'){
           self::EditFinancial();
       }

       if($type == 'created_cart_agent'){
           self::CreateCard();
       }
       if($type == 'edit_cart_agent'){
           self::EditCard();
       }
      if($type == 'admin_create_card'){
           self::AdminCreateCard();
       }

      if($type == 'admin_enabled_card'){
           self::AdminEnabledCard();
       }
      if($type == 'admin_disabled_card'){
           self::AdminDisabledCard();
       }
      if($type == 'create_financial_admin'){
           self::CreateFinancialAdmin();
       }
      if($type == 'approved_financial_admin'){
           self::ApprovedFinancialAdmin();
       }
      if($type == 'reject_financial_admin'){
           self::RejectFinancialAdmin();
       }

      if($type == 'admin_change_price_factore'){
           self::ChangeFinancialPriceAdmin();
       }
      if($type == 'admin_change_custom_price'){
           self::ChangeCustomPrice();
       }


       if($type == 'user_send_financial'){
           self::UserSendFinancial();
       }

       if($type == 'user_edit_financial'){
           self::UserEditFinancial();
       }
      if($type == 'user_charge_account'){
           self::user_charge_account();
       }
      if($type == 'create_bd_agent'){
           self::create_bd_agent();
       }

   }

   public static function SendFinancial(){
       $content = 'ارسال رسید تراکنش به مبلغ :'.number_format(self::$data['price'])." تومان ";
       Notifications::create([
           'from' => self::$from,
           'for' => 'admin',
           'content' => $content,
       ]);

       return true;
   }
   public static function EditFinancial(){
       $content = 'ویرایش رسید تراکنش به شناسه : '.number_format(self::$data['id']);
       Notifications::create([
           'from' => self::$from,
           'for' => 'admin',
           'content' => $content,
       ]);

       return true;
   }

   public static function CreateCard(){
       $content = 'شماره کارت جدید ثبت شد با شناسه : '.number_format(self::$data['id']);
       Notifications::create([
           'from' => self::$from,
           'for' => 'admin',
           'content' => $content,
       ]);

       return true;
   }

   public static function EditCard(){
       $content = 'شماره کارت ویرایش شد با شناسه : '.number_format(self::$data['id']);
       Notifications::create([
           'from' => self::$from,
           'for' => 'admin',
           'content' => $content,
       ]);

       return true;
   }

   public static function AdminCreateCard(){
       $content = 'شماره حساب توسط مدیر برای شما ایجاد شد';
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }

   public static function AdminEnabledCard(){
       $content = 'شماره حساب شما به شناسه '.self::$data['id']." توسط مدیر فعال شد !";
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function AdminDisabledCard(){
       $content = 'شماره حساب شما به شناسه '.self::$data['id']." توسط مدیر غیرفعال شد !";
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function CreateFinancialAdmin(){
      $content = vsprintf(' یک فاکتور مالی برای شما ثبت شد توسط مدیر به مبلغ %s نوع (%s) با وضعیت (%s) توضیحات : %s',[number_format(self::$data['price'])." تومان",(self::$data['type'] == 'plus' ? 'افزایش' : (self::$data['type'] == 'minus' ? 'کاهش' : 'بدهکاری')),
          (self::$data['approved'] ? 'تایید شده' : 'تایید نشده' ),self::$data['description']]);

       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function ApprovedFinancialAdmin(){
      $content = vsprintf('فاکتور ثبتی به شناسه %s و با مبلغ (%s) تومان توسط مدیر تایید شد توضیحات : (%s) و به مبلغ پنل شما اضافه شد.',[self::$data['id'],number_format(self::$data['price']),self::$data['description']]);

       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function RejectFinancialAdmin(){
      $content = vsprintf('فاکتور ثبتی به شناسه %s و با مبلغ (%s) تومان توسط مدیر رد شد توضیحات : (%s) ',[self::$data['id'],number_format(self::$data['price']),self::$data['description']]);

       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function ChangeFinancialPriceAdmin(){
      $content = vsprintf('فاکتور ثبتی به شناسه %s  مبلغ (%s) تومان به (%s) تومان توسط مدیر تغییر کرد توضیحات : (%s) ',[self::$data['id'],number_format(self::$data['price']),number_format(self::$data['new_price']),self::$data['description']]);

       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function ChangeCustomPrice(){
      $content = vsprintf('قیمت بسته گروه (%s) برای شما توسط مدیر از (%s) تومان  به (%s) تومان تغییر داده شد.',[self::$data['group_name'],number_format(self::$data['price']),number_format(self::$data['new_price'])]);
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function UserSendFinancial(){
      $content = vsprintf('یک رسید پرداختی برای شما ارسال کرد به مبلغ : %s',[number_format(self::$data['price'])]);
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }

   public static function UserEditFinancial(){
      $content = vsprintf(' کاربر رسید پرداختی  به شناسه  (%s) مبلغ : %s را ویرایش کرد.',[self::$data['id'],number_format(self::$data['price'])]);
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function user_charge_account(){
      $content = vsprintf(' کاربر حساب کاربری خود را در  گروه (%s) با مبلغ (%s) تومان شارژ کرد.',[self::$data['group_name'],number_format(self::$data['price'])]);
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
   public static function create_bd_agent(){
      $content = vsprintf(' یک مبلغ بدهی برای %s با مبلغ %s برای تایید پرداختی زیر نماینده برای شما ایجاد شد.',[self::$data['name'],number_format(self::$data['price'])]);
       Notifications::create([
           'from' => auth()->user()->id,
           'for' => self::$data['for'],
           'content' => $content,
       ]);

       return true;
   }
}

