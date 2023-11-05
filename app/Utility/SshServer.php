<?php

namespace App\Utility;


use App\Models\Ras;
use Morilog\Jalali\Jalalian;
use phpseclib3\Exception\UnableToConnectException;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Process;


class SshServer
{

    public $server = [
        'ip' => '',
        'id' => '',
        'username' => '',
        'password' => '',
        'port' => '',
    ];
    public $ssh;

    public function __construct($server_ip){
        $this->server['ip'] = $server_ip;



    }

    public function  CheckConnect(){
        $server = $this->FindServer();
        if(!$server){
            return [
                'status' => false,
                'message' => 'Not Find Server',
            ];
        }

        $login = $this->LogginServer();

        if(!$login){
            return $login;
        }

        return [
            'status' => true,
        ];
     }


    public function FindServer(){
        $findServer = Ras::where('ipaddress',$this->server['ip'])->where('is_enabled',1)->where('ssh_server',1)->first();
        if($findServer){
            $this->server['id'] = $findServer->id;
            $this->server['username'] = $findServer->ssh_username;
            $this->server['password'] = $findServer->ssh_password;
            $this->server['port'] = $findServer->ssh_port;

            return $findServer;
        }

        return false;
    }
    public function LogginServer(){
        try {
            $ssh = new SSH2($this->server['ip']);
            $ssh->login($this->server['username'], $this->server['password']);

            $this->ssh = $ssh;
            return true;
        }catch (UnableToConnectException $exception){
            return [
                'status' => false,
                'message' => 'Not Connect To Server',
            ];
        }

    }

    public function getOnlineCount(){
        $output = $this->ssh->exec("sudo lsof -i :22 -n | grep -v root | grep ESTABLISHED");
        $onlineuserlist = preg_split("/\r\n|\n|\r/", $output);
        foreach ($onlineuserlist as $user) {
            $user = preg_replace('/\s+/', ' ', $user);
            $userarray = explode(" ", $user);
            if (!isset($userarray[2])) {
                $userarray[2] = null;
            }

            $onlinelist[] = $userarray[2];
        }

        $onlinelist = array_replace($onlinelist, array_fill_keys(array_keys($onlinelist, null), ''));
        $onlinecount = array_count_values($onlinelist);

        return  [
            'status' => true,
            'online_count' => $onlinecount,
            'all_count' => count($onlinelist),
            'users' => $onlinelist,
        ];

    }

    public function getFirstLoginUser($username){
        $output = $this->ssh->exec("sudo journalctl _COMM=sshd | grep 'Accepted password for $username' | head -n 1 | awk '{print $1, $2, $3}'");

        if(strlen($output) > 5){
            return [
                'status' => true,
                'jalali' => Jalalian::forge(strtotime($output))->format('Y-m-d H:i:s'),
                'timestamp' => strtotime($output),
                'orginal' => $output,
            ];
        }

        return [
            'status' => true,
            'jalali' =>  -1,
            'timestamp' => -1,
            'orginal' => -0,
        ];

    }
    public function getLastLoginUser($username){
        $output = $this->ssh->exec("sudo  journalctl _COMM=sshd | grep 'Accepted password for vpn' /var/log/auth.log | tail -n 1 | awk '{print $1, $2, $3}'");

        if(strlen($output) > 5){
            return [
                'status' => true,
                'jalali' => Jalalian::forge(strtotime($output))->format('Y-m-d H:i:s'),
                'timestamp' => strtotime($output),
                'orginal' => $output,
            ];
        }

        return [
            'status' => true,
            'jalali' =>  -1,
            'timestamp' => -1,
            'orginal' => -0,
        ];

    }
    public function test(){
        $output = $this->ssh->exec("pgrep nethogs");


        return $output;

    }

}

