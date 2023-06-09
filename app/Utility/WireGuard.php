<?php

namespace App\Utility;


use App\Models\Ras;
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

        $this->config_file = $this->username."-".date('Ymd');
    }

    public function getUser($public_key){
        $checkInterface = $this->getInterface();

        if(!$checkInterface['status']){
            return $checkInterface;
        }

        $BRIDGEINFO_Peers = $this->ROS->comm('/interface/wireguard/peers/print', array(
            '?interface' => 'ROS_WG_USERS',
            '?public-key' => $public_key,
        ));
        if(count($BRIDGEINFO_Peers)){
            return ['status' => true,'user' => $BRIDGEINFO_Peers[0]];
        }

        return ['status' => false,'message' => 'Not Find User'];
    }

    public function Run(){
        $checkInterface = $this->getInterface();

        if(!$checkInterface['status']){
            return $checkInterface;
        }

         $this->CreatePear();

        $this->CreateUserConfig();


        return [
          'status' => true,
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
        $API        = new Mikrotik();
        $API->debug = false;
        if($API->connect($this->server->ipaddress, 'admin', 'Amir@###1401')){
            $BRIDGEINFO = $API->comm('/interface/wireguard/print', array(
                '?name' => 'ROS_WG_USERS',
            ));

            if(count($BRIDGEINFO)) {
                $this->server_pub_key = $BRIDGEINFO[0]['public-key'];
                $this->server_port = $BRIDGEINFO[0]['listen-port'];
                $BRIDGEINFO_Peers = $API->comm('/interface/wireguard/peers/print', array(
                    '?interface' => 'ROS_WG_USERS',
                ));

                $newCount = count($BRIDGEINFO_Peers);
                $newIp = "12.11.10.".$newCount+2 ;
                $this->ip_address = $newIp;
                $this->ROS = $API;
                return ['status' => true];
            }
            return ['status' => false, 'message' => 'Not Can Get Wireguard Interface'];
        }

        return ['status' => false, 'message' => 'Not Can Connect To Server'];
    }

    public function CreatePear(){
        return $this->ROS->comm('/interface/wireguard/peers/add', array(
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
        $content .= "\nPublicKey = ".$this->server_pub_key."=";
        $content .= "\nAllowedIPs = 0.0.0.0/0";
        $content .= "\nEndpoint = ".$this->server->ipaddress.":".$this->server_port;
        $content .= "\nPersistentKeepalive = 10";
        fwrite($fp,$content);
        fclose($fp);
    }

}

