<?php
namespace App\Utility;

use App\Models\Ras;

class V2raySN {
    public $Server = [
        'HOST' => null,
        'PORT' => null,
        'USERNAME' => null,
        'PASSWORD' => null,
    ];
    public $error = [
        'status' => false,
        'message' => null
    ];

    private string $cookies_directory;

    private string $cookie_txt_path;

    public mixed $empty_object;


    public function __construct($server = [])
    {
        $this->Server = $server;

        $this->cookies_directory = public_path('.cookies/');
        $HOST = $this->Server['HOST'];
        $PORT = $this->Server['PORT'];
        $this->empty_object = new \stdClass();
        $this->cookies_directory = public_path('.cookies/');
        $this->cookie_txt_path = "$this->cookies_directory$HOST.$PORT.txt";

        if(!is_dir($this->cookies_directory)) mkdir($this->cookies_directory);
        if(!file_exists($this->cookie_txt_path))
        {
            $login = $this->login();

            if(!$login["success"])
            {
                $this->error = ['status' => true,'message' => $login['msg']];
                unlink($this->cookie_txt_path);
                return false;
            }else{
                $this->error = ['status' => false,'message' => ''];
            }
        }


    }
    public function request(string $method, array | string $param = "",$type = "POST") : array
    {
        $URL = "http://".$this->Server['HOST'].":".$this->Server['PORT']."/$method";

        $POST = is_array($param) ? json_encode($param) : $param;

        $options = [
            CURLOPT_URL => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_COOKIEFILE => $this->cookie_txt_path,
            CURLOPT_COOKIEJAR => $this->cookie_txt_path,
            CURLOPT_HEADER  => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POST => ($type == 'POST' ? true : false),
            CURLOPT_POSTFIELDS => ($type == 'POST' ? $POST : false)
        ];


            $ch = curl_init();
            curl_setopt_array($ch, $options);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json'
            ));

            $response = curl_exec($ch);
            $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($response, $headerSize);
            $dataObject = json_decode($body,true);
            curl_close($ch);
            if(is_null($http_code)){
                return [
                    "msg" => "Status Code : $http_code",
                    "success" => false
                ];
            }
            return match ($http_code) {
                200 => $dataObject,
                0 => [
                    "msg" => "The Client cannot connect to the server",
                    "success" => false
                ],
                default => [
                    "msg" => "Status Code : $http_code",
                    "success" => false
                ]
            };

    }



    public function login()
    {
        return $this->request("login",[
            "username" => $this->Server['USERNAME'],
            "password" => $this->Server['PASSWORD']
        ]);
    }
    public function InBoandList()
    {
        return $this->request("panel/api/inbounds/list",[],'GET');
    }
    public function add_client(int $service_id,string $email,int $limit_ip = 2,int $totalGB,float $expiretime,bool $enable = true)
    {

        $tm = (86400 * 1000);
        $expiretime = $tm * $expiretime;
        $user_id = $this->genUserId();
        $data = $this->request("panel/api/inbounds/addClient",[
            'id' => $service_id,
            'settings' => json_encode([
                'clients' => [[
                    'id' => $user_id,
                    'alterId' => 0,
                    'email' => $email,
                    'limitIp' => $limit_ip,
                    'totalGB' => $totalGB * 1024 * 1024 * 1024,
                    'expiryTime' => "-$expiretime",
                    'enable' => $enable,
                    'tgId' => '',
                    'subId' => '',
                ]]
            ])
        ]);
        $data['uuid'] = $user_id;
        return $data;
    }

    /**
     * @throws Exception
     */
    private function genUserId() : string
    {
        $data = random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function get_client($email = false){
        $get = $this->request("panel/api/inbounds/getClientTraffics/".$email,[],'GET');
        if(!$get['success']){
            return false;
        }

        return $get['obj'];
    }
    public function update_client($uuid = false,$data = []){
        $get = $this->request("panel/api/inbounds/updateClient/".$uuid,[
            'id' => (int) $data['service_id'],
            'settings' => json_encode([
                'clients' => [[
                    'id' => (string) $uuid,
                    'alterId' => 0,
                    'email' => $data['username'],
                    'limitIp' => (int) $data['multi_login'],
                    'totalGB' => (int)  $data['totalGB'] ,
                    'expiryTime' => (int)  $data['expiryTime'],
                    'enable' => (boolean) $data['enable'],
                    'tgId' => '',
                    'subId' => '',
                ]]
            ])]);
        if(!$get['success']){
            return false;
        }

        return true;
    }


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

    public function formatTime(int $time, int $format = 2) : string
    {
        if($format == 1) {
            $lang = ["ثانیه","دقیقه","ساعت","روز","هفته","ماه","سال"]; # Persian
        } else {
            $lang = ["Second(s)","Minute(s)","Hour(s)","Day(s)","Week(s)","Month(s)","Year(s)"];
        }

        if($time >= 1 && $time < 60) {
            return round($time) . " " . $lang[0];
        } elseif ($time >= 60 && $time < 3600) {
            return round($time / 60) . " " . $lang[1];
        } elseif ($time >= 3600 && $time < 86400) {
            return round($time / 3600) . " " . $lang[2];
        } elseif ($time >= 86400 && $time < 604800) {
            return round($time / 86400) . " " . $lang[3];
        } elseif ($time >= 604800 && $time < 2600640) {
            return round($time / 604800) . " " . $lang[4];
        } elseif ($time >= 2600640 && $time < 31207680) {
            return round($time / 2600640) . " " . $lang[5];
        } elseif ($time >= 31207680) {
            return round($time / 31207680) . " " . $lang[6];
        } else {
            return false;
        }
    }

    public function server_status() : array
    {
        $status = $this->request(
            "server/status"
        )["obj"];

        $status["cpu"] = round($status["cpu"]) ."%";
        $status["mem"]["current"] = $this->formatBytes($status["mem"]["current"]);
        $status["mem"]["total"] = $this->formatBytes($status["mem"]["total"]);
        $status["swap"]["current"] = $this->formatBytes($status["swap"]["current"]);
        $status["swap"]["total"] = $this->formatBytes($status["swap"]["total"]);
        $status["disk"]["current"] = $this->formatBytes($status["disk"]["current"]);
        $status["disk"]["total"] = $this->formatBytes($status["disk"]["total"]);
        $status["netIO"]["up"] = $this->formatBytes($status["netIO"]["up"]);
        $status["netIO"]["down"] = $this->formatBytes($status["netIO"]["down"]);
        $status["netTraffic"]["sent"] = $this->formatBytes($status["netTraffic"]["sent"]);
        $status["netTraffic"]["recv"] = $this->formatBytes($status["netTraffic"]["recv"]);
        $status["uptime"] = $this->formatTime($status["uptime"]);

        return $status;
    }
    public function get_user($id = false,$username = false) : array
    {
        $item = (array)$this->request(
            "panel/api/inbounds/get/$id",[],'GET'
        )['obj'];

        $user = [];
        $inBound = [];
            $inBound['up'] = $item['up'];
            $inBound['down'] = $item['down'];
            $inBound['total'] = $item['down'] + $item['up'];
            $inBound['remark'] = $item['remark'];
            $inBound['enable'] = $item['enable'];
            $inBound['expiryTime'] = $item['expiryTime'];
            $inBound['port'] = $item['port'];
            $inBound['protocol'] = $item['protocol'];
            $inBound['tag'] = $item['tag'];
            $inBound['sniffing'] = $item['sniffing'];
            $UUid = false;
            $remark = false;
            foreach (json_decode($item['settings'],true)['clients'] as $client){
                if($client['email'] !== $username){
                    continue;
                }
                $UUid = $client['id'];
                $remark = $item['remark']."-".$client['email'];
                $user = $client;
            }

            $user['url'] =  $this->url($inBound['port'],$inBound['protocol'],$UUid,$remark,json_decode($item['streamSettings'],true)['network'],json_decode($item['streamSettings'],true));
            $user['url_encode'] =  urlencode($this->url($inBound['port'],$inBound['protocol'],$UUid,$remark,json_decode($item['streamSettings'],true)['network'],json_decode($item['streamSettings'],true)));




        return ['inbound' => $inBound ,'user' => $user];
    }

    public function url(
        int $port,
        string $protocol = "",
        string $uid = "",
        string $remark = "",
        string $transmission,
        array $network
    ) : string
    {
        $protocol = $protocol;
        $uid = $uid;
        $remark = $remark;
        $transmission = $transmission;
        $path = $transmission == "ws" ? "/" : "";

        switch ($protocol) {
            case "vmess":
                $vmess_url = "vmess://";
                $vmess_settings = [
                    "v" => "2",
                    "ps" => $remark,
                    "add" => $network['serverName'],
                    "port" => $port,
                    "id" => $uid,
                    "aid" => 0,
                    "net" => $transmission,
                    "type" => "none",
                    "host" => "",
                    "path" => $path,
                    "tls" => "none"
                ];
                $vmess_base = base64_encode(json_encode($vmess_settings));
                return $vmess_url . $vmess_base;

            case "vless":
                $vless_url = "vless://$uid";
                $vless_url .= "@" . $network['tlsSettings']['serverName'] . ":$port";
                $vless_url .= "?mode=gun";
                if (isset($network['security'])) {
                    $vless_url .= "&security=" . $network['security'];
                }
                $vless_url .= "&type=$transmission";
                $vless_url .= "&encryption=none";

                if (isset($network['tlsSettings'])) {
                    if (isset($network['tlsSettings']['fingerprint'])) {
                        $vless_url .= "&fp=" . $network['tlsSettings']['fingerprint'];
                    }
                    if (isset($network['tlsSettings']['alpn'])) {
                        $vless_url .= "&alpn=" . implode(',', $network['tlsSettings']['alpn']);
                    }
                }
                $vless_url .= "&serviceName=#$remark";
                return $vless_url;

            default:
                return "Error, url could not be created";
        }
    }

}
