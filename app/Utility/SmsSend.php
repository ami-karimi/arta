<?php

namespace App\Utility;

use App\Models\User;

class SmsSend {
   public $url = 'http://rest.ippanel.com/v1/messages/patterns/send';
   public $from = '+985000404223';
   public $numbers = "";
   public $expire_pattent = '3k78op5nnwox2lb';

   public $user = 'ami-karimi';
   public $pass= 'Amir@###1401';
   public  $pattern_code;
   public  $inputData;

   public function __construct($to)
   {
       $this->numbers = $to;

   }

   public  function SendSmsExpire($left = 1){

       $this->pattern_code = $this->expire_pattent;
       $this->inputData = array('day_left' => $left);
       return $this->Send();
   }


   public  function Send(){
       $url = $this->url;

       $param = array
       (
           'originator'=> $this->from,
           'pattern_code'=> $this->pattern_code,
           'recipient'=> $this->numbers,
           'values'=> $this->inputData,
       );
       $userAgent = sprintf("IPPanel/ApiClient/%s PHP/%s", "2.0.0", phpversion());

       $headers = ['Authorization: AccessKey 6mK68iTpJYGr_38E4atepRBqrEax1sMFWkHWFaDPgAo=',sprintf("User-Agent: %s", $userAgent),'Content-Type: application/json','Accept: application/json'];
       $handler = curl_init($url);
       curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
       curl_setopt($handler, CURLOPT_POSTFIELDS, @json_encode($param));
       curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
       $response2 = curl_exec($handler);

       $response2 = json_decode($response2);
       return $response2;
   }


}
