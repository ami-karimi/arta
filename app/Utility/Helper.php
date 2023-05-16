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


    public static function getMePrice($group_id =  false){

        if(!$group_id){
            return 0;
        }
        $group= Groups::where('id',$group_id)->first();

        if(!$group){
            return false;
        }

        $creator = false;
        if(auth()->user()->creator){
            $creator  = auth()->user()->creator;
        }
        $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key','price_for_reseler_'.$group_id."_for_".auth()->user()->id)->first();
        if($Reselermetas){
            return (int) $Reselermetas->value;
        }


        $Reselermetas = ReselerMeta::select(['key', 'value'])->where('reseler_id', $creator)->where('key','price_for_reseler_'.$group_id)->first();
        if($Reselermetas){
            return (int) $Reselermetas->value;
        }


        return $group->price_reseler;

    }

    public static function GetReselerGroupList($type = 'list',$group_id = false,$for = false)
    {
        $group_lists = Groups::get();


        $Reselermetas = ReselerMeta::select(['key', 'value']);
          $Reselermetas->where('reseler_id', auth()->user()->id);
          $Reselermetas =   $Reselermetas->get();


        $RsMtFull = Helper::toArray($Reselermetas);



        $group_re = [];

        foreach ($group_lists as $row) {
            $price = $row->price;
            $reseler_price =  self::getMePrice($row->id);
            $re_sell_price =  self::getMePrice($row->id);

            if (isset($RsMtFull['group_price_' . $row->id])) {
                $price = (int)$RsMtFull['group_price_' . $row->id];
            }


            if (isset($RsMtFull['price_for_reseler_' . $row->id])) {
                $re_sell_price = (int)$RsMtFull['price_for_reseler_' . $row->id];
            }



            if($for){
                if (isset($RsMtFull['price_for_reseler_' . $row->id."_for_".$for])) {
                    $re_sell_price = (int)$RsMtFull['price_for_reseler_' . $row->id."_for_".$for];
                }
            }

            if (isset($RsMtFull['reseler_price_' . $row->id."_for_".auth()->user()->id])) {
                $reseler_price = (int)$RsMtFull['reseler_price_' . $row->id."_for_".auth()->user()->id];
            }


            $group_re[] = [
                'id' => $row->id,
                'name' => $row->name,
                'multi_login' => $row->multi_login,
                'reseler_price' => $reseler_price,
                'price_for_reseler' => $re_sell_price,
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

