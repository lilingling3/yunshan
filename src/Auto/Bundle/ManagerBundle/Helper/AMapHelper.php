<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/11/6
 * Time: 下午1:25
 */

namespace Auto\Bundle\ManagerBundle\Helper;



class AMapHelper extends AbstractHelper{
    const AMAP_KEY = "60664aedfa861356d4d95a3f80c2c96d";
    const EARTH_RADIUS = 6378.137;

    public function walking_distance($origin,$destination){

        $origin_str = implode(",",$origin);
        $destination_str = implode(",",$destination);
        $url = "http://restapi.amap.com/v3/direction/walking?origin=".$origin_str."&destination=".$destination_str."&key="
            .self::AMAP_KEY;
        $map = $this->curlHelper->get_url_contents($url);
        $map = $this->object_array(json_decode($map));


        if($map['status'] == 1){

            $distance = $map['route']['paths'][0]['distance'];
            return $distance;

        }else{
            return null;
        }

    }
//$origin  必须是二维数组
    public function drive_distance($origin,$destination){

        $destination_str = implode(",",$destination);
        $origin_list = array_map(function($arr){
            return implode(",",$arr);
        },$origin);

        $orgin_str = implode("|",$origin_list);
        $url = "http://restapi.amap.com/v3/distance?origins=".$orgin_str."&destination=".$destination_str."&output=json&key="
            .self::AMAP_KEY;

        $map = $this->curlHelper->get_url_contents($url);
        $map = $this->object_array(json_decode($map));


        if($map['status'] == 1){

            $distance = array_reduce(

                $map['results'],function($v1,$v2){
                    return $v1+$v2['distance'];
                });
            return $distance;

        }else{
            return null;
        }

    }


    //经纬度之间距离计算
    function rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }

    function straight_distance($origin,$destination)
    {
        $lng1 = $origin[0];
        $lat1 = $origin[1];
        $lng2 = $destination[0];
        $lat2 = $destination[1];

        $radLat1 = $this->rad($lat1);
        //echo $radLat1;
        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) +
                cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
        $s = $s *self::EARTH_RADIUS;
        $s = round($s * 1000) ;
        return $s;
    }


    function square_point($lng,$lat,$distance=10){

        if (!is_double($lng))
        {
            $lng = doubleval($lng);
        }

        if (!is_double($lat))
        {
            $lat = doubleval($lat);
        }

        $dlng = 2*asin(sin($distance/(2*6378.137))/cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);

        $dlat=$distance/self::EARTH_RADIUS;
        $dlat = rad2deg($dlat);


        return [$lng+$dlng,$lat+$dlat,$lng-$dlng,$lat-$dlat];

    }


    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }



    function object_array($array){
        if(is_object($array)){
            $array = (array)$array;
        }
        if(is_array($array)){
            foreach($array as $key=>$value){
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }
}
