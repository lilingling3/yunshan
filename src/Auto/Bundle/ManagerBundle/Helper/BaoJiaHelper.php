<?php
/**
 * Created by PhpStorm.
 * User: Ma
 * Date: 17/4/25
 * Time: 下午5:05
 */

namespace Auto\Bundle\ManagerBundle\Helper;

class BaoJiaHelper extends AbstractHelper
{
    //api接口host
    private $bathPath = 'http://zykuaiche.com.cn/zcloud/service';
    //私钥
//    private $privateKey = 'a97589d1b74c4ff8af2f606c822d9c49';// test key @todo need change real key
    private $privateKey = 'febe2d6071414fd1a0b532ab0d70f957';// real key

    //车辆请求命令
    const COMMAND_CAR_ATTRIBUTE = 'attributeQuery';
    const COMMAND_CAR_STATUS = 'statusQuery';
    const COMMAND_CAR_ONLINE_STATUS = 'onLineQuery';
    const COMMAND_CAR_FIND = 'findCar';
    const COMMAND_CAR_CLEAR = 'clear';
    const COMMAND_CAR_CHANGE_PLATE = 'plateNum';
    const COMMAND_CONTROL_WINDOW = 'windowControl';
    const COMMAND_CONTROL_DOOR = 'doorControl';
    const COMMAND_CONTROL_POWER = 'electricControl';
    const COMMAND_ORDER_START = 'sendOrder';
    const COMMAND_ORDER_FINISH = 'finishOrder';
    const COMMAND_AREA_ADD = 'areaAdd';
    const COMMAND_AREA_DEL = 'areaDel';
    const COMMAND_SET = 'paramSet';

    //返回结果 系统码 sysCode 值
    const SYS_CODE_SUC = 'suc';//success
    const SYS_CODE_CAR_ID = 'carIdNotFound';
    const SYS_CODE_EXCEPTION = 'exception';

    //返回结果 车辆状态码 rtCode 值
    const RT_CODE_SUC = 0;//成功
    const RT_CODE_FAILED = 1;//失败
    const RT_CODE_DISCONNECT = 3;//设备断开连接
    const RT_CODE_ONLINE_NO_MESSAGE = 4;//设备在线，但无信息返回，有可能即将掉线
    const RT_CODE_REPEAT = 6;//重复的命令


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
        switch ($action) {
            case 'find':
                //鸣笛
                $re = $this->executeCMD($deviceNum, self::COMMAND_CAR_FIND, ['type' => 3]);
                break;
            case 'open':
                //解锁
                $re = $this->executeCMD($deviceNum, self::COMMAND_CONTROL_DOOR, ['type' => 1]);
                break;
            case 'close':
                //锁门
                $re = $this->executeCMD($deviceNum, self::COMMAND_CONTROL_DOOR, ['type' => 2]);
                break;
            case 'off':
                //断电
                $re = $this->executeCMD($deviceNum, self::COMMAND_CONTROL_POWER, ['type' => 2]);
                break;
            case 'on':
                //供电
                $re = $this->executeCMD($deviceNum, self::COMMAND_CONTROL_POWER, ['type' => 1]);
                break;
            case 'window_up':
                //上升车窗
                $re = $this->executeCMD($deviceNum, self::COMMAND_CONTROL_WINDOW, ['type' => 2]);
                break;
            case 'window_down':
                //下降车窗
                $re = $this->executeCMD($deviceNum, self::COMMAND_CONTROL_WINDOW, ['type' => 1]);
                break;
        }

        if($re)
        {
            if(isset($re['rtCode'])&&$re['rtCode']==self::RT_CODE_SUC)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * 单台车辆实时状态查询接口
     *
     * @param $deviceNum
     * @return bool
     */
    public function findCarLocation($deviceNum)
    {
        return $this->executeCMD($deviceNum, self::COMMAND_CAR_STATUS);
    }

    /**
     * 车辆在线状态
     *
     * @param $deviceNum
     * @return bool|mixed
     */
    public function oneCarStatus($deviceNum)
    {
        return $this->executeCMD($deviceNum, self::COMMAND_CAR_ONLINE_STATUS);
    }

    /**
     * @param $deviceNum
     * @param array $setting
     *          heartBeat 心跳间隔 String 可选；单位：秒
     *          sleepTimeInterval 休眠时汇报时 间间隔 String 可选：位置信息上报的 时间间隔，默认 10 分 钟，单位：秒
     *          sleepDistanceInterval 休眠时汇报距 离间隔 String 可选：位置信息上报的 时间间隔，默认 200 米；单位：米
     *          workTimeInterval 工作时时间汇 报间隔 String 可选：位置信息上报的 时间间隔，默认1分钟； 单位：秒
     *          workDistanceInterval 工作时距离汇 报间隔 String 可选：位置信息上报的 时间间隔，默认 200 米；单位：米
     *          waitMaxTime 用户刷身份证 最长等待时间 String 当用户使用身份证刷 卡时，如果超过该值， 则自动判断为失效订 单。单位：秒
     *          maxDistance 停车时，距离最 后一次定位位 置的距离 String 超过该距离，不允许使 用空车牌还车；默认 值：200 米；单位米
     *          settleAccountInterval 通知进行费用 结算时间间隔 String 默认：24 小时；单位： 秒
     *          driveRange 总行驶里程 String 可选：如果设备不能从 OBD 读取到仪表盘上 的总行驶里程，则此时 需要调用改接口设置 总行驶里程，单位：百 米；值只能为整数字符 串。
     * @return bool|mixed
     */
    public function carSetting($deviceNum, $setting)
    {
        return $this->executeCMD($deviceNum, self::COMMAND_SET, $setting);
    }

    /**
     * 车辆订单接口 下发订单
     *
     * @param $deviceNum
     * @return bool
     */
    public function sendOrder($deviceNum)
    {
        $re = $this->executeCMD($deviceNum, self::COMMAND_ORDER_START, ['beginTime' => (new \DateTime())->format('Y-m-d H:i:s')]);

        if ($re) {
            $redis_cmd = $this->redisHelper->createCommand('set', array("bao_jia_order_id_" . $deviceNum, 1));
            $this->redisHelper->executeCommand($redis_cmd);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除订单
     *
     * @param $deviceNum
     * @return bool
     */
    public function deleteOrder($deviceNum)
    {
        $re = $this->executeCMD($deviceNum, self::COMMAND_ORDER_START);
        if ($re) {
            $redis_cmd = $this->redisHelper->createCommand('del', ["bao_jia_order_id_" . $deviceNum]);
            $this->redisHelper->executeCommand($redis_cmd);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 执行请求命令
     *
     * @param $carId
     * @param $cmd
     * @param array $data
     * @param bool $errorReturnFalse
     * @return bool|mixed
     */
    private function executeCMD($carId, $cmd, $data = array(), $errorReturnFalse = true)
    {
        $data['carId'] = $carId;
        $data['cmd'] = $cmd;
        $re = json_decode($this->post($data), true);

        if (!$errorReturnFalse) {
            return $re;
        }

        if ($re['sysCode'] == self::SYS_CODE_SUC) {
            if (isset($re['rtCode'])) {
                switch ($re['rtCode']) {
                    case self::RT_CODE_SUC:
                        return $re;
                    case self::RT_CODE_FAILED:
                        return false;
                    case self::RT_CODE_DISCONNECT:
                        return false;
                    case self::RT_CODE_ONLINE_NO_MESSAGE:
                        return false;
                    case self::RT_CODE_REPEAT:
                        return false;
                }
            }
        }

        return false;
    }

    /**
     * 向宝驾发送post请求
     *
     * @param $data
     * @param bool $log
     * @return bool|mixed
     */
    private function post($data, $log = false)
    {
        $data_string = $this->jsonPartner($data);
        if (false === $data_string) {
            return false;
        }
        $ch = curl_init($this->bathPath);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ? $result : true;
    }

    /**
     * 验证参数是否合法并添加sign返回Json化数据
     *
     * @param $data
     * @return bool|string
     */
    private function jsonPartner($data)
    {

        if (isset($data['carId']) && isset($data['cmd'])) {
            $data['sign'] = $this->getSign($data);
            return json_encode($data);
        } else {
            return false;
        }
    }

    /**
     * 获得签名
     *
     * @param $dataArr
     * @return 签名值
     */
    private function getSign($dataArr)
    {
        $queryStr = '';

        ksort($dataArr);
        foreach ($dataArr as $key => $value) {
            $queryStr .= $key . '=' . $value . '&';
        }
        $signature = substr($queryStr, 0, -1) . $this->privateKey;

        return md5($signature);
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    public function setRedisHelper($redisHelper)
    {
        $this->redisHelper = $redisHelper;
    }
}