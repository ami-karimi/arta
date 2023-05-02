<?php

namespace App\Utility;


use App\Models\Groups;
use App\Models\ReselerMeta;
use App\Models\UserMetas;
use App\Models\PriceReseler;

class Helper
{

    public static function toArray($array = [],$keys = 'key' ,$values = 'value') {

        $re = [];
        foreach ($array as $value){
            $re[$value[$keys]] = $value[$values];
        }

        return $re;
    }

    public static function getGroupPriceReseler($type = 'list',$group_id = false,$seller_price = false)
    {
        $metas = UserMetas::select(['key', 'value'])->where('user_id', auth()->user()->id)->get();
        $full_meta = Helper::toArray($metas);


        $group_lists = Groups::get();

        $RsMtFull = false;
        if (auth()->user()->creator) {
            $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', auth()->user()->creator)->get();
            $RsMtFull = Helper::toArray($Reselermetas);
        }


        $group_re = [];

        foreach ($group_lists as $row) {

            if (isset($RsMtFull['disabled_group_' . $row->id])) {
                continue;
            }

            if (isset($full_meta['disabled_group_' . $row->id])) {
                continue;
            }

            $price = $row->price;

            if (isset($RsMtFull['group_price_' . $row->id])) {
                $price = (int)$RsMtFull['group_price_' . $row->id];
            }

            if (isset($full_meta['group_price_' . $row->id])) {
                $price = (int)$full_meta['group_price_' . $row->id];
            }

            $re_price = false;
            if($seller_price){
                $re_price = $row->price_reseler;
                $reseler_p = PriceReseler::where('group_id',$row->id)->where('reseler_id',auth()->user()->creator)->first();
                if($reseler_p){
                    $re_price = $row->price;
                }
            }
            $group_re[] = [
                'id' => $row->id,
                'name' => $row->name,
                'seller_price' => $re_price,
                'selected' => (auth()->user()->group_id === $row->id ? true : false),
                'price' => $price,
                'multi_login' => $row->multi_login,
            ];


        }

        if($type == 'list'){
            return $group_re;
        }


        if($group_id){
            foreach ($group_re as $row){
                if((int) $row['id'] == (int) $group_id){
                    return $row;
                }
            }

            return false;
        }


        return [];

    }


    public static function GetReselerGroupList($type = 'list',$group_id = false)
    {
        $group_lists = Groups::get();

        $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', auth()->user()->id)->get();
        $RsMtFull = Helper::toArray($Reselermetas);



        $group_re = [];

        foreach ($group_lists as $row) {
            $price = $row->price;

            if (isset($RsMtFull['group_price_' . $row->id])) {
                $price = (int)$RsMtFull['group_price_' . $row->id];
            }


            $group_re[] = [
                'id' => $row->id,
                'name' => $row->name,
                'multi_login' => $row->multi_login,
                'reseler_price' => $row->price_reseler,
                'cmorgh_price' => $row->price,
                'price' => $price,
                'status' => (isset($RsMtFull['disabled_group_' . $row->id]) ? ((boolean) $RsMtFull['disabled_group_' . $row->id]) : true)
            ];


        }

        if($type == 'list'){
            return $group_re;
        }


        if($group_id){
            foreach ($group_re as $row){
                if((int) $row['id'] == (int) $group_id){
                    return $row;
                }
            }

            return false;
        }


        return [];

    }


}

