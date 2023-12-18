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
        $CDN = $this->Server['CDN_ADDRESS'];
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
    public function request(string $method, array | string $param = "",$type = "POST")
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
             if(is_null($response)){
               unlink($this->cookie_txt_path);
                 curl_close($ch);
                 return [
                "msg" => "Status Code : 0",
                "success" => false
               ];
            }
            $body = substr($response, $headerSize);
            $dataObject = json_decode($body,true);
            curl_close($ch);

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
        if(is_null($get)){
            unlink($this->cookie_txt_path);
            return false;
        }
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


            $user['url'] =  $this->getConnectionLink(json_encode($item), $user)[0];
            $user['url_encode'] =  urlencode($this->getConnectionLink( json_encode($item), $user)[0]);




        return ['inbound' => $inBound ,'user' => $user];
    }
    public function getConnectionLink($row,$client,$sni = false)
    {

        $uniqid = $client['id'];
        $row = json_decode($row);
        $port = $row->port;

        $protocol = $row->protocol;


                    $email = $client['email'];
                     $remark = $row->remark;

                    $tlsStatus = json_decode($row->streamSettings)->security;
                     $tlsSetting = false;
                    if(isset(json_decode($row->streamSettings)->tlsSettings)) {
                        $tlsSetting = json_decode($row->streamSettings)->tlsSettings;
                    }
                    $xtlsSetting  = false;
                    if(isset(json_decode($row->streamSettings)->xtlsSettings)){
                        $xtlsSetting = json_decode($row->streamSettings)->xtlsSettings;
                    }
                    $netType = json_decode($row->streamSettings)->network;
                    if ($netType == 'tcp') {
                        $header_type = json_decode($row->streamSettings)->tcpSettings->header->type;
                        $path = json_decode($row->streamSettings)->tcpSettings->header->request->path[0];
                        $host = json_decode($row->streamSettings)->tcpSettings->header->request->headers->Host[0];

                        if ($tlsStatus == "reality") {
                            $realitySettings = json_decode($row->streamSettings)->realitySettings;
                            $fp = $realitySettings->settings->fingerprint;
                            $spiderX = $realitySettings->settings->spiderX;
                            $pbk = $realitySettings->settings->publicKey;
                            $sni = $realitySettings->serverNames[0];
                            $flow = $client['flow'];
                            $sid = $realitySettings->shortIds[0];
                        }
                    }
                    if ($netType == 'ws') {
                        $header_type = json_decode($row->streamSettings)->wsSettings->header->type;
                        $path = json_decode($row->streamSettings)->wsSettings->path;
                        $host = json_decode($row->streamSettings)->wsSettings->headers->Host;
                    }
                    /*
                    if ($header_type == 'http' && empty($host)) {
                        $request_header = explode(':', $request_header);
                        $host = $request_header[1];
                    }
                    */
                    if ($netType == 'grpc') {
                        if ($tlsStatus == 'tls') {
                            $alpn = "";
                            if(isset($tlsSetting->certificates->alpn)) {
                                $alpn = $tlsSetting->certificates->alpn;
                            }
                            if (isset($tlsSetting->settings->serverName)) $sni = $tlsSetting->settings->serverName;
                        } elseif ($tlsStatus == "reality") {
                            $realitySettings = json_decode($row->streamSettings)->realitySettings;
                            $fp = $realitySettings->settings->fingerprint;
                            $spiderX = $realitySettings->settings->spiderX;
                            $pbk = $realitySettings->settings->publicKey;
                            $sni = $realitySettings->serverNames[0];
                            $flow = $client['flow'];
                            $sid = $realitySettings->shortIds[0];
                        }
                        $serviceName = json_decode($row->streamSettings)->grpcSettings->serviceName;
                        $grpcSecurity = json_decode($row->streamSettings)->security;
                    }
                    if ($tlsStatus == 'tls') {
                        $serverName = $tlsSetting->serverName;
                        if (isset($tlsSetting->settings->serverName)) $sni = $tlsSetting->settings->serverName;
                    }
                    if ($tlsStatus == "xtls") {
                        $serverName = $xtlsSetting->serverName;
                        $alpn = $xtlsSetting->alpn;
                        if (isset($xtlsSetting->settings->serverName)) $sni = $xtlsSetting->settings->serverName;
                    }
                    if ($netType == 'kcp') {
                        $kcpSettings = json_decode($row->streamSettings)->kcpSettings;
                        $kcpType = $kcpSettings->header->type;
                        $kcpSeed = $kcpSettings->seed;
                    }






        $protocol = strtolower($protocol);
        $serverIp = [$this->Server['CDN_ADDRESS']];
        $outputLink = array();
        foreach ($serverIp as $server_ip) {
                 $server_ip = str_replace("\r", "", ($server_ip));
                if ($protocol == 'vless') {

                    if (strlen($sni) > 1 && $tlsStatus != "reality") $psting = "&sni=$sni"; else $psting = '';
                    if ($netType == 'tcp') {
                        if ($netType == 'tcp' and $header_type == 'http') $psting .= '&headerType=http';
                        if ($tlsStatus == "xtls") $psting .= "&flow=xtls-rprx-direct";
                        if ($tlsStatus == "reality") $psting .= "&fp=$fp&pbk=$pbk&sni=$sni" . ($flow != "" ? "&flow=$flow" : "") . "&sid=$sid&spx=$spiderX";
                        if ($header_type == "http") $psting .= "&path=/&host=$host";
                        $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus{$psting}#$remark";
                    } elseif ($netType == 'ws') {
                         $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&path=/&host=$host{$psting}#$remark";
                    } elseif ($netType == 'kcp')
                        $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&headerType=$kcpType&seed=$kcpSeed#$remark";
                    elseif ($netType == 'grpc') {
                        if ($tlsStatus == 'tls') {
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&sni=$serverName#$remark";
                        } elseif ($tlsStatus == "reality") {
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&fp=$fp&pbk=$pbk&sni=$sni" . ($flow != "" ? "&flow=$flow" : "") . "&sid=$sid&spx=$spiderX#$remark";
                        } else {
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName#$remark";
                        }
                    }
                } elseif ($protocol == 'trojan') {
                    $psting = '';
                    if ($header_type == 'http') $psting .= "&path=/&host=$host";
                    if ($netType == 'tcp' and $header_type == 'http') $psting .= '&headerType=http';
                    if (strlen($sni) > 1) $psting .= "&sni=$sni";
                    $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus{$psting}#$remark";

                    if ($netType == 'grpc') {
                        if ($tlsStatus == 'tls') {
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName&sni=$sni#$remark";
                        } else {
                            $outputlink = "$protocol://$uniqid@$server_ip:$port?type=$netType&security=$tlsStatus&serviceName=$serviceName#$remark";
                        }

                    }
                } elseif ($protocol == 'vmess') {
                    $vmessArr = [
                        "v" => "2",
                        "ps" => $remark,
                        "add" => $server_ip,
                        "port" =>  $port,
                        "id" => $uniqid,
                        "aid" => 0,
                        "net" => $netType,
                        "type" => ($header_type) ? $header_type : ($kcpType ? $kcpType : "none"),
                        "host" => $host,
                        "path" => (is_null($path) and $path != '') ? '/' : (is_null($path) ? '' : $path),
                        "tls" => ((is_null($tlsStatus)) ? 'none' : $tlsStatus)
                    ];
                    if ($netType == 'grpc') {
                        if (!is_null($alpn) and json_encode($alpn) != '[]' and $alpn != '') $vmessArr['alpn'] = $alpn;
                        if (strlen($serviceName) > 1) $vmessArr['path'] = $serviceName;
                        $vmessArr['type'] = $grpcSecurity;
                        $vmessArr['scy'] = 'auto';
                    }
                    if ($netType == 'kcp') {
                        $vmessArr['path'] = $kcpSeed ? $kcpSeed : $vmessArr['path'];
                    }

                    if (strlen($sni) > 1) $vmessArr['sni'] = $sni;
                    $urldata = base64_encode(json_encode($vmessArr, JSON_UNESCAPED_SLASHES, JSON_PRETTY_PRINT));
                    $outputlink = "vmess://$urldata";
                }

            $outputLink[] = $outputlink;
        }

        return $outputLink;
    }



}
