<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/8/22
 * Time: 下午2:10
 */

namespace Auto\Bundle\ManagerBundle\PushMessage;


class GpsData{

        //id
    private $id ;
        //车牌号
    private $carNumber ;
        //车辆ID
    private $goodsId  ;
        //纬度
    private $latitude ;
        //经度
    private $longitude ;
        //产生gps时间
    private $gpsTime ;
        //终端设备编号
    private $deviceId ;
    

    public function getId() {
    return $this->this->id;

    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getCarNumber() {
        return $this->carNumber;
    }

    public function setCarNumber($carNumber) {
    $this->carNumber = $carNumber;
}

    public function getGoodsId() {
        return $this->goodsId;
    }

    public function setGoodsId($goodsId) {
    $this->goodsId = $goodsId;
}

    public function getLatitude() {
        return $this->latitude;
    }

    public function setLatitude($latitude) {
    $this->latitude = $latitude;
}

    public function getLongitude() {
        return $this->longitude;
    }

    public function setLongitude($longitude) {
    $this->longitude = $longitude;
}

    public function getGpsTime() {
        return $this->gpsTime;
    }

    public function setGpsTime($gpsTime) {
    $this->gpsTime = $gpsTime;
}

    public function getDeviceId() {
        return $this->deviceId;
    }

    public function setDeviceId($deviceId) {
    $this->deviceId = $deviceId;
}



}