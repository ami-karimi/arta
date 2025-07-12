<?php

namespace App\Utility;

use App\Models\Ras;
use App\Models\WireGuardUsers;
use App\Utility\Mikrotik;

class WireGuard
{
    public string $server_id;
    public string $username;
    public object $server;

    public string $server_pub_key;
    public string $server_port;
    public string $ip_address;
    public object $ROS;
    public string $client_private_key;
    public string $client_public_key;
    public string $config_file;

    public function __construct(string $server_id, string $username)
    {
        $this->server_id = $server_id;
        $this->username = $username;
        $this->server = Ras::findOrFail($server_id);

        $keypair = sodium_crypto_kx_keypair();
        $this->client_private_key = base64_encode(sodium_crypto_kx_secretkey($keypair));
        $this->client_public_key = base64_encode(sodium_crypto_kx_publickey($keypair));
        $this->config_file = preg_replace('/\s+/', '', $username) . time();
    }

    public function run(): array
    {
        $interface = $this->getInterface();
        if (!$interface['status']) return $interface;

           $peerCreated = $this->createPeer();
           $this->createUserConfig();
           $this->addQueue(['ip' => $this->ip_address, 'name' => $this->config_file]);

            return [
                'status' => $peerCreated['ok'],
                'client_private_key' => $this->client_private_key,
                'client_public_key' => $this->client_public_key,
                'config_file' => $this->config_file,
                'server_id' => $this->server_id,
                'server_pub_key' => $this->server_pub_key . '=',
                'server_port' => $this->server_port,
                'ip_address' => $this->ip_address,
            ];

    }

    public function removeConfig(string $public_key): array
    {
        if (!$this->connect()) return ['status' => false, 'message' => 'Cannot connect to server'];

        $peer = $this->ROS->bs_mkt_rest_api_get("/interface/wireguard/peers?interface=ROS_WG_USERS&public-key={$public_key}");
        if ($peer['ok'] && !empty($peer['data'])) {
            $this->ROS->bs_mkt_rest_api_del("/interface/wireguard/peers/{$peer['data'][0]['.id']}");
            $this->deleteQueue(['allowed-address' => $peer['data'][0]['allowed-address']]);
            return ['status' => true, 'message' => 'User removed'];
        }

        return ['status' => true, 'message' => 'User not found; local record deleted'];
    }

    public function changeConfigStatus(string $public_key, bool $enable): array
    {
        if (!$this->connect()) return ['status' => false, 'message' => 'Cannot connect to server'];

        $peer = $this->ROS->bs_mkt_rest_api_get("/interface/wireguard/peers?interface=ROS_WG_USERS&public-key={$public_key}");
        if (empty($peer['data'])) {
            return ['status' => false, 'message' => 'User not found'];
        }

        $response = $this->ROS->bs_mkt_rest_api_upd(
            "/interface/wireguard/peers/{$peer['data'][0]['.id']}",
            ['disabled' => $enable ? 'no' : 'yes']
        );

        return ['status' => true, 'response' => $response];
    }

    public function getUser(string $public_key): array
    {
        if (!$this->connect()) return ['status' => false, 'message' => 'Cannot connect to server'];

        $peer = $this->ROS->bs_mkt_rest_api_get("/interface/wireguard/peers?interface=ROS_WG_USERS&public-key={$public_key}");

        if ($peer['ok'] && !empty($peer['data'])) {
            return ['status' => true, 'user' => $peer['data'][0]];
        }

        return ['status' => false, 'message' => 'User not found'];
    }

    public function getAllPeers(): array
    {
        $interface = $this->getInterface();
        if (!$interface['status']) return $interface;

        $peers = $this->ROS->comm('/interface/wireguard/peers/print', ['?interface' => 'ROS_WG_USERS']);
        return ['status' => true, 'peers' => $peers];
    }

    private function connect(): bool
    {
        $api = new Mikrotik((object)[
            'l2tp_address' => $this->server->mikrotik_domain,
            'mikrotik_port' => $this->server->mikrotik_port,
            'username' => $this->server->mikrotik_username,
            'password' => $this->server->mikrotik_password,
        ]);

        $api->debug = false;

        if ($api->connect()['ok']) {
            $this->ROS = $api;
            return true;
        }

        return false;
    }

    private function getInterface(): array
    {
        if (!$this->connect()) {
            return ['status' => false, 'message' => 'Cannot connect to server'];
        }

        $interface = $this->ROS->bs_mkt_rest_api_get('/interface/wireguard?name=ROS_WG_USERS');
        if ($interface['ok'] && !empty($interface['data'])) {
            $data = $interface['data'][0];
            $this->server_pub_key = $data['public-key'];
            $this->server_port = $data['listen-port'];
            $this->ip_address = $this->findAvailableIp();
            if(!$this->ip_address){
                return ['status' => false, 'message' => 'WireGuard interface not found'];
            }
            return ['status' => true];
        }

        return ['status' => false, 'message' => 'WireGuard interface not found'];
    }

    private function findAvailableIp(): string|false
    {
        for ($i = 2; $i <= 254; $i++) {
            $ip = "12.11.10.$i";
            $exists = WireGuardUsers::where('server_id', $this->server_id)->where('user_ip', $ip)->exists();
            if (!$exists) return $ip;
        }

        return false;
    }

    private function createPeer(): array
    {
        return $this->ROS->bs_mkt_rest_api_add('/interface/wireguard/peers', [
            'interface' => 'ROS_WG_USERS',
            'allowed-address' => "{$this->ip_address}/32",
            'public-key' => $this->client_public_key,
        ]);
    }

    private function createUserConfig(): void
    {
        $path = public_path("configs/{$this->config_file}.conf");
        $config = <<<CONF
[Interface]
PrivateKey = {$this->client_private_key}
Address = {$this->ip_address}/32
DNS = 8.8.8.8

[Peer]
PublicKey = {$this->server_pub_key}
AllowedIPs = 0.0.0.0/0
Endpoint = {$this->server->l2tp_address}:{$this->server_port}
PersistentKeepalive = 10
CONF;
        file_put_contents($path, $config);
    }

    private function addQueue(array $data): array
    {
        $response = $this->ROS->bs_mkt_rest_api_add('/queue/simple', [
            'name' => $data['name'],
            'target' => $data['ip'],
            'max-limit' => "80M/10M",
        ]);

        return ['status' => true, 'response' => $response];
    }

    private function deleteQueue(array $data): array
    {
        $queues = $this->ROS->bs_mkt_rest_api_get('/queue/simple?target=' . $data['allowed-address']);
        if ($queues['ok']) {
            foreach ($queues['data'] as $queue) {
                $this->ROS->bs_mkt_rest_api_del('/queue/simple/' . $queue['.id']);
            }
            return ['status' => true, 'message' => 'Queue(s) deleted'];
        }

        return ['status' => false, 'message' => 'No queue found'];
    }
}
