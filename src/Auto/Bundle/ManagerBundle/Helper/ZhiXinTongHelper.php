<?php
/**
 * Created by PhpStorm.
 * User: Tang
 * Date: 17/5/23
 * Time: 上午11:40
 */

namespace Auto\Bundle\ManagerBundle\Helper;

class ZhiXinTongHelper extends AbstractHelper
{

    const URL_CAR_STATUS = 'GetCarStatus.ashx';// include URL_CARS_STATUS
    const URL_CARS_STATUS = 'GetCarsStatus.ashx';
    const URL_CAR_LOCATION = 'GetGpsInfo.ashx';
    const URL_CAR_CONTROL = 'RemoteControlCarsNew.ashx';

    const URL_CAR_SET_PWD = 'SetControlPwd.ashx';
    const URL_CAR_DRIVING_TRACK = 'GetDrivingTrack.ashx';
    const URL_CAR_ONLINE = 'RunRecordsInfo.ashx';

    const CAR_CONTROL_OPEN = '500';
    const CAR_CONTROL_CLOSE = '501';
    const CAR_CONTROL_FIND = '400';
    const CAR_CONTROL_OFF = '100';
    const CAR_CONTROL_ON = '101';

//    const CAR_CONTROL_MUTE_ON = 'C00';
//    const CAR_CONTROL_MUTE_CLOSE = 'C01';
//    const CAR_CONTROL_OFF_CLOSE = 'S100';
//    const CAR_CONTROL_ON_OPEN = 'S101';

    static $CAR_ADMIN_PWD = '684153';
    const BASE_URL = 'http://221.123.179.91:9819/zzZC/';
    const PLATFORM = 'YUSHAN';
    const MD5KEY = 'pwdys39785';
    const AES_KEY = 'NmQA1kCNN7Yzl26+PN8+kQ==';

    const ID_SID = 'EFAC4D89-0F9E-4045-B80D-8866F82A4996';
    const CUSTOMER_FLAG = '000';

    const RT_CODE_SUC = 1;

    // 测试车辆： 京QM85H5  江淮IEV5  白色  sn:250006064 code:093990

    /**
     * 车辆远程控制接口
     *
     * @param $deviceNum
     * @param $action
     * @param $password
     * @return bool
     */
    public function operate($deviceNum, $action, $password = null)
    {
        $re = null;
        $actionCode = null;
        switch ($action) {
            case 'find':
                //鸣笛
                $actionCode = self::CAR_CONTROL_FIND;
                break;
            case 'open':
                //解锁
                $actionCode = self::CAR_CONTROL_OPEN;
                break;
            case 'close':
                //锁门
                $actionCode = self::CAR_CONTROL_CLOSE;
                break;
            case 'off':
                //断电
                $actionCode = self::CAR_CONTROL_OFF;
                break;
            case 'on':
                //供电
                $actionCode = self::CAR_CONTROL_ON;
                break;
        }

        if ($actionCode == null) {
            return false;
        }

        $re = $this->carControl($deviceNum, $actionCode);
        $re = json_decode($re, true);

        if (isset($re['result']) && $re['result'] == self::RT_CODE_SUC) {
            return true;
        }

        return false;
    }

    /**
     * @param $deviceNum string
     * @param $actionCode
     * @return bool|mixed
     */
    private function carControl($deviceNum, $actionCode)
    {
        $device = explode(self::SEPARATOR, $deviceNum);
        if (count($device) < 2)
            return false;

        $parameters = [
            'SN' => $device[0],
            'Code' => $device[1],
            'Value' => $actionCode,
            'TimeStamp' => $this->microTime(),
            'platform' => self::PLATFORM,
            'CustomerFlag' => self::CUSTOMER_FLAG,
        ];
        $checkInfo = $this->getSign($parameters);
        $parameters['checkInfo'] = $checkInfo;
        $re = $this->post(self::BASE_URL . self::URL_CAR_CONTROL, $parameters);
        return $re;
    }

    /**
     * 单台车辆实时状态查询接口
     *
     * @param $deviceNum string|array
     * @return array
     */
    public function findCarLocation($deviceNum)
    {
        return $this->connectZhiXinTong($deviceNum, self::URL_CAR_STATUS);
    }

    /**
     * 单台车辆实时状态查询接口
     *
     * @param $deviceNum string|array
     * @return array
     */
    public function getCarAltitude($deviceNum)
    {
        return $this->connectZhiXinTong($deviceNum, self::URL_CAR_LOCATION);
    }

    /**
     * @param $deviceNum
     * @param $secondUrl
     * @return bool|mixed|string
     */
    private function connectZhiXinTong($deviceNum, $secondUrl)
    {
        $sn = '';
        if (is_array($deviceNum)) {// 数组boxId 拼接串
            foreach ($deviceNum as $value) {
                $device = explode(self::SEPARATOR, $value);
                if (count($device) < 2)
                    continue;
                else {
                    $sn = $device[0] . ',';
                }
            }
        } else {// 字符串boxId
            $device = explode(self::SEPARATOR, $deviceNum);
            if (count($device) >= 2)
                $sn = $device[0];
        }

        if (empty($sn))
            return false;

        if (substr($sn, -1) == ',')// 以 “,” 结尾，删除结尾 “,”
        {
            $sn = substr($sn, 0, -1);
        }

        $parameters = [
            'SN' => $sn,
            'customerFlag' => self::CUSTOMER_FLAG,
        ];

        $re = $this->post(self::BASE_URL . $secondUrl, $parameters);
        $re = iconv("GB2312//IGNORE", "UTF-8", $re);

        return $re;
    }

    /**
     * @param $deviceNum string
     * @param $timeStampStart string 毫秒时间戳
     * @param $timeStampEnd string 毫秒时间戳
     * @return bool|mixed|string
     */
    public function carTrack($deviceNum, $timeStampStart, $timeStampEnd)
    {
        $device = explode(self::SEPARATOR, $deviceNum);
        if (count($device) >= 2)
            $sn = $device[0];
        if (empty($sn))
            return false;
        $parameters = [
            'sn' => $sn,
            'customerFlag' => self::CUSTOMER_FLAG,
            'startTime' => $timeStampStart,
            'endTime' => $timeStampEnd,
        ];
        $re = $this->post(self::BASE_URL . self::URL_CAR_DRIVING_TRACK, $parameters);
        $re = iconv("GB2312//IGNORE", "UTF-8", $re);
        return $re;
    }

    /**
     * @param $deviceNum string
     * @param $timeStampStart string time like 2017-06-17 00:00
     * @param $timeStampEnd string time like 2017-06-17 00:00
     * @return bool|mixed|string
     */
    public function carOnlineTrack($deviceNum, $timeStampStart, $timeStampEnd)
    {
        $device = explode(self::SEPARATOR, $deviceNum);
        if (count($device) >= 2)
            $sn = $device[0];
        if (empty($sn))
            return false;
        $parameters = [
            'sn' => $sn,
            'customerFlag' => self::CUSTOMER_FLAG,
            'startTime' => $timeStampStart,
            'endTime' => $timeStampEnd,
            'Idsid' => self::ID_SID,
        ];
        $re = $this->post(self::BASE_URL . self::URL_CAR_ONLINE, $parameters);
        $re = iconv("GB2312//IGNORE", "UTF-8", $re);
        return $re;
    }

    /**
     * 向宝驾发送post请求
     *
     * @param $url
     * @param $data
     * @param bool $log
     * @return bool|mixed
     */
    private function post($url, $data, $log = false)
    {
        file_put_contents('/data/logs/debug.log', $url . "\n", FILE_APPEND);
        file_put_contents('/data/logs/debug.log', json_encode($data) . "\n", FILE_APPEND);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);
        curl_close($ch);
        file_put_contents('/data/logs/debug.log', json_encode($ret) . "\n", FILE_APPEND);
        return $ret;
    }

    /**
     * 获得签名
     *
     * @param $parameters
     * @return 签名值
     */
    private function getSign($parameters)
    {
        $queryStr = '';
        foreach ($parameters as $key => $value) {
            $queryStr .= $key . '=' . $value . '&';
        }
        $queryStr = substr($queryStr, 0, -1);
        $combine = $queryStr . self::MD5KEY;
        $md5 = md5($combine);

        return $md5;
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    private function microTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (int)(($usec + $sec) * 1000);
    }
}