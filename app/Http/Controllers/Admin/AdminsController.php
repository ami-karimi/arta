<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Financial;
use App\Models\RadAcct;
use App\Models\UserGraph;
use Illuminate\Http\Request;
use App\Http\Resources\Api\AdminCollection;
use App\Http\Resources\Api\UserCollection;
use App\Http\Resources\Api\AgentDetailResource;
use App\Models\User;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\EitAdminRequest;
use Illuminate\Support\Facades\Hash;
use Morilog\Jalali\Jalalian;
use App\Utility\V2rayApi;
use App\Http\Resources\Api\V2rayServersStatusCollection;
use App\Models\Ras;
use Carbon\Carbon;
use App\Utility\Mikrotik;

class AdminsController extends Controller
{

    public function formatBytes(int $size,int $format = 2, int $precision = 2) : string
    {
        $base = log($size, 1024);

        if($format == 1) {
            $suffixes = ['بایت', 'کلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت']; # Persian
        } elseif ($format == 2) {
            $suffixes = ["B", "KB", "MB", "GB", "TB"];
        } else {
            $suffixes = ['B', 'K', 'M', 'G', 'T'];
        }

        if($size <= 0) return "0 ".$suffixes[1];

        $result = pow(1024, $base - floor($base));
        $result = round($result, $precision);
        $suffixes = $suffixes[floor($base)];

        return $result ." ". $suffixes;
    }


    public function getDashboard(Request $request){
        $total_month = 0;
        $total_last_month = 0;
        $total_day = 0;
        $total_online = 0;

        /*
        $API        = new Mikrotik();
        $API->debug = false;
        $API->connect('s1.arta20.xyz', 'admin', 'Amir@###1401');
        $API->write('/interface/getall');

        $READ = $API->read(false);
        $ARRAY = $API->parseResponse($READ);

        $API->disconnect();
        */
        $AllOnlineCount = RadAcct::where('acctstoptime',NULL)->get()->count();


        $total_month = Financial::whereIn('type',['plus','minus_amn'])->where('approved',1)->whereMonth('created_at', Carbon::now()->month)->get()->sum('price');
        $total_day = Financial::whereIn('type',['plus','minus_amn'])->where('approved',1)->whereDay('created_at', Carbon::now()->day)->get()->sum('price');



         /*
        $chart_data = [];
        $chart_data[] = [
             'label' => 'دانلود',
             'backgroundColor' => '#000eff',
             'data' => [],
            ];

        $chart_data[] = [
            'label' => 'آپلود',
            'backgroundColor' => '#ffd500',
            'data' => [],
        ];


        $end = 6;
        if(!$request->chart_type || $request->chart_type == 'week') {
            $start_day = Jalalian::now()->getFirstDayOfWeek();
            $start_day_G = Jalalian::now()->getFirstDayOfWeek()->toCarbon();
        }elseif($request->chart_type == 'month'){
            $start_day = Jalalian::now()->getFirstDayOfMonth();
            $start_day_G = Jalalian::now()->getFirstDayOfMonth()->toCarbon();
            $end = Jalalian::now()->getMonthDays();
        }

        $Gdays = [];

        $Gdays[] = $start_day_G->format('Y-m-d');
        for ($i=0; $i < $end; $i++) {
           $Gdays[] =  $start_day_G->addDays(1)->format('Y-m-d');
            $Jdays[] =  Jalalian::forge($start_day->toCarbon())->addDays($i)->format('Y-m-d');
        }


        $sumAllDownload = 0;
        $sumAllUpload = 0;
        $sumAllDuplex = 0;
        foreach ($Gdays as $date){
            $data = UserGraph::where('date',$date)->get();
            $sumUpload = $data->sum('tx');
            $sumDownload = $data->sum('rx');
            $chart_data[0]['data'][] = $sumDownload;
            $chart_data[1]['data'][] = $sumUpload;

            $sumAllDownload += $sumDownload;
            $sumAllUpload += $sumUpload;
            $sumAllDuplex += $sumDownload + $sumUpload;
        }


        $cl_us = [];

        $usageUser = UserGraph::selectRaw('SUM(total) as total_usage,user_id')->whereHas('user',function($query) {
            return $query->where('is_enabled',1);
        })->groupBy('user_id')->orderBy('total_usage','DESC')->limit(40)->get();


        foreach ($usageUser as $row){
            $user = User::where('id',$row->user_id)->first();
            if($user) {
                $cl_us[] = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'sum' => $this->formatBytes($row->total_usage)
                ];
           }
        }

        $userEndBandwidth = UserGraph::selectRaw('CAST(SUM(total) as  UNSIGNED) as total_usage,total,user_id')->groupBy('user_id')->EndBandwidth()->orderBy('total_usage','DESC')->limit(40)->get();

        $EndBandwithList = [];
        foreach ($userEndBandwidth as $row){
            $user = User::where('id',$row->user_id)->first();
            if($user) {
                $EndBandwithList[] = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'sum' => $this->formatBytes($row->total_usage),
                    'max_usage' => $this->formatBytes($user->max_usage),
                    'group' => ($user->group ? $user->group->name : '---')
                ];
            }
        }

        */

        return response()->json([
           //'endBandwidth' => $EndBandwithList,
           'total_month'  => $total_month,
           'total_day'  => $total_day,
           'total_last_month'  => 0,
           'total_online'  => $AllOnlineCount,
           //'data' => $Jdays,
          // 'chart_data' => $chart_data,
          //  'sum_download' => $sumAllDownload,
           // 'sum_upload' => $sumAllUpload,
           // 'sum_total' => $sumAllDuplex,
            //'cl_us' => $cl_us,
        ]);
    }

    public function index(Request $request){
        $list = User::where('role','!=','user');
        if($request->SearchText){
            $list->where('name', 'LIKE', "%$request->SearchText%")
                ->orWhere('email', 'LIKE', "%$request->SearchText%");
        }

        return new AdminCollection($list->orderBy('id','DESC')->paginate(20));
    }
    public function view($id){
        $find = User::where('id',$id)->where('role','agent')->first();
        if(!$find){
            return  response()->json([
                'status' => false,
                'message' => 'حساب کاربری یافت نشد!'
            ],403);
        }

        return new AgentDetailResource($find);
    }
    public function create(CreateAdminRequest $request){

        $reqall = $request->all();
        $reqall['is_enabled'] = ($request->is_enabled ? 1 : 0);
        $reqall['password'] = Hash::make($request->password);
        User::create($reqall);

        return response()->json([
            'status' => true,
            'message' => 'حساب کاربری با موفقیت ایجاد شد'
        ]);
    }
    public function edit(EitAdminRequest $request,$id){
        $find = User::where('id',$id)->where('role','!=','user')->first();
        if(!$find){
            return  response()->json([
                'status' => false,
                'message' => 'حساب کاربری یافت نشد!'
            ],403);
        }

        if($request->change_password){
            if(!$request->password){
                return  response()->json([
                    'status' => false,
                    'message' => 'لطفا کلمه عبور را وارد نمایید!'
                ],403);
            }
            if(strlen($request->password) < 6){
                return  response()->json([
                    'status' => false,
                    'message' => 'کلمه عبور حداقل میتواند 6 کارکتر باشد!'
                ],403);
            }
        }
        $enabled = 1;
        if(!$request->is_enabled){
            $enabled = 0;
        }
        $find->update($request->only(['name','email','role']));
        $find->is_enabled = $enabled;
        if($request->change_password){
            $find->password = Hash::make($request->password);
        }

        $find->save();

        return response()->json([
            'status' => true,
            'message' => 'حساب کاربری با موفقیت بروزرسانی شد'
        ]);
    }
    public function GetRealV2rayServerStatus(Request $request){
        $gets = Ras::where('server_type','v2ray')->where('is_enabled',1)->paginate(5);

        return new V2rayServersStatusCollection($gets);
    }

}
