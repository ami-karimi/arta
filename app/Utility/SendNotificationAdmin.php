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
}
