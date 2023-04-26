<?php

namespace App\Utility;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;


class Ping
{

   public static $ip =  '';

   public static  function send($ip){
      self::$ip = $ip;

      return self::check();
   }

   public static function check(){
      $client = new Client([
           'timeout' => 5,
           'allow_redirects' => false,
       ]);

       try {
           $response = $client->get(self::$ip);
           return $response->getStatusCode();
       } catch(ClientException $e) {
           $response = $e->getResponse();
           return $response->getStatusCode();
       } catch(ConnectException $e) {
           return $e->getCode();
       } catch(ServerException $e) {
           return $e->getCode();
       }

       return false;
   }
}
