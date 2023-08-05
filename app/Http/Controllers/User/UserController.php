<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RadAuthAcctCollection;
use App\Http\Resources\Api\GetServerCollection;
use App\Models\Financial;
use App\Models\UserGraph;
use App\Utility\SendNotificationAdmin;
use App\Utility\V2rayApi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Ras;
use App\Models\Groups;
use App\Models\RadAcct;
use Morilog\Jalali\Jalalian;
use App\Models\RadPostAuth;
use App\Models\UserMetas;
use App\Models\ReselerMeta;
use App\Utility\Helper;
use App\Utility\SaveActivityUser;


class UserController extends Controller
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

    public function index(){
       $findUser = User::where('id',auth()->user()->id)->first();
       if(!$findUser){
           return response()->json(['status' => false,'message' => 'حساب کاربری یافت نشد!'],403);
       }

       if($findUser->service_group == 'v2ray'){

           $findServer = false;
           $usage = 0;
           $total = 0;
           $left_usage = 0;
           $v2ray_user = [];
           $preg_left = 0;
           $down = 0;
           $up = 0;
           if($findUser->v2ray_server){
               $login_s =new V2rayApi($findUser->v2ray_server->ipaddress,$findUser->v2ray_server->port_v2ray,$findUser->v2ray_server->username_v2ray,$findUser->v2ray_server->password_v2ray);
               if($login_s) {
                   $v2ray_user =  $login_s->list(['port' => (int) $findUser->port_v2ray]);
                   if(count($v2ray_user)) {
                       if (!$findUser->v2ray_id) {
                           $findUser->v2ray_id = $v2ray_user['id'];
                           $findUser->save();
                       }
                       $usage = $login_s->formatBytes($v2ray_user['usage'],2);
                       $total = $login_s->formatBytes($v2ray_user['total'],2);
                       $left_usage = $login_s->formatBytes($v2ray_user['total'] - $v2ray_user['usage']);
                       $preg_left = ($v2ray_user['total'] > 0 ? ($v2ray_user['usage'] * 100 / $v2ray_user['total']) : 0);
                       $preg_left = 100  - $preg_left  ;
                       $down = $login_s->formatBytes($v2ray_user['down'],2);
                       $up = $login_s->formatBytes($v2ray_user['up'],2);
                   }
               }
           }

           $ballance = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','plus')->get()->sum('price');
           $ballance_minus = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','minus')->get()->sum('price');

           $credit = $ballance - $ballance_minus;
           if($credit <= 0){
               $credit = 0;
           }

           $left_usage = 0;
           $up = 0;
           $down = 0;
           $usage = 0;
           $total = 0;





           return  response()->json([
               'status' => true,
               'user' => [
                   'id' => $findUser->id,
                   'server_detial' => ($findUser->v2ray_server ? $findUser->v2ray_server->only(['server_location','ipaddress','cdn_address_v2ray','id']) : false),
                   'left_usage' => $left_usage,
                   'down' => $down,

                   'up' => $up,
                   'preg_left' => $preg_left,
                   'v2ray_user' => $v2ray_user,
                   'usage' => $usage,
                   'total' => $total,
                   'name' => $findUser->name,
                   'v2ray_location' => $findUser->v2ray_location,
                   'v2ray_transmission' => $findUser->v2ray_transmission,
                   'port_v2ray' => $findUser->port_v2ray,
                   'remark_v2ray' => $findUser->remark_v2ray,
                   'protocol_v2ray' => $findUser->protocol_v2ray,
                   'v2ray_id' => $findUser->v2ray_id,
                   'v2ray_u_id' => $findUser->v2ray_u_id,
                   'service_group' => $findUser->service_group,
                   'username' => $findUser->username,
                   'credit' => $credit,
                   'creator' => $findUser->creator,
                   'creator_detial' => ($findUser->creator_name ? ['name' => $findUser->creator_name->name ,'id' =>$findUser->creator_name->id] : [] ) ,
                   'password' => $findUser->password,
                   'group' => ($findUser->group ? $findUser->group->name : '---'),
                   'group_id' => $findUser->group_id,
                   'is_enabled' => $findUser->is_enabled ,
                   'created_at' => Jalalian::forge($findUser->created_at)->__toString(),
               ],
               'groups' => Groups::select('name','id')->get(),
               'credit'  => $findUser->credit,
               'v2ray_servers' => Ras::select(['id','server_type','name','server_location'])->where('server_type','v2ray')->where('is_enabled',1)->get(),
           ]);
       }
       $leftTime = ($findUser->expire_date !== NULL ? Carbon::now()->diffInDays($findUser->expire_date, false) : false);
       $fulldate = 0;
       if($findUser->expire_type == 'month'){
           $fulldate = $findUser->expire_value * 30;
       }
       if($findUser->expire_type == 'days'){
           $fulldate = $findUser->expire_value * 1;
       }

       $preg = 0;
       if($leftTime){
           if($leftTime > 0 && $leftTime > 0 && $fulldate > 0){
               $preg = round(floor(($leftTime * 100 ) /  $fulldate));
           }
       }

       $ballance = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','plus')->get()->sum('price');
       $ballance_minus = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','minus')->get()->sum('price');

       $credit = $ballance - $ballance_minus;
       if($credit <= 0){
           $credit = 0;
       }

       $lastOnline = RadAcct::where('username',$findUser->username)->orderBy('radacctid','DESC')->first();
       $onlineCount = RadAcct::where('username',$findUser->username)->where('acctstoptime',NULL)->count();

        $up = 0;
        $down = 0;
        $usage = 0;
        $left_usage = 0;
        $total = 0;

        if($findUser->group){
            if($findUser->group->group_type === 'volume'){
                $up = $findUser->upload_usage;
                $down = $findUser->download_usage;
                $usage = $findUser->usage;
                $left_usage = $findUser->max_usage - $usage;
                $total = $findUser->max_usage;
            }
        }
        $preg_left = ($total > 0 ? ($usage * 100 / $total) : 0);
        $preg_left = 100  - $preg_left  ;


       return  response()->json([
           'status'=> true,
           'user' =>  [
               'id' => $findUser->id,
               'down' => $down,
               'down_format' => $this->formatBytes($down,2),
               'left_usage' => $left_usage,
               'left_usage_format' =>  $this->formatBytes($left_usage,2),
               'up' => $up,
               'up_format' => $this->formatBytes($up,2),
               'usage' => $usage,
               'usage_format' => $this->formatBytes($usage,2),
               'total' => $total,
               'preg_left_volume' => $preg_left,
               'total_format' => $this->formatBytes($total,2),
               'username' => $findUser->username,
               'service_group' => $findUser->service_group,
               'phonenumber' => $findUser->phonenumber,
               'password' => $findUser->password,
               'name' => $findUser->name,
               'is_enabled'=> $findUser->is_enabled,
               'group_id'=> $findUser->group_id,
               'group' => ($findUser->group ? $findUser->group->name : false),
               'group_type' => ($findUser->group ? $findUser->group->group_type : false),
               'multi_login' => $findUser->multi_login,
               'first_login' =>($findUser->first_login !== NULL ? Jalalian::forge($findUser->first_login)->__toString() : false),
               'account_status' =>  ($findUser->isOnline ? 'online': 'offline'),
               'time_left' => $leftTime,
               'last_online' => ($lastOnline ? Jalalian::forge($lastOnline->acctupdatetime)->__toString() : false),
               'preg_left' => $preg,
               'expire_set' => $findUser->expire_set,
               'credit' => $credit,
               'expire_date' => ($findUser->expire_date !== NULL ? Jalalian::forge($findUser->expire_date)->__toString() : false),
               'last_connect' => ($lastOnline !== NULL ? ($lastOnline->servername ? $lastOnline->servername->name : '---') : '---'),
               'online_count' => $onlineCount,
           ]
       ]);
   }
   public function edit_password(Request $request){
       if(!$request->password){
           return response()->json([
               'status' => false,
               'message' => 'لطفا کلمه عبور جدید را وارد نمایید!'
           ],403);
       }
       if(strlen($request->password) < 4){
           return response()->json([
               'status' => false,
               'message' => 'کلمه عبور بایستی حداقل 4 کاراکتر باشد!'
           ],403);
       }
       if($request->password !== $request->password_confirm ){
           return response()->json([
               'status' => false,
               'message' => 'کلمه عبور جدید با هم مطابقت ندارند!'
           ],403);
       }
       $findUser = User::where('id',auth()->user()->id)->first();
       if(!$findUser){
           return response()->json(['status' => false,'message' => 'حساب کاربری یافت نشد!'],403);
       }
       $findUser->password = $request->password;
       $findUser->save();
       return response()->json(['status' => false,'message' => 'کله عبور با موفقیت بروزرسانی شد!']);
   }
   public function edit_detial(Request $request){
       $findUser = User::where('id',auth()->user()->id)->first();
       if(!$findUser){
           return response()->json(['status' => false,'message' => 'حساب کاربری یافت نشد!'],403);
       }
       if($request->phonenumber){
           if(!preg_match('/^(09){1}[0-9]{9}+$/', $request->phonenumber)){
               return response()->json(['message' => 'لطفا یک شماره تماس معتبر وارد نمایید همراه با 0 باشد!'],403);
           }
           $findUser->phonenumber = $request->phonenumber;
       }
       if($request->name){
           $findUser->name = $request->name;
       }
       $findUser->save();
       return response()->json(['status' => false,'message' => 'اطلاعات کاربری با موفقیت بروزرسانی شد!']);
   }
   public function auth_log(Request $request){
       $radLog =  new RadPostAuth();
       $radLog = $radLog->where('username',$request->user()->username);

       return new RadAuthAcctCollection($radLog->orderBY('id','DESC')->paginate(5));
   }
   public function get_servers(Request $request){

       return new GetServerCollection(Ras::where('is_enabled',1)->orderBy('name','DESC')->get());
   }


   public function get_groups(){
       $ballance = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','plus')->get()->sum('price');
       $ballance_minus = Financial::where('for',auth()->user()->id)->where('approved',1)->where('type','minus')->get()->sum('price');

       $credit = $ballance - $ballance_minus;
       if($credit <= 0){
           $credit = 0;
       }

       return response()->json([
             'groups' => Helper::getGroupPriceReseler(),
             'credit' => $credit,
             'expire_set' => auth()->user()->expire_set,
             'left_time' => (auth()->user()->expire_date !== NULL ? Carbon::now()->diffInDays(auth()->user()->expire_date, false) : false),

         ]);
   }

   public function get_group(){
       return response()->json(Helper::getGroupPriceReseler('one',auth()->user()->group_id));
   }

   public function charge_account(Request $request){
       $findGroups = Helper::getGroupPriceReseler('one',$request->id,true);
       if(!$findGroups){
           return response()->json(['status' => false,'message' => 'گروه مورد نظر یافت نشد!']);
       }

       $price =  (int) $findGroups['price'];
       $res_price =  (int) $findGroups['seller_price'];

       if(auth()->user()->creator !== 2) {
           $minus_income = Financial::where('for', auth()->user()->creator)->where('approved', 1)->whereIn('type', ['minus'])->sum('price');
           $icom_user = Financial::where('for', auth()->user()->creator)->where('approved', 1)->whereIn('type', ['plus'])->sum('price');
           $incom = $icom_user - $minus_income;
           if (($incom < $res_price)) {
               return response()->json(['status' => false, 'message' => 'امکان شارژ اکانت در حال حاضر وجد ندارد!'],403);
           }
       }

       $find = User::where('id',auth()->user()->id)->first();

       $findGroup = Groups::where('id',$findGroups['id'])->first();
       $exp_val_minute = $find->exp_val_minute;
       if($findGroup->id !== $find->group_id){
           if($findGroup->expire_type !== 'no_expire'){
               if($findGroup->expire_type == 'minutes'){
                   $exp_val_minute = $findGroup->expire_value;
               }elseif($findGroup->expire_type == 'month'){
                   $exp_val_minute = floor($findGroup->expire_value * 43800);
               }elseif($findGroup->expire_type == 'days'){
                   $exp_val_minute = floor($findGroup->expire_value * 1440);
               }elseif($findGroup->expire_type == 'hours'){
                   $exp_val_minute = floor($findGroup->expire_value * 60);
               }elseif($findGroup->expire_type == 'year'){
                   $exp_val_minute = floor($findGroup->expire_value * 525600);
               }
           }

           SaveActivityUser::send($find->id,auth()->user()->id,'change_group_user',['last' => $find->group->name,'new' => $findGroups['name']]);
           $find->group_id = $findGroup->id;
           $find->exp_val_minute = $exp_val_minute;
           $find->multi_login = $findGroup->multi_login;
           $find->expire_type = $findGroup->expire_type;
           $find->expire_value = $findGroup->expire_value;


       }

       $find->expire_set = 0;
       $find->limited = 0;
       $find->first_login = NULL;
       $find->expire_date = NULL;
       $find->save();

       SaveActivityUser::send($find->id,auth()->user()->id,'user_recharge_account',[]);

       $financial  = new Financial();
       $financial->creator = $find->creator;
       $financial->for = auth()->user()->id;
       $financial->description = 'تمدید اکانت';
       $financial->type = 'minus';
       $financial->approved = 1;
       $financial->price = $price;
       $financial->save();
       SendNotificationAdmin::send(auth()->user()->id,'user_charge_account',['for' => $find->creator ,'price' => $request->price,'group_name' => $findGroup['name']]);


       if(auth()->user()->creator !== 2) {
           $financial_cr = new Financial();
           $financial_cr->creator = 2;
           $financial_cr->for = auth()->user()->creator;
           $financial_cr->description = 'تمدید اکانت کاربر ' . auth()->user()->username;
           $financial_cr->type = 'minus';
           $financial_cr->approved = 1;
           $financial_cr->price = $price;
           $financial_cr->save();
       }



       return response()->json([
           'status' => true,
           'message' => 'حساب کاربری شما با موفقیت تمدید شد!'
       ]);

   }
}
