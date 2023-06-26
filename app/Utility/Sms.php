<?php
namespace App\Utility;
use Illuminate\Support\Facades\Http;
use Kavenegar;

class Sms {

    public $to;

    public function __construct($phone){
      $this->to = $phone;

    }

    public function SendVerifySms(){

        $createToken = mb_substr(rand(1,99999999999),1,5);
try{
    //Send null for tokens not defined in the template
    //Pass token10 and token20 as parameter 6th and 7th
    $result = Kavenegar::VerifyLookup($this->to,$createToken, null, null, 'elecomp',null,null);
      if($result){

        return ['status' =>false ,'message' => 'کد فعال سازی با موفقیت به شماره '.$this->to.' ارسال شد.'];
       }
    }
     catch(\Kavenegar\Exceptions\ApiException $e){
    // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
        return ['status' =>false ,'message' => $e->errorMessage()];
     }
     catch(\Kavenegar\Exceptions\HttpException $e){
        // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
       return ['status' =>false ,'message' => $e->errorMessage()];
    }


    }
}
