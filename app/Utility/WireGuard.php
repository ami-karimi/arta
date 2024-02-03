<?php

namespace App\Utility;


use App\Models\Ras;
use App\Models\WireGuardUsers;
use \App\Utility\Mikrotik;

class WireGuard
{

    public  $server_id;
    public  $username;
    public $server;

    public $server_pub_key;
    public $server_port;
    public $ip_address;
    public $ROS;
    public $client_private_key;
    public $client_public_key;
    public $config_file;

    public function __construct(
        string $server_id,
        string $username,
    )
    {
        $this->server_id = $server_id;
        $this->username = $username;
        $find = Ras::where('id',$server_id)->first();
        $this->server = $find;
        $keypair = \sodium_crypto_kx_keypair();
        $this->client_private_key = \base64_encode(\sodium_crypto_kx_secretkey($keypair));
        $this->client_public_key = base64_encode(\sodium_crypto_kx_publickey($keypair));

        $this->config_file = str_replace(' ','',$this->username).strtotime(date('Ymd'));
    }

    public function removeConfig($public_key){
        $API        = new Mikrotik(
            (object)[
                'l2tp_address' => $this->server->mikortik_domain,
                'mikrotik_port' => $this->server->mikrotik_port,
                'username' => $this->server->mikrotik_username,
                'password' => $this->server->mikrotik_password,
            ]
        );
        $API->debug = false;
        $res=$API->connect();
        $this->ROS = $API;
        if($res['ok']) {
            $findUser  = $this->ROS->bs_mkt_rest_api_get('/interface/wireguard/peers?interface=ROS_WG_USERS&public-key='.$public_key);
            if($findUser['ok']){
                if(count($findUser['data'])) {
                    $this->ROS->bs_mkt_rest_api_del("/interface/wireguard/peers/" . $findUser['data'][0]['.id']);
                    $this->delQuee(['allowed-address' => $findUser['data'][0]['allowed-address']]);
                    return ['status' => true, 'message' => 'Removed User'];
                }
            }
            return ['status' => true,'message' => 'User Not Find Im Delete Record'];
        }else{
            return ['status' => true,'message' => 'Not Can Connect Server'];

        }

    }

    public function delQuee($data = []){

        $findQuea  = $this->ROS->bs_mkt_rest_api_get('/queue/simple?target='.$data['allowed-address']);
        if($findQuea['ok']) {
            foreach ($findQuea['data'] as $row) {
                $this->ROS->bs_mkt_rest_api_del('/queue/simple/' . $row['.id']);
            }
            return ['status' => true,'message' => 'Deleted'];
        }
        return ['status' => false,'message' => 'not Find'];

    }

    public function addQuee($data = []){

        $add  = $this->ROS->bs_mkt_rest_api_add('/queue/simple', array(
            'name' => $data['name'],
            'target' => $data['ip'],
            'max-limit' =>"80M/10M",
        ));

        return ['status' => true,'re' => $add];

    }

    public function getAllPears(){
        $checkInterface = $this->getInterface();
        if(!$checkInterface['status']){
            return $checkInterface;
        }

        $findUser  = $this->ROS->comm('/interface/wireguard/peers/print', array(
            '?interface' => 'ROS_WG_USERS',
        ));

        return ['status'=> true,'peers' => $findUser];
    }

    public function ChangeConfigStatus($public_key,$status ){
        $API        = new Mikrotik( (object)[
            'l2tp_address' => $this->server->mikortik_domain,
            'mikrotik_port' => $this->server->mikrotik_port,
            'username' => $this->server->mikrotik_username,
            'password' => $this->server->mikrotik_password,
        ]);
        $API->debug = false;
        $res=$API->connect();
        $this->ROS = $API;
        if($res['ok']) {

            $findUser = $this->ROS->bs_mkt_rest_api_get('/interface/wireguard/peers?interface=ROS_WG_USERS&public-key=' . $public_key);
            if (!count($findUser['data'])) {
                return ['status' => false, 'message' => 'User Not Find'];
            }

            $re = $this->ROS->bs_mkt_rest_api_upd("/interface/wireguard/peers/".$findUser['data'][0]['.id'], array(
                'disabled' => ($status ? 'no' : 'yes'),
            ));
            return ['status' => true,'re' => $re];
        }

        return ['status' => false,'re' => 'Not Connect Server'];


    }
    public function getUser($public_key){
        $API        = new Mikrotik( (object)[
            'l2tp_address' => $this->server->mikortik_domain,
            'mikrotik_port' => $this->server->mikrotik_port,
            'username' => $this->server->mikrotik_username,
            'password' => $this->server->mikrotik_password,
        ]);
        $API->debug = false;
        if(!$this->server){
            return ['status' => false, 'message' => 'Nots Can Connect To Server'];
        }
        $res=$API->connect();
        if($res['ok']) {
            $this->ROS = $API;
        }else{
            return ['status' => false,'message' => 'Not Conenct To Server'];
        }
        $BRIDGEINFO_Peers = $this->ROS->bs_mkt_rest_api_get('/interface/wireguard/peers?interface=ROS_WG_USERS&public-key='.$public_key);
        if(!$BRIDGEINFO_Peers['ok']){
            return ['status' => false,'message' => 'Not Find User'];
        }
        if(count($BRIDGEINFO_Peers['data'])){
            return ['status' => true,'user' => $BRIDGEINFO_Peers['data'][0]];
        }

        return ['status' => false,'message' => 'Not Find User'];
    }

    public function Run(){
        $checkInterface = $this->getInterface();

        if(!$checkInterface['status']){
            return $checkInterface;
        }

         $sd = $this->CreatePear();

        $this->CreateUserConfig();

        $this->addQuee(['ip' => $this->ip_address,'name' => $this->config_file]);

        return [
          'status' => ($sd['ok']  ? true : false),
          'client_private_key' => $this->client_private_key,
          'client_public_key' => $this->client_public_key,
          'config_file' => $this->config_file,
          'server_id' => $this->server_id,
          'server_pub_key' => $this->server_pub_key."=",
          'server_port' => $this->server_port,
          'ip_address' => $this->ip_address,

        ];
    }



    public function getInterface(){
        $API        = new Mikrotik( (object)[
            'l2tp_address' => $this->server->mikortik_domain,
            'mikrotik_port' => $this->server->mikrotik_port,
            'username' => $this->server->mikrotik_username,
            'password' => $this->server->mikrotik_password,
        ]);
        $API->debug = false;
        if(!$this->server){
            return ['status' => false, 'message' => 'Nots Can Connect To Server'];
        }
        $res=$API->connect();
        if($res['ok']){
            $this->ROS = $API;
            $BRIDGEINFO = $API->bs_mkt_rest_api_get('/interface/wireguard?name=ROS_WG_USERS');

            if(!$BRIDGEINFO['ok']){
                return ['status' => false, 'message' => 'Could Not Get Interface List'];
            }
            if(count($BRIDGEINFO['data'])) {
                $this->server_pub_key = $BRIDGEINFO['data'][0]['public-key'];
                $this->server_port = $BRIDGEINFO['data'][0]['listen-port'];
                $this->ROS = $API;

                $newIp = $this->findIpaddress();
                $this->ip_address = $newIp;
                return ['status' => true];
            }
            return ['status' => false, 'message' => 'Not Can Get Wireguard Interface'];
        }

        return ['status' => false, 'message' => 'Not Can Connect To Server'];
    }

    public function findIpaddress(){

        $to = 253;
        $AllIp = [];
        for ($i = 2; $i < 255;$i++){
            $AllIp[] = "12.11.10." . $i;
        }
        foreach ($AllIp as $ip) {
            $findIp = WireGuardUsers::where('server_id',$this->server->id)->where('user_ip',$ip)->first();
            if (!$findIp) {
                return $ip;
            }
        }

        return false;
    }

    public function CreatePear(){
        return $this->ROS->bs_mkt_rest_api_add('/interface/wireguard/peers', array(
            'interface' => 'ROS_WG_USERS',
            'allowed-address' => $this->ip_address."/32",
            'public-key' => $this->client_public_key,
        ));
    }

    public function CreateUserConfig(){
        $fp = fopen(public_path()."/configs/".$this->config_file.".conf","wb");
        $content = "[Interface] \n";
        $content .= "PrivateKey = ".$this->client_private_key;
        $content .= "\nAddress = ".$this->ip_address."/32";
        $content .= "\nDNS = 8.8.8.8";
        $content .= "\n[Peer]";
        $content .= "\nPublicKey = ".$this->server_pub_key;
        $content .= "\nAllowedIPs = 0.0.0.0/0";
        $content .= "\nEndpoint = ".$this->server->l2tp_address.":".$this->server_port;
        $content .= "\nPersistentKeepalive = 10";
        fwrite($fp,$content);
        fclose($fp);
    }

}

