<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use \Morilog\Jalali\Jalalian;

class RadAcctCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($item){
                return [
                    'radacctid' => $item->radacctid,
                    'servername' => ($item->servername ? $item->servername->name : '---'),
                    'nasipaddress' =>$item->nasipaddress,
                    'start_time' => Jalalian::forge($item->acctstarttime)->__toString(),
                    'acctupdatetime' => Jalalian::forge($item->acctupdatetime)->__toString(),
                    'acctstoptime' => ($item->acctstoptime !== NULL ? Jalalian::forge($item->acctstoptime)->__toString() : ''),
                    'callingstationid' => $item->callingstationid,
                    'acctterminatecause' => $item->acctterminatecause,
                    'framedipaddress' => $item->framedipaddress,
                    'acctsessiontime' => gmdate("H:i:s",$item->acctsessiontime) ,
                ];
            })
        ];
    }
}
