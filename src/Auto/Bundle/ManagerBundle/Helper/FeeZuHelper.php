<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/5/17
 * Time: 下午5:05
 */
namespace Auto\Bundle\ManagerBundle\Helper;

class FeeZuHelper extends AbstractHelper{


    //api接口host
    private $bathPath = 'http://api.feezu.cn';
    //私钥
    private $privateKey = 'chengcheng830704';
    //uid
    private $uid = 'YS00001';

    /**
     * 多台车辆实时位置查询接口
     */
    public function NewfindCarsLocation(){
        // 多台车辆接口地址(全部)
        $carApi = '/car/v1/realtimePosition/list';

        $dataArr = [
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
        ];

        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr,true);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return $data['data'] ;

        }else{

            return false;

        }
    }

    /**
     * 多台车辆状态查询
     */
    public function multiCarStatus(){

        // 车辆状态查询接口地址(全部)
        $carApi = '/car/v1/status/list';

        $dataArr=[
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
        ];

        //获得签名
        $sign=$this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $result = $this->curl_post($this->bathPath.$carApi,$dataArr,true);

        $data = substr($result,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return $data['data'] ;

        }else{

            return false;

        }
    }

    /**
     * 单台车辆实时位置查询接口
     */
    public function findCarLocation($deviceNum){
        // 单台车辆接口地址(单台)
        $carApi = '/car/v2/realtimePosition/detail';

        $dataArr = [
            'uid'=>$this->uid,
            'reqTime'=>date('Y-m-d H:i:s'),
            'deviceNum'=>$deviceNum
        ];
        
        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign']=$sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr,true);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return $data['data'] ;

        }else {

            return false;

        }
    }


    /**
     * 多台(全部)车辆实时位置查询接口
     */
    public function findCarsLocation() {
        //多台车辆接口地址
        $carApi = '/car/v1/realtimePosition/detail';

        $dataArr = [
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
        ];

        //获得签名
        $sign = $this->getSign($dataArr, $carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return $data['data'] ;

        }else {

            return false;

        }
    }

    /**
     * 单台车辆历史轨迹回放
     */
    public function oneCarPath($deviceNum,$startTime,$endTime){
        // 单台车辆历史轨迹回放
        $carApi = '/car/v1/historyPosition/detail';

        $dataArr = [
            'uid'=>$this->uid,
            'reqTime'=>date('Y-m-d H:i:s'),
            'deviceNum'=>$deviceNum,
            '$startTime'=>$startTime,
            '$endTime'=>$endTime
        ];

        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data=substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return $data['data'];

        }else{

            return false;
        }
    }


    /**
     * 单台车辆状态查询
     */
    public function oneCarStatus($deviceNum){

        // 车辆状态查询接口地址(单台)
        $carApi = '/car/v2/status/detail';

        $dataArr=[
            'uid'=>$this->uid,
            'reqTime'=>date('Y-m-d H:i:s'),
            'deviceNum'=>$deviceNum,
        ];

        //获得签名
        $sign=$this->getSign($dataArr,$carApi);

        $dataArr['sign']=$sign;

        $result = $this->curl_post($this->bathPath.$carApi,$dataArr,true);

        $data = substr($result,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return $data['data'] ;

        }else{

            return false;

        }
    }

    /**
     * 车辆订单接口 下发订单
     */

    public function sendOrder($deviceNum){

        $takeCarTime=(new \DateTime())->format('Y-m-d H:i:s');

        $returnCarTime = (new \DateTime())->modify("+24 hours")->format('Y-m-d H:i:s');


        $redis_cmd = $this->redisHelper->createCommand('get',["fee_zu_order_id_".$deviceNum]);

        $redisData = $this->redisHelper->executeCommand($redis_cmd);


        if(empty($redisData) ){

        // 车辆 下发订单接口地址(单台)
        $carApi = '/order/v1/send';

        $dataArr = [
            'uid'=>$this->uid,
            'reqTime'=>date('Y-m-d H:i:s'),
            'deviceNum'=>$deviceNum,
            'takeCarTime'=>$takeCarTime,
            'returnCarTime'=>$returnCarTime
        ];


        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        //接口访问是否成功
        if($data['status']['code']=='ic0001'){

                $trueData = [];

                $trueData['orderId']=$data['data']['orderId'];

                $trueData['deviceNum']=$deviceNum;

                $trueData = json_encode($trueData);

                $redis_cmd = $this->redisHelper->createCommand('set',array("fee_zu_order_id_".$deviceNum,$trueData));

                $this->redisHelper->executeCommand($redis_cmd);

            }else {

                return json_encode(array('msg' => '接口访问失败'));

            }
        }else{

            return json_encode(array('msg'=>'订单已存在！'));

        }

    }

    /**
     * 删除订单
     */
    public function deleteOrder($deviceNum){


        $redis_cmd = $this->redisHelper->createCommand('get',["fee_zu_order_id_".$deviceNum]);

        $redisData = $this->redisHelper->executeCommand($redis_cmd);

        $data = json_decode( $redisData,true);

        if(!empty($redisData)){

            // 删除订单接口地址(单台)
            $carApi = '/order/v1/delete';

            $dataArr=[
                'uid' => $this->uid,
                'reqTime' => date('Y-m-d H:i:s'),
                'deviceNum' => $deviceNum,
                'orderId' => $data['orderId']
            ];

            //获得签名
            $sign = $this->getSign($dataArr,$carApi);

            $dataArr['sign'] = $sign;

            $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

            $data = substr($returnMsg,0,-28);

            $data = json_decode($data,true);

            if($data['status']['code'] == 'ic0001'){

                $this->takeCar($deviceNum,$data['orderId']);

                $redis_cmd = $this->redisHelper->createCommand('del',["feeZu_order_id_".$deviceNum]);

                $this->redisHelper->executeCommand($redis_cmd);

            }else{

                return false;
            }

        }else{

            return false;
        }
    }

    /**
     *  @param $action远程控制动作：1鸣笛 取车 find 解锁 open 锁门 close 还车 on  off gps
     *  @param $lpNum (string) 车牌号
     *  @param $deviceNum (int)设备号
     *  @param $orderId (string) 订单号
     */
    public function operate2($deviceNum,$action,$password=null){


        $redis_cmd =  $this->redisHelper->createCommand('get',["fee_zu_order_id_".$deviceNum]);


        $redisData =  $this->redisHelper->executeCommand($redis_cmd);


        if(empty($redisData)) return false;

        $deviceData = json_decode($redisData,true);

        $orderId = $deviceData['orderId'];

        switch($action){

            case 'find':

                //鸣笛
                echo $this->blow($deviceNum,$orderId);

                break;

            case 'open':
                //解锁
                return $this->deblocking($deviceNum,$orderId);

                break;

            case 'close':
                //锁门
                return $this->lockCar($deviceNum,$orderId);

                break;

            case 'return':
                //还车
                return $this->returnCar($deviceNum,$orderId);

                break;

        }

    }

    /**
     * 鸣笛接口
     */
    public function blow($deviceNum,$orderId){

        // 鸣笛接口地址(单台)
        $carApi = '/order/v1/whistle';

        $dataArr=[
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
            'deviceNum' => $deviceNum,
            'orderId' => $orderId
        ];


        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return true;

        } else {

            return false;

        }
    }

    /**
     * 取车接口添加
     */
    public function takeCar($deviceNum,$orderId){
        // 取车接口地址(单台)
        $carApi = '/order/v1/takeCar';

        $dataArr=[
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
            'deviceNum' => $deviceNum,
            'orderId' => $orderId
        ];

        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);


        if($data['status']['code'] == 'ic0001'){

            return true;

        }else{

            return false;
        }
    }

    /**
     * 解锁接口
     */
    public function deblocking($deviceNum,$orderId){

        // 解锁接口地址(单台)
        $carApi = '/order/v1/unlockCar';

        $dataArr=[
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
            'deviceNum' => $deviceNum,
            'orderId' => $orderId
        ];

        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return true;

        }else{

            return false;

        }
    }

    /**
     * 锁门接口
     */
    public function lockCar($deviceNum,$orderId){

        // 锁门接口地址(单台)
        $carApi = '/order/v1/unlockCar';

        $dataArr = [
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
            'deviceNum' => $deviceNum,
            'orderId' => $orderId
        ];

        //获得签名
        $sign = $this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);

        if($data['status']['code'] == 'ic0001'){

            return true;

        }else {

            return false;

        }
    }

    /**
     * 还车接口
     */
    public function returnCar($deviceNum){

        $redis_cmd = $this->redisHelper->createCommand('get',["fee_zu_order_id_".$deviceNum]);

        $redisData = $this->redisHelper->executeCommand($redis_cmd);

        $data = json_decode( $redisData,true);

        if(!empty($data['orderId'])){

            // 锁门接口地址(单台)
            $carApi = '/order/v1/returnCar';

            $dataArr = [
                'uid' => $this->uid,
                'reqTime' => date('Y-m-d H:i:s'),
                'deviceNum' => $deviceNum,
                'orderId' => $data['orderId']
            ];

            //获得签名
            $sign = $this->getSign($dataArr,$carApi);

            $dataArr['sign'] = $sign;

            $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

            $data = substr($returnMsg,0,-28);

            $data = json_decode($data,true);

            if($data['status']['code'] == 'ic0001'){

                return true;

            }else{

                return false;

            }
        }
    }

    /**
     * 车辆远程控制接口
     *
     * @param string  $deviceNum 设备号
     * @param integer $action    操作（1.鸣笛 2.开门 3.关门 4.断电 5.供电 6.供断电恢复默认）
     * @param string  $password
     * @return bool
     */
    public function operate($deviceNum,$action,$password=''){

        switch($action){
            case 'find':
                //鸣笛
                 $action=1;

                break;
            case 'open':
                //解锁
                 $action=2;

                break;
            case 'close':
                //锁门
                  $action=3;

                break;

            case 'off':
                //断电
                 $action=4;

                break;
            case 'on':
                //供电
                 $action=5;

                break;
            case 'reset':
                //初始化
                $action=6;

                break;
            default:
                return false;
                break;
        }



        $carApi='/car/v1/control';

        $dataArr=[
            'uid' => $this->uid,
            'reqTime' => date('Y-m-d H:i:s'),
            'deviceNum' => $deviceNum,
            'action' => $action
        ];

        $sign=$this->getSign($dataArr,$carApi);

        $dataArr['sign'] = $sign;

        $returnMsg = $this->curl_post($this->bathPath.$carApi,$dataArr);

        $data = substr($returnMsg,0,-28);

        $data = json_decode($data,true);


        if($data['status']['code'] == 'ic0001'){

            return true;

        }else{

            return false;
        }
    }

    /**
     * 获得签名
     *
     * @param array  $dataArr 请求数据
     * @param string $carApi  请求连接
     * @return 签名值
     */
    private function getSign($dataArr,$carApi){

        ksort($dataArr);

        // 查询参数字符串
        $queryStr = '';
        foreach ($dataArr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }
        // 删除最后一位'&'符号
        $queryStr = trim($queryStr, '&');

        // 拼接签名串
        $signature = urlencode($carApi).urlencode($queryStr);

        //生成签名

        $signValue = $this->getSignature($signature, $this->privateKey);

        return $signValue;
    }

    /**
     * curl POST
     *
     * @param string $url 接收POST数据的url
     * @param array $data 需要POST的数据
     * @param bool $log   是否记录失败记录
     * @return mixed
     */
    private function curl_post($url,$data,$log = false) {

        $curlObj = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            // CURLOPT_POST => TRUE, //使用post提交
            CURLOPT_RETURNTRANSFER => TRUE, //接收服务端范围的html代码而不是直接浏览器输出
            CURLOPT_BINARYTRANSFER => TRUE,
            CURLOPT_TIMEOUT => $log ? 15 :4,
            // CURLOPT_HTTPHEADER => FormatHeader($url, $data),
            // CURLOPT_POSTFIELDS => $data //post的数据
            CURLOPT_POSTFIELDS => http_build_query($data) //post的数据
        );

        curl_setopt_array($curlObj, $options);
        $response = curl_exec($curlObj);

        if($log && false === $response){
            $error = curl_error($curlObj);
            $this->error = $error;
            $path =  __DIR__;
            $path = substr($path, 0, -36);
            $curl_root = $path.'app/logs/curl_error.txt';
            $time = date('Y-m-d H:i:s');
            $txt = "[$time] $this->error [$url]\n";
            file_put_contents($curl_root, "$txt", FILE_APPEND);
        }
        curl_close($curlObj);
        return $response;
    }

    /**
     * @brief 使用HMAC-SHA1算法生成oauth_signature签名值
     *
     * @param $key 密钥
     * @param $str 源串
     *
     * @return 签名值
     */
    private function getSignature($str, $key) {
        $signature = "";
        if (function_exists('hash_hmac')) {
            $signature = base64_encode(hash_hmac("sha1", $str, $key, true));
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                'H*', $hashfunc(
                    ($key ^ $opad) . pack(
                        'H*', $hashfunc(
                            ($key ^ $ipad) . $str
                        )
                    )
                )
            );
            $signature = base64_encode($hmac);
        }
        return $signature;
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    public function setRedisHelper($redisHelper){

        $this->redisHelper = $redisHelper;

    }
}
