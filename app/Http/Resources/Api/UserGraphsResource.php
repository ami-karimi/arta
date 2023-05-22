<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\User;
use App\Models\UserGraph;
use App\Models\RadAcct;
use Carbon\Carbon;

class UserGraphsResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
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
        return [
            'data' => $this->collection->map(function ($item){
                $findUser = User::where('username',$item->username)->first();

                if($findUser) {
                    $findOrCreateTotals = UserGraph::where('user_id', $findUser->id)->where('date',Carbon::now()->format('Y-m-d'))->first();
                    if ($findOrCreateTotals) {
                        $findOrCreateTotals->rx += $item->download_sum;
                        $findOrCreateTotals->tx += $item->upload_sum;
                        $findOrCreateTotals->total += $item->download_sum + $item->upload_sum;
                        $findOrCreateTotals->save();
                    } else {
                        UserGraph::create([
                            'date' => Carbon::now()->format('Y-m-d'),
                            'user_id' => $findUser->id,
                            'rx' => $item->download_sum,
                            'tx' => $item->upload_sum,
                            'total' => $item->download_sum + $item->upload_sum,
                        ]);
                    }
                }

                RadAcct::where('username',$item->username)->where('acctstoptime','!=','NULL')->delete();

                return [
                  'user_id' => ($findUser ? $findUser->id : false),
                  'username' => $item->username,
                  'upload_sum' => $item->upload_sum,
                  'upload_format' => $this->formatBytes($item->upload_sum,2),
                  'download_sum' => $item->download_sum,
                  'download_format' => $this->formatBytes($item->download_sum,2),
                  'total_sum' =>   $item->download_sum + $item->upload_sum,
                  'total_format' =>    $this->formatBytes($item->download_sum + $item->upload_sum),
                ];
            })
        ];
    }
}
