<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Utility\Helper;
use Illuminate\Http\Request;
use App\Utility\V2raySN;
use App\Models\Ras;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\NotoSans;

class V2rayController extends Controller
{
    public function index(){

    }

    public function get_services($server_id){
       $find_ras = Ras::where('id',$server_id)->first();
       if(!$find_ras){
           return response()->json(['status' => false,'message' => 'سرور یافت نشد!'],404);
       }
        $V2ray = new V2raySN([
            'HOST' =>  $find_ras->ipaddress,
            "PORT" =>  $find_ras->port_v2ray,
            "USERNAME" => $find_ras->username_v2ray,
            "PASSWORD"=> $find_ras->password_v2ray,
            "CDN_ADDRESS"=> $find_ras->cdn_address_v2ray,

        ]);
        if(!$V2ray->error['status']){
            $list = $V2ray->InBoandList();
            $re = [];
            foreach ($list['obj'] as $key => $row){
                $re[] = [
                    'id' => $row['id'],
                    'port' => $row['port'],
                    'protocol' => $row['protocol'],
                    'remark' => $row['remark'],
                    'tag' => $row['tag'],
                    'counts' => Helper::ContV2rayUsers($row['clientStats']),
                ];
            }
            return response()->json($re);

        }else{
            return response()->json(['status' => false,'message'=> $V2ray->error['message']],502);
        }

    }

    public function get_qr(Request $request){
        $writer = new PngWriter();

// Create QR code
        $qrCode = Builder::create()
            ->data($request->text)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->labelText(Helper::s('QR_WATRMARK'))
            ->labelFont(new NotoSans(20))
            ->labelAlignment(LabelAlignment::Center)
            ->validateResult(false)
            ->build();

        echo $qrCode->getString();
    }

    public function get_v2ray_server(){

        $servers = Ras::select('id','name')->where('server_type','v2ray')->where('is_enabled',1)->get();

        return response()->json($servers);
    }
}
