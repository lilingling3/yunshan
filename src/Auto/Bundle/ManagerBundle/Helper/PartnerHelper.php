<?php
/**
 * Created by PhpStorm.
 * User: Ma
 * Date: 17/4/25
 * Time: 下午5:05
 */

namespace Auto\Bundle\ManagerBundle\Helper;

class PartnerHelper extends AbstractHelper
{
    private $curlHelper;
    private $redisHelper;
    private $cacheHelper;

    const SEPARATOR = '|';
    const GET_RESULT = true; // @default data: false

    const PARTNER_XIAOER = '266389';
    const CONNECT_PARTNER_LOG = false;

    const INTERFACE_URL = array(
        self::PARTNER_XIAOER => [  // 小二
            'name' => 'XiaoEr',
            'keyID' => 'jiabei',
            'key' => '61a4823ghkhu49231',
            'base' => 'http://www.xiaoerzuche.com/hourlyRate',
            'stations' => '/api/partner/v1.0/stations',
            'online' => '/api/partner/v1.0/cars/:carNo/online',
            'offline' => '/api/partner/v1.0/cars/:carNo/offline',
            'unrental' => '/api/partner/v1.0/cars/:carNo/unrental',
            'rentalable' => '/api/partner/v1.0/cars/:carNo/rentalable'
        ]
    );


    /**
     * @param $partnerCode
     * @return array carIds
     */
    public function getStationIdsByPartnerCode($partnerCode)
    {
        $this->cacheHelper->cachePartnerStations($partnerCode);

        $redis_cmd = $this->redis->createCommand(self::CMD_HGETALL, [self::H_PARTNER_STATION_ID_TO_STATION_ID_ . $partnerCode]);
        $stationIds = $this->redisHelper->executeCommand($redis_cmd);

        return $stationIds;
    }
    /**
     * @param $partnerCode
     * @return array carIds
     */
    public function getRentalCarIdsByPartnerCode($partnerCode)
    {
        $this->cacheHelper->cachePartnerVisibleCars();

        $redis_cmd = $this->redis->createCommand(self::CMD_SMEMBERS, [self::M_PARTNER_VISIBLE_CARS_ . $partnerCode]);
        $carIds = $this->redisHelper->executeCommand($redis_cmd);

        return $carIds;
    }
    /**
     * @param $partnerCode
     * @return array carIds
     */
    public function getVisibleCarPlateByPartnerCode($partnerCode)
    {
        $carIds = $this->getRentalCarIdsByPartnerCode($partnerCode);

        $cars = $this->cacheHelper->getCarPlatesByIDs($carIds);
        return $cars;
    }

    public function getStations($partnerCode)
    {
        $data = null;
        switch ($partnerCode) {
            case self::PARTNER_XIAOER:
                $data = $this->getStationsFromXiaoEr();
                break;
            default:
                //@todo log something
                break;
        }
        return $data;
    }

    private function getStationsFromXiaoEr()
    {
        $url = self::INTERFACE_URL[self::PARTNER_XIAOER]['base'] . self::INTERFACE_URL[self::PARTNER_XIAOER]['stations'] . '?';

        $parameters = [
            'key' => self::INTERFACE_URL[self::PARTNER_XIAOER]['key'],
            'partner' => self::INTERFACE_URL[self::PARTNER_XIAOER]['keyID'],
            'timestamp' => $this->microTime(),
        ];

        $connectURL = $url . $this->getConnectURL($parameters);
        $re = $this->curlHelper->get_url_contents($connectURL);

        return $re;
    }

    /**
     * @param array $fields : 至少包含以下数据 [ 'carId' =>1  'operator'=>1  'reason' =>1 'remark' =>1 'stationId'=> 1];
     * @return string
     */
    public function carOnline($fields)
    {
        $partners = $this->getPartnersNeedToCall($fields['carId']);

        file_put_contents('/data/logs/debug.log', '[' . date('y/m/d H:i:s') . ']:caronline:'
            . $this->microTime() . ': [url]: online [data]:' . json_encode($partners) . "\n", FILE_APPEND);
        $re = '';
        foreach ($partners as $partner) {
            $re .= $this->callPartner($partner, 'carOnlineCall', $fields);
        }

        return $re;
    }

    private function getPartnersNeedToCall($carId)
    {
        $this->cacheHelper->cachePartnerVisibleCars();

        $redis_cmd = $this->redisHelper->createCommand('keys', array('partner_visible_cars_*'));
        $partners = $this->redisHelper->executeCommand($redis_cmd);

        $partnerCodes = array();
        foreach ($partners as $partner) {
            $redis_cmd = $this->redisHelper->createCommand('SISMEMBER', array($partner, $carId));
            $re = $this->redisHelper->executeCommand($redis_cmd);
            if (1 == $re) {
                $partnerCode = substr($partner, 21);
                $partnerCodes[] = $partnerCode;
            }
        }
        return $partnerCodes;
    }

    /**
     * 统一任务分发
     *
     * @param $partnerCode
     * @param $fun
     * @param $fields
     * @return int
     */
    private function callPartner($partnerCode, $fun, $fields)
    {
        $partnerEnable = $this->container->getParameter('partner_enable');

        if (!$partnerEnable)
            return 'call none partner, please check parameter';

        $re = -2;
        switch ($partnerCode) {
            case self::PARTNER_XIAOER:
                $function = $fun . self::PARTNER_XIAOER;

                if (!isset($fields['carNo'])) {
                    $fields['carNo'] = $this->cacheHelper->getCarPlatesByID($fields['carId']);
                }

                $this->changeStationId($fields, self::PARTNER_XIAOER);// 小二station数据段特殊处理

                $re = $this->$function($fields);
                break;
            default:
                //@todo log something
                break;
        }
        return $re;
    }

    /**
     * 更改数据中的station id 为第三方station id
     *
     * @param $fields
     * @param $partnerCode
     */
    private function changeStationId(&$fields, $partnerCode)
    {
        if (isset($fields['stationId'])) {
            $fields['stationId'] = $this->cacheHelper->getPartnerStationIdByStationIdFromCache($fields['stationId'], $partnerCode);
        }
        if (isset($fields['backRentalStation'])) {
            $fields['backRentalStation'] = $this->cacheHelper->getPartnerStationIdByStationIdFromCache($fields['backRentalStation'], $partnerCode);
        }
        if (isset($fields['rentalStation'])) {
            $fields['rentalStation'] = $this->cacheHelper->getPartnerStationIdByStationIdFromCache($fields['rentalStation'], $partnerCode);
        }
    }

    private function carOnlineCall266389($fields)
    {
        if (isset($fields['notCall']) && $fields['notCall'] == self::PARTNER_XIAOER) {
            return true;
        }
        $parameters = array(
            'carNo' => $fields['carNo'],
            'key' => self::INTERFACE_URL[self::PARTNER_XIAOER]['key'],
            'operator' => $fields['operator'],
            'partner' => self::INTERFACE_URL[self::PARTNER_XIAOER]['keyID'],
            'reason' => $fields['reason'],
            'remark' => $fields['remark'],
            'siteId' => $fields['stationId'],
            'timestamp' => $this->microTime(),
        );

        $url = self::INTERFACE_URL[self::PARTNER_XIAOER]['base'] . self::INTERFACE_URL[self::PARTNER_XIAOER]['online'];

        $fields['carNo'] = urlencode($fields['carNo']);
        $url = str_replace(':carNo', $fields['carNo'], $url);

        $data = $this->getPutJsonData($parameters);

        $re = $this->curlHelper->do_put($url, $data, self::GET_RESULT, self::CONNECT_PARTNER_LOG);

        return $re;
    }

    /**
     * @param array $fields : 至少包含以下数据 [ 'carId' =>1  'operator'=>1  'reason' =>1 'remark' =>1 'stationId'=> 1, 'status'=>1];
     * @return string
     */
    public function carOnOffLine($fields)
    {
        $status = $fields['status'];
        unset($fields['status']);

        if ($status == 1) {
            return $this->carOnline($fields);
        } elseif ($status == 0) {
            return $this->carOffline($fields);
        }
    }

    /**
     * @param array $fields : 至少包含以下数据 [ 'carId' =>1  'operator'=>1  'reason' =>1 'remark' =>1 'stationId'=> 1];
     * @return string
     */
    public function carOffline($fields)
    {
        $partners = $this->getPartnersNeedToCall($fields['carId']);

        $re = '';
        foreach ($partners as $partner) {
            $re .= $this->callPartner($partner, 'carOfflineCall', $fields);
        }

        return $re;
    }

    private function carOfflineCall266389($fields)
    {
        if (isset($fields['notCall']) && $fields['notCall'] == self::PARTNER_XIAOER) {
            return true;
        }
        $parameters = array(
            'carNo' => $fields['carNo'],
            'key' => self::INTERFACE_URL[self::PARTNER_XIAOER]['key'],
            'operator' => $fields['operator'],
            'partner' => self::INTERFACE_URL[self::PARTNER_XIAOER]['keyID'],
            'reason' => $fields['reason'],
            'remark' => $fields['remark'],
            'siteId' => $fields['stationId'],
            'timestamp' => $this->microTime(),
        );

        $url = self::INTERFACE_URL[self::PARTNER_XIAOER]['base'] . self::INTERFACE_URL[self::PARTNER_XIAOER]['offline'];

        $fields['carNo'] = urlencode($fields['carNo']);
        $url = str_replace(':carNo', $fields['carNo'], $url);

        $data = $this->getPutJsonData($parameters);
        $re = $this->curlHelper->do_put($url, $data, self::GET_RESULT, self::CONNECT_PARTNER_LOG);

        return $re;
    }

    /**
     * @param array $fields : 至少包含以下数据 [ 'carId' =>1 'orderId'=>1  'rentalStation' =>1 'backRentalStation' =>1];
     * @return string
     */
    public function carUnRental($fields)
    {
        $partners = $this->getPartnersNeedToCall($fields['carId']);

        $re = '';
        foreach ($partners as $partner) {
            $re .= $this->callPartner($partner, 'carUnRentalCall', $fields);
        }

        return $re;
    }

    private function carUnRentalCall266389($fields)
    {
        if (isset($fields['notCall']) && $fields['notCall'] == self::PARTNER_XIAOER) {
            return true;
        }
        $parameters = array(
            'carNo' => $fields['carNo'],
            'key' => self::INTERFACE_URL[self::PARTNER_XIAOER]['key'],
            'orderId' => $fields['orderId'],
            'partner' => self::INTERFACE_URL[self::PARTNER_XIAOER]['keyID'],
            'rentalAddressId' => $fields['rentalStation'],
            'returnAddressId' => $fields['backRentalStation'],
            'timestamp' => $this->microTime(),
        );

        $url = self::INTERFACE_URL[self::PARTNER_XIAOER]['base'] . self::INTERFACE_URL[self::PARTNER_XIAOER]['unrental'];

        $fields['carNo'] = urlencode($fields['carNo']);
        $url = str_replace(':carNo', $fields['carNo'], $url);

        $data = $this->getPutJsonData($parameters);

        $re = $this->curlHelper->do_put($url, $data, self::GET_RESULT, self::CONNECT_PARTNER_LOG);

        return $re;
    }

    /**
     * @param array $fields : 至少包含以下数据 [ 'carId' =>1 'orderId'=>1 'backRentalStation' =>1];
     * @return string
     */
    public function carRentalAble($fields)
    {
        $partners = $this->getPartnersNeedToCall($fields['carId']);

        $re = '';
        foreach ($partners as $partner) {
            $re .= $this->callPartner($partner, 'carRentalAbleCall', $fields);
        }

        return $re;
    }

    private function carRentalAbleCall266389($fields)
    {
        if (isset($fields['notCall']) && $fields['notCall'] == self::PARTNER_XIAOER) {
            return true;
        }
        $parameters = array(
            'carNo' => $fields['carNo'],
            'orderId' => $fields['orderId'],
            'key' => self::INTERFACE_URL[self::PARTNER_XIAOER]['key'],
            'partner' => self::INTERFACE_URL[self::PARTNER_XIAOER]['keyID'],
            'returnAddressId' => $fields['backRentalStation'],
            'timestamp' => $this->microTime(),
        );

        $url = self::INTERFACE_URL[self::PARTNER_XIAOER]['base'] . self::INTERFACE_URL[self::PARTNER_XIAOER]['rentalable'];

        $fields['carNo'] = urlencode($fields['carNo']);
        $url = str_replace(':carNo', $fields['carNo'], $url);

        $data = $this->getPutJsonData($parameters);

        $re = $this->curlHelper->do_put($url, $data, self::GET_RESULT, self::CONNECT_PARTNER_LOG);

        return $re;
    }

    /**
     * 获取PUT形式加入验签的URL
     *
     * @param array $parameters 需要发送到参数
     * @return string               Get形式URL连接
     */
    public function getPutJsonData($parameters)
    {
        $sign = $this->getSign($parameters);
        $parameters['sign'] = $sign;
        unset($parameters['key']);
        unset($parameters['carNo']);
        return json_encode($parameters);
    }

    /**
     * 获取Get形式加入验签的URL
     *
     * @param array $parameters 需要发送到参数
     * @return string               Get形式URL连接
     */
    private function getConnectURL($parameters)
    {
        $connectURL = '';
        foreach ($parameters as $key => $value) {
            if ($key == 'key')
                continue;
            $connectURL .= $key . '=' . $value . '&';
        }
        $connectURL .= 'sign=' . $this->getSign($parameters);

        return $connectURL;
    }

    /**
     * 根据需加密参数获取签名  小二专用
     *
     * @param array $parameters 需要验证加密的传入参数数组
     * @return string           签名
     */
    private function getSign($parameters)
    {
        $queryStr = '';
        foreach ($parameters as $key => $value) {
            $queryStr .= $value;
        }

        $md5 = md5($queryStr);

        return $md5;
    }

    /**
     * 获取整型13位毫秒级时间戳
     *
     * @return int
     */
    private function microTime()
    {

        list($usec, $sec) = explode(" ", microtime());

        return (int)(($usec + $sec) * 1000);
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    public function setRedisHelper($redisHelper)
    {
        $this->redisHelper = $redisHelper;
    }

    public function setCacheHelper($cacheHelper)
    {
        $this->cacheHelper = $cacheHelper;
    }
}