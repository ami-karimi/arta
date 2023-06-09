<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Utility\WireGuard;
use Morilog\Jalali\Jalalian;

class WireGuardConfigCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
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


    public function toArray(Request $request): array
    {
        return $this->collection->map(function($item){
            $wire = new WireGuard($item->server_id,'user');
            $findUser = $wire->getUser($item->public_key);

            $ROS_DATA = [];
            if($findUser['status']){
                $ROS_DATA = $findUser['user'];
            }
            return [
              'id' => $item->id,
              'server' => $item->server ? $item->server->name : '---',
              'name' => $item->profile_name,
              'config_file' => url("/configs/$item->profile_name.conf"),
              'qr_file' => url("/configs/$item->profile_name.png"),
              'config_download_patch' => url("/api/download/$item->profile_name.conf"),
              'is_enabled' => $item->is_enabled,
              'ros_data' => (count($ROS_DATA) ? ['rx' => $this->formatBytes($ROS_DATA['rx'],2),'tx' => $this->formatBytes($ROS_DATA['tx'],2)] : ['rx' => 'NULL','tx'=> 'NULL']),
              'created_at' =>Jalalian::forge($item->created_at)->__toString(),
              'updated_at' => $item->updated_at,
            ];
        })->toArray($request);
    }
}
