<?php
/**
 * Created by PhpStorm.
 * User: Tang
 * Date: 17/5/23
 * Time: 上午11:40
 */

namespace Auto\Bundle\ManagerBundle\Helper;

class YunshanZhihuiHelper extends AbstractHelper
{
    private $bathPath = 'https://139.198.3.24:8443/timesharing';
    private $app_id = '201704282300007148';
    private $charset = 'utf-8';
    private $version = 'v1.0';
    private $sign = 'b586a503049b456304e9f7f75843fbd5';
    //用户
    private $order_auth = 'testbj';
    private $notify_url = 'https://go.win-sky.com.cn/api/rentalCar/yunshanCallback';
    //操作指令存活时间（秒）
    private $order_live = 300;
    //操作车辆的订单号开头(唯一)
    private $order_header = 'yszx';

    //车辆位置查询
    private $location = 'pt101';
    //车辆信息查询
    private $information = 'pt102';
    //车载设备查询
    private $equipment = 'pt103';
    //车辆控制
    private $control = 'pt104';

    //操作车辆指令
    const OPERATE_CLOSE = 1;
    const OPERATE_OPEN = 2;
    const OPERATE_OFF  = 3;
    const OPERATE_ON   = 4;
    const OPERATE_FIND = 5;

    //服务器返回code
    const E_SUCCESS = 1000;
    const E_FAILED = 1001;
    const E_JSON_ERROR = 1002;
    const E_PARAMETER_ERROR = 1003;
    const E_SINE_ERROR = 1004;
    const E_ORDERID_REPEAT = 1005;

    //返回信息
    const ILLEGAL_OPERATION = '非法操作';
    const ERROR_RETURN = '接口返回数据有误';

    //车辆位置查询 默认查询多单量 $num 指定返回数据形式
    public function findCarLocation($carPlates, $deviceId, $operationType = 1, $num = 1)
    {

        $timestamp = date('Y-m-d H:i:s');
        $carPlatesArr = explode(',', $carPlates);
        // 1 查询最后定位数据(可多车) 2 查询(单车)今天的定位数据
        if (1 != $operationType && 2 != $operationType ||
            1 != count($carPlatesArr) && 1 == $num || 1 == count($carPlatesArr) && 1 != $num
        ) {
            return self::ILLEGAL_OPERATION;
        }
        $type = array(
            0 => 'pot_type',
            1 => 1
        );
        if (2 == $operationType && 1 == count($carPlatesArr)) {
            $type = array(
                0 => 'pot_type',
                1 => 2
            );
        }
        return $this->executeCMD($this->location, $timestamp, $type, $carPlates, $deviceId, $num);
    }

    //车辆信息查询 默认单量
    public function findCarInformation($carPlates, $deviceId, $operationType = 1, $num = 1)
    {

        $timestamp = date('Y-m-d H:i:s');
        $carPlatesArr = explode(',', $carPlates);
        //1 不限 2 电车 3 油电混合
        if (1 != $operationType && 2 != $operationType && 3 != $operationType ||
            1 != count($carPlatesArr) && 1 == $num || 1 == count($carPlatesArr) && 1 != $num
        ) {
            return self::ILLEGAL_OPERATION;
        }
        $type = array(
            0 => 'car_type',
            1 => $operationType
        );
        return $this->executeCMD($this->information, $timestamp, $type, $carPlates, $deviceId, $num);
    }

    //车载设备查询
    public function findCarEquipment($carPlates, $deviceId, $operationType = 1, $num = 1)
    {

        $timestamp = date('Y-m-d H:i:s');
        $carPlatesArr = explode(',', $carPlates);
        //1 主设备 2 附加设备
        if (1 != $operationType && 2 != $operationType ||
            1 != count($carPlatesArr) && 1 == $num || 1 == count($carPlatesArr) && 1 != $num
        ) {
            return self::ILLEGAL_OPERATION;
        }
        $type = array(
            0 => 'vt_type',
            1 => $operationType
        );
        return $this->executeCMD($this->equipment, $timestamp, $type, $carPlates, $deviceId, $num);
    }
    
    //车辆控制
    public function operate($carPlates, $deviceId, $operationType, $memberId)
    {

        //1 车门上锁 2 车门解锁 3 断开油路 4 接通油路 5 寻车（鸣笛，双闪）
        if ('close' != $operationType && 'open' != $operationType && 'off' != $operationType &&
            'on' != $operationType && 'find' != $operationType && 'reset' != $operationType) {
            return self::ILLEGAL_OPERATION;
        }
        $carPlatesArr = explode(',', $carPlates);
        if (1 != count($carPlatesArr)) {
            return self::ILLEGAL_OPERATION;
        }

        switch($operationType){
            case 'close':
                $opt = self::OPERATE_CLOSE;
                break;
            case 'open':
                $opt = self::OPERATE_OPEN;
                break;
            case 'off':
                $opt = self::OPERATE_OFF;
                break;
            case 'on':
                $opt = self::OPERATE_ON;
                break;
            case 'find':
                $opt = self::OPERATE_FIND;
                break;
            default :
                $opt = self::OPERATE_FIND;
        }
        $timestamp = date('Y-m-d H:i:s');
        $order_id = $this->create_uuid($this->order_header);
        $order_id .= '-'.$opt.'_'.$memberId;

        $order_auth = $this->order_auth;
        $order_live = date("YmdHis",time()+$this->order_live);

        $notify_url = $this->notify_url;
        
        $pt104 = "biz_content={\"carPlate\":\"$carPlates\",\"device_id\":\"$deviceId\",\"order_id\":\"$order_id\",\"order_auth\":\"$order_auth\",\"order_live\":\"$order_live\",\"opt\":\"$opt\",\"notify_url\":\"$notify_url\"}";

        return $this->executeCMD($this->control, $timestamp, $type = array(0, 0), $carPlates, $deviceId, 4, $pt104);
    }

    //公共方法
    private function executeCMD($method, $timestamp, $type, $carPlates, $deviceId, $num, $pt104 = NULL)
    {
        $arr = array(
            0 => "app_id=$this->app_id",
            1 => "method=$method",
            2 => "charset=$this->charset",
            3 => "timestamp=$timestamp",
            4 => "version=$this->version",
            5 => "biz_content={\"carPlate\":\"$carPlates\",\"device_id\":\"$deviceId\",\"$type[0]\":\"$type[1]\"}",
            6 => "sign=$this->sign"
        );
        //如果是操作车辆pt104则替换数据
        if ($pt104) {
            $arr[5] = $pt104;
        }
        //拼接请求参数
        $data_string = implode('&', $arr);

        $result = $this->post($data_string, false);

        $json = json_decode($result);
        //判断是否存在对象
        if(!$json){
            return self::ERROR_RETURN;
        }

        //如果接口返回数据有误
        if(!property_exists($json,'code') || !property_exists($json,'msg')
            || !property_exists($json,'r_content') || !property_exists($json,'sign')
        ){
            return self::ERROR_RETURN;
        }
        //单车数据返回
        if (self::E_SUCCESS == $json->code && 1 == $num) {
            return $this->oneCarResult($json->r_content);
        }
        //多车数据返回
        if (self::E_SUCCESS == $json->code && 2 == $num) {
            return $this->manyCarsResult($json->r_content);
        }
        //车辆控制数据返回
        if (self::E_SUCCESS == $json->code && 4 == $num) {
            return $this->operateResult($json->r_content);
        }
        return $json->code;

    }

    //单量车数据返回
    private function oneCarResult($data)
    {
        $arr = array();
        foreach ($data[0] as $k => $v) {
            $arr[$k] = $v;
        }
        return $arr;
    }

    //多车数据返回
    private function manyCarsResult($data)
    {
        $arr = array();
        foreach ($data as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $arr[$k][$k1] = $v1;
            }
        }
        return $arr;
    }

    //车辆操作数据返回
    private function operateResult($data)
    {
        $arr = array();
        foreach ($data as $k => $v) {
            $arr[$k] = $v;
        }
        return $arr;
    }

    /**
     * 向云杉发送post请求
     *
     * @param $data
     * @param bool $log
     * @return bool|mixed
     */
    private function post($data_string, $log = false)
    {
        if (false === $data_string) {
            return false;
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_URL, $this->bathPath);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($data_string));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? $response : false;
    }

    //UUID,可以指定前缀
    public function create_uuid($prefix = "")
    {
        $str = md5(uniqid(mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $prefix . $uuid;
    }
}
