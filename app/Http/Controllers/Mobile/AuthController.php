<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Utility\Tokens;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class AuthController extends Controller
{
    public function sign_in(Request $request){
        if($request->username == ""){
            return response()->json(['error' => true, 'result' => 'لطفا نام کاربری را وارد نمایید!'],403);
        }
        if($request->password == ""){
            return response()->json(['error' => true, 'result' => 'لطفا کلمه عبور را وارد نمایید!'],403);
        }

        $findUser = User::where('username',$request->username)->where('password',$request->password)->first();
        if($findUser){
            if(!$findUser->is_enabled){
                return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!']);
            }

            $token = new Tokens();
            $ts = $token->CreateToken($findUser->id);
            $expire_date = $findUser->expire_date ;
            if(!$findUser->expire_set){
                $findUser->expire_set = 1;
                $findUser->expire_date = Carbon::now()->addMinutes($findUser->exp_val_minute);
                $expire_date = $findUser->expire_date;
                $findUser->first_login = Carbon::now();
                $findUser->save();
            }

            return  response()->json([
               'error' => false,
               'result' =>  [
                  'token' => $ts->token,
                  'email' => $findUser->username,
                  'password' => $findUser->password,
                  'isPremium' => 0,
                  'subscriptionEndDate' => Jalalian::forge($expire_date)->__toString(),
                  'left_day' => Carbon::now()->diffInDays($expire_date, false),
                 ]
            ]);
        }

        return response()->json(['error' => true, 'result' => 'حساب کابری شما یافت نشد!']);


    }

    public function is_valid_token(Request $request){
        if(!$request->token){
            return response()->json(['error' => true, 'result' => 'توکن یافت نشد'],403);
        }
        $token = new Tokens();
        $check = $token->checkToken($request->token);
        if(!$check){
            return response()->json(['error' => true, 'result' => 'توکن نامعتبر میباشد '],403);
        }
        $findUser = User::where('id',$check->user_id)->first();
        if(!$findUser){
            return response()->json(['error' => true, 'result' => 'کاربر یافت تشد! '],403);
        }
        if(!$findUser->is_enabled){
            return response()->json(['error' => true, 'result' => 'حساب کاربری شما غیر فعال میباشد لطفا با مدیر تماس بگیرید!']);
        }
        $expire_date = $findUser->expire_date ;

        return  response()->json([
            'error' => false,
            'result' =>  [
                'token' => $check->token,
                'email' => $findUser->username,
                'password' => $findUser->password,
                'isPremium' => 0,
                'subscriptionEndDate' => Jalalian::forge($expire_date)->__toString(),
                'left_day' => Carbon::now()->diffInDays($expire_date, false),
            ]
        ]);

    }

    public function get_servers(Request $request){

        $servers =  [];
        $servers[] = [
            'type' => 'openvpn',
            'name' => 'S1',
            'config'=> "
client
dev tun
proto tcp
persist-key
persist-tun
nobind
verb 3
remote s1.arta20.xyz 110
auth SHA1
cipher AES-256-CBC
auth-user-pass
<ca>
-----BEGIN CERTIFICATE-----
MIIDDzCCAfegAwIBAgIIEQM4BZdb5+0wDQYJKoZIhvcNAQELBQAwDTELMAkGA1UE
AwwCQ0EwHhcNMTkwNTI1MDg1NTUzWhcNMjkwNTIyMDg1NTUzWjANMQswCQYDVQQD
DAJDQTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAL/L7tAhIptwKsvB
LtGqxOdP+ToQOXxCXNIU5wUsQqBy+R6R4XHNHB2j2DfxMXzgdlnJr1+9dZCmcR4v
wP00JsQ4lPduEDwECvahaYAYdnWwOskBbbDTVSdTnanV0jx6ecKM7oWdaLisDnyc
m69hY5Cn7SWPYKkspH+7lDDgS2oYh2acLSRn5KQBkrmhsvmhlaFCIHlGhmm3NQBJ
GvlBRuTEGj72iTkjqm/yPdsf22Z+KuVM6oUQ9OdJIE1WS2/2/AEgfFDYPatdtdWg
NWefRzKmNgOiU3K1TzOn2dmEO3U+a2ndwPx944eAVw83yJL1uLTg5Kyd7i/pITY8
YBUqwG0CAwEAAaNzMHEwDwYDVR0TAQH/BAUwAwEB/zAOBgNVHQ8BAf8EBAMCAaYw
HQYDVR0OBBYEFCguAqSxsFq3eeC6wtZUklF5Lo4cMC8GA1UdHwQoMCYwJKAioCCG
Hmh0dHA6Ly9zMS5hcnRhMjAueHl6L2NybC8xLmNybDANBgkqhkiG9w0BAQsFAAOC
AQEAqBYbV9pBIVvzj6CA6uVtzXnFAeLK8wgCK166aFeUsFr1mkZeWqj7qOWeYoeT
125BVgw2unvkVs5RTgU6tJICxvWrofp4F+yD+gBG1TnJDyvfxqdwCUhDX+p7blLN
JMV18fEr33l06PLSDNwygIS9l2MrRQarM0TCJ/emKXVN/A+8AMZUMh7wIJvEAHb4
clr1bJCmlEMVtHL2fbs3w9BV0xUeKHyNFLKFSZtSkvZrhOFuyzvMyV/5vPT0qtNb
OXuHWbImC6NBzcxhePfe56UG9Sb9agveXsUEXVT31NACb0bzNMSwnh4aCOGCTbAp
N6sVJlVv/+nNT3IZ6FDmhOZIJg==
-----END CERTIFICATE-----

</ca>
<cert>
-----BEGIN CERTIFICATE-----
MIIDJDCCAgygAwIBAgIIIL4Q0dyYAXcwDQYJKoZIhvcNAQELBQAwDTELMAkGA1UE
AwwCQ0EwHhcNMTkwNTI1MDg1NjQzWhcNMjkwNTIyMDg1NjQzWjARMQ8wDQYDVQQD
DAZDbGllbnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCmzpMY2OZh
qnT36rFfWWWPDEht6gbEiJU/7gGbeqsGZg+Bhh4x05yrPWMxVzHTxCXfmeKoDHCx
wvaSbvGs9SqjDuDCp5EHk/Lz2cAVYH+xp8qEm+9eKQ9dCmCwg14dn2Uq1e6iKfpX
YZX+5sky0B9gfqJ6sEKL7lCtqLLwTlFE3RfJogqs558VeoMd0rQhgeIeypiXrZqL
G76gGLYDUqysWMpe8+7Ah+Y9+r6uHDGbQ1t/Ffee48IYBKtxnfrE7wam1Xj3BHfm
dC4F28WUWR6Sk84Pb0NH5lk5P2dLEyhyHl8N7022+m6QtY+21+tW3TNlDUBYHM2G
l7sdtvNG1ALPAgMBAAGjgYMwgYAwDwYDVR0TAQH/BAUwAwEB/zAOBgNVHQ8BAf8E
BAMCAbYwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMB0GA1UdDgQWBBRV
mEPYDjYQqmHs9RGpVAauEyYoJDAfBgNVHSMEGDAWgBQoLgKksbBat3ngusLWVJJR
eS6OHDANBgkqhkiG9w0BAQsFAAOCAQEAJWppBxOdGFucXaA2rYPSeO6GTcO2WfGY
VZuSZhGDu58Uohm4Auaabh367hdX6ZYyFuNRQfPS/kKcOp2IPySvivIf5fcIxktD
ja2A6IsjvMtozZDHmZQtwgLgvqpwWPr0kAcQ4V7IesBEOiOw5D1j3gVsDIa1U9nK
RAQjx1GxcCjTFytIF8YOXOzlY+kSEA7ioQpP40UJaHGYRhWBOWrWYEBKzGEh2UCs
jIyU1f3E79TzILJz95sD5UIz7IO03XVpZMqT2SP3ayZY+U1md/3j2oSmlIwcCp/J
VbruJxrpDaDjYjkXvWpaEW3gt0A2Yqdy2DfudrxDTqegOUy20BK0uw==
-----END CERTIFICATE-----

</cert>
<key>
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAps6TGNjmYap09+qxX1lljwxIbeoGxIiVP+4Bm3qrBmYPgYYe
MdOcqz1jMVcx08Ql35niqAxwscL2km7xrPUqow7gwqeRB5Py89nAFWB/safKhJvv
XikPXQpgsINeHZ9lKtXuoin6V2GV/ubJMtAfYH6ierBCi+5Qraiy8E5RRN0XyaIK
rOefFXqDHdK0IYHiHsqYl62aixu+oBi2A1KsrFjKXvPuwIfmPfq+rhwxm0NbfxX3
nuPCGASrcZ36xO8GptV49wR35nQuBdvFlFkekpPOD29DR+ZZOT9nSxMoch5fDe9N
tvpukLWPttfrVt0zZQ1AWBzNhpe7HbbzRtQCzwIDAQABAoIBABSKP2bB3qyMFtco
WSsKkQzqUEjolmjBAM/ceOoyUrj4/FPQtgsgqZwUdRBwUjxnXNqJ0nUrAv2Aqmgh
rTTFA7kMbfTKOXubZkFMwPBg75hqtu9ZXEJWAARO8NULeB1hsU1zBm2FicQUyimX
NZNCOXriXROKfMdKUzjvGwmoOy2lcPfEjKCY2ue7BvGOa5Hg3em71yXWr+9Z16in
10sbly59OMp4EsNmOiG5Sa3ZkFRarbDY8TvnQS5Hmpvc1EiChYINWH3drUZKsp9U
GtU1kdbwDAX6QBCjSQ8brI69QkjJFtCqaNlQZdcSqMGRTWXSrNKuTyxRYZsC79c3
sJfXuoECgYEA3DFp/aa4kDvdtMFC6SwZ/uonQADv+Wp02iqMakupZSWONXpmWaJP
lNimyH6p3zjFkVdnH4XvyGsdQo6F/pwhF++FnelIoTU/L3mgtzNqq4O3+4mTosph
axv5W90+4XsCD80NS/+hEyLsRqSYW0iefFg0WjOTtYjEJp58BopAEiECgYEAwe61
qL6yqI7wqKglCvcKoVSvf98St1UAQRKnQu6TknPO+23ye/J5nhoqCyZFZaywm4m/
p4kLal5FV+aZ2hxHvvzByQ9xAD/lNxOJFpW0bXVz3oNf+LbEYnWxRu1b9Aqbp/UH
Aq04xfg1BS2XVvunQG3BTVj8oRVY6gQI/+lsVu8CgYAsJshe8RAu86IX/WyCPrKT
t7XZEpcLxvnZSRDQu40i1+308S8WqAIXEX4X07YSKVsMMp9d5chXwoqibtuVWw8T
spZzPHSwxnF9/oBoW6n27Dl2+XYd/UCdboWIkwtpwPV/35jb9U0B/k2sOJLIMv58
Zl9Q+uiSTPMv3zV1RkFkoQKBgQCscXmg0ekFTw0Zu2Is7NzL9gSUHKSE1pWCR3bp
YkFgkY+0LODYbBTOjA9kmKROs47az1LXQ1oePDNG5StbMZhucExUX2GoyigkoD9f
EME+L5lXe9RD0SixMFvxaLBCQYiFgbC5JZR9HKbwssiGtQDUnoOrJnyFM/k7JVln
TYVjiwKBgF+VBPOmgpBFXj/tmweyyzkw8SFuF5X4M72sJaGTnhpg3mkqLsntWoF2
hhC55ODZTmu3ApIqM8AyK+OFj0N5BefyYoOLcTQ+ttb2LkaB765JxQBIYJ6NA/pO
HAhnCawZ3hux5U89FGJmTEusXpNqq4XjOvc9gGyuJUwZwgH7X6Iy
-----END RSA PRIVATE KEY-----

</key>",
            'id' => 1,
            'ip' => '185.126.15.66',
            'premium' => false,
            'load' => 100,
            'ping' => 90,
            'synced' => 1,
            'abbreviation' => 'ru',


        ];

        $servers[0]['config'] = base64_encode($servers[0]['config']);
        return response()->json(['error' => false,'result' => $servers
        ]);
    }
}
