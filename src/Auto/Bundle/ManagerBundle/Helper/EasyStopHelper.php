<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/5/16
 * Time: 上午11:07
 */
namespace Auto\Bundle\ManagerBundle\Helper;

class EasyStopHelper extends AbstractHelper{

    //停简单合作账号

//    private $patner='b63d311f150448a29a1a7db410d99fb6';
    private $patner='e1095a524b5d496fb8ead808b46bb038';
    //掩码key

//    private $key='8cf4e2d005e8470a92741040bd2823cd';
    private $key='4f074ecfd35b47a281e84b654ef00a1b';
    //字符集
    private $charset='utf-8';

    //加密方式
    private $encrypt='md5';

    //接口访问host URL
//    private $hostUrl='http://testapi.tingjiandan.com/openapi/gateway';
    private $hostUrl='http://api.tingjiandan.com/openapi/gateway';

    //版本号
    private $version='1.0';


    /**
     * 查询停车场基本信息
     * @param $parkId (string) 停车场id
     * @return string
     */
    public function getStopMsg($parkId){

        //接口查询访问方法
        $service='groups.gcOut.selectRelParkDetailInfoList';

        if(!empty($parkId)){


                //查询该停车场的基本信息

                $date=date('Y-m-d H:i:s');

                $dataArr=[
                    'charset'=>$this->charset,
                    'partner'=>$this->patner,
                    'version'=>$this->version,
                    'service'=>'groups.gcOut.selectRelParkDetailInfoList',
                    'psrkId' =>$parkId,
                    'timestamp'=>$date,

                ];

                //获得签名
                $sign=$this->getSign($dataArr);

                $data = '{
                "charset": "'.$this->charset.'",
                "signType": "'.$this->encrypt.'",
                "partner": "'.$this->patner.'",
                "version": "'.$this->version.'",
                "service": "groups.gcOut.selectRelParkDetailInfoList",
                "sign": "'.$sign.'",
                "parkId":"'.$parkId.'",
                "timestamp": "'.$date.'"

                }';

                //访问接口获得数据
                $returnMsg = $this->sendString($data);

                $data=json_decode($returnMsg,true);

                if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                    return $data['dataList'];

                }

                    return $data['returnMsg'];


        }else{
            return false;
        }


    }


    /**
     * 修改车辆组信息
     * @param $gcCargroupId (string) 车辆组id
     * @param $name (string)修改车辆组的名字
     * @param $code (string)备注
     * @param $content (string)内容
     */
    public function alterCarGroupMsg($gcCargroupId,$name,$code,$content){

        if(!empty($gcCargroupId)){

            //修改逻辑根据产品开发需求再加

            $date=date('Y-m-d H:i:s');

            $dataArr=[

                'charset'=>$this->charset,
                'partner'=>$this->patner,
                'service'=>'groups.gcOut.updateCarGroupSelective',
                'timestamp'=>$date,
                'content'=>$content,
                'gcCargroupId'=>$gcCargroupId,
                'name'=>$name,
                'code'=>$code,

            ];

            //获得签名
            $sign=$this->getSign($dataArr);

            $data = '{
            "charset":"'.$this->charset.'",
            "partner": "'.$this->patner.'",
            "service": "groups.gcOut.updateCarGroupSelective",
            "sign": "'.$sign.'",
            "gcCargroupId":"'.$gcCargroupId.'",
            "name":"'.$name.'",
            "code":"'.$code.'",
            "content":"'.$content.'",
            "signType":"'.$this->encrypt.'",
            "timestamp": "'.$date.'",
            "version": "'.$this->version.'"
            }';


            $returnMsg = $this->sendString($data);


            $data=json_decode($returnMsg,true);

            if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                return true;

            }

            return $data['returnMsg'];



        }else{
            return false;
        }


    }


    /**
     * 添加车辆
     * @param $carNumList (array) 添加车辆时的车牌号数组
     * @param $groupId (string)添加车辆时 添加车辆车辆组id
     *
     */
    public function addCar($carNumList,$groupId){

        $date=date('Y-m-d H:i:s');

        if(!empty($carNumList) && !empty($groupId)){

            $array=$carNumList;

            $carStr='';
            foreach($array as $a){

                $carStr.="'".$a."'".",";

            }

            $str=trim($carStr,',');

            $carNumstr='['.$str.']';

            $dataArr=[

                'charset'=>$this->charset,
                'partner'=>$this->patner,
                'version'=>$this->version,
                'service'=>'groups.gcOut.addCarGroupCarBatch',
                'timestamp'=>$date,
                'carNumList'=>$carNumstr,
                'groupId'=>$groupId

            ];
            //获得签名
            $sign=$this->getSign($dataArr);

            $data = '{
            "charset": "'.$this->charset.'",
            "version": "'.$this->version.'",
            "partner": "'.$this->patner.'",
            "service": "groups.gcOut.addCarGroupCarBatch",
            "sign": "'.$sign.'",
            "groupId":"'.$groupId.'",
             "carNumList":"'.$carNumstr.'",
            "signType":  "'.$this->encrypt.'",
            "timestamp": "'.$date.'"

        }';

            //访问接口获得数据

            $returnMsg = $this->sendString($data);


            $data=json_decode($returnMsg,true);

            if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                return true;

            }




        }else{
            return false;
        }

    }


    /**
     * 修改车辆
     * @param $carInfoList （array）修改车辆时车牌号
     * @param $groupId （string） 车辆组id
     *
     */
    public function alterCarMsg($carInfoList,$groupId){
        $date=date('Y-m-d H:i:s');
        $arr=$carInfoList;
        $str='';
        foreach($arr as $k=>$a){

            $str.=$k.'='.$a.',';

        }

        $str = trim($str, ',');
        //拼接成发送所需的格式
        $str='[{'.$str.'}]';
        //去除最后一个，

        $carInfoList=$str;

        $dataArr=[

            'charset'=>$this->charset,
            'partner'=>$this->patner,
            'service'=>'groups.gcOut.updateCarGroupCarBatch',
            'timestamp'=>$date,
            'carInfoList'=>$carInfoList,
            'groupId'=>$groupId,
            'version'=>$this->version

        ];

        //获得签名
        $sign=$this->getSign($dataArr);



        $data = '{
            "charset":"'.$this->charset.'",
            "partner": "'.$this->patner.'",
            "service": "groups.gcOut.updateCarGroupCarBatch",
            "sign": "'.$sign.'",
            "groupId":"'.$groupId.'",
             "carInfoList":"'.$carInfoList.'",
            "signType": "'.$this->encrypt.'",
            "timestamp": "'.$date.'",
            "version": "'.$this->version.'"
        }';

        $returnMsg = $this->sendString($data);

        $data=json_decode($returnMsg,true);

        if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

            return true;

        }

        return $data['returnMsg'];

    }



    /**
     * 删除车辆
     * @param $carNumList (array) 删除车辆时的车牌号数组
     * @param $groupId (string) 删除车辆时 添加车辆车辆组id
     *
     */
    public function delCar($carNumList,$groupId){

        $date=date('Y-m-d H:i:s');

        if(!empty($carNumList) && !empty($groupId)){

            $array=$carNumList;

            $carStr='';
            foreach($array as $a){

                $carStr.="'".$a."'".",";

            }

            $str=trim($carStr,',');

            $carNumstr='['.$str.']';

            $dataArr=[

                'charset'=>$this->charset,
                'partner'=>$this->patner,
                'version'=>$this->version,
                'service'=>'groups.gcOut.deleteCarGroupCarBatch',
                'timestamp'=>$date,
                'carNumList'=>$carNumstr,
                'groupId'=>$groupId

            ];
            //获得签名
            $sign=$this->getSign($dataArr);

            $data = '{
            "charset": "'.$this->charset.'",
            "version": "'.$this->version.'",
            "partner": "'.$this->patner.'",
            "service": "groups.gcOut.deleteCarGroupCarBatch",
            "sign": "'.$sign.'",
            "groupId":"'.$groupId.'",
             "carNumList":"'.$carNumstr.'",
            "signType":  "'.$this->encrypt.'",
            "timestamp": "'.$date.'"

        }';

            //访问接口获得数据
            $returnMsg = $this->sendString($data);

            $data=json_decode($returnMsg,true);

            if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                return true;

            }

            return $data['returnMsg'];

        }else{
            return false;
        }

    }

    /**
     * 车辆组信息查询
     * @param $gcCargroupId (string) 车辆组id
     */
    public function selectCarGroupMsg($gcCargroupId){

        if(!empty($gcCargroupId)){

            //修改逻辑根据产品开发需求再加

            $date=date('Y-m-d H:i:s');

            $dataArr=[

                'charset'=>$this->charset,
                'partner'=>$this->patner,
                'service'=>'groups.gcOut.selectCarGroupList',
                'timestamp'=>$date,
                'gcCargroupId'=>$gcCargroupId,

            ];

            //获得签名
            $sign=$this->getSign($dataArr);

            $data = '{
            "charset":"'.$this->charset.'",
            "partner": "'.$this->patner.'",
            "service": "groups.gcOut.selectCarGroupList",
            "sign": "'.$sign.'",
            "gcCargroupId":"'.$gcCargroupId.'",
            "signType":"'.$this->encrypt.'",
            "timestamp": "'.$date.'",
            "version": "'.$this->version.'"
            }';

            $returnMsg = $this->sendString($data);


            $data=json_decode($returnMsg,true);

            if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                return $data['dataList'];

            }

            return $data['returnMsg'];

        }else{
            return false;
        }

    }

    /**
     * 车辆信息查询
     * @param $str (string) 所传的参数数值
     * @param $type 所传的参数类型
     * （$type＝gcCargroupId：查询该车辆组下所有车辆信息）
     * （$type＝carNum：查询该车辆组下对应车辆信息）
     */

    public function getCarMsg($str,$type){

        //车辆组下车辆信息列表
        if(!empty($str)){
            //当参数为车辆组的
            if($type='gcCargroupId'){
                $gcCargroupId=$str;
                $date=date('Y-m-d H:i:s');
                $dataArr=[
                    'charset'=>$this->charset,
                    'partner'=>$this->patner,
                    'version'=>$this->version,
                    'service'=>'groups.gcOut.selectCarGroupCarLists',
                    'timestamp'=>$date,
                    'gcCargroupId'=>$str

                ];
                //获得签名
                $sign=$this->getSign($dataArr);

                $data = '{
                "charset": "'.$this->charset.'",
                "signType": "'.$this->encrypt.'",
                "partner": "'.$this->patner.'",
                "version": "'.$this->version.'",
                "service": "groups.gcOut.selectCarGroupCarList",
                "sign": "'.$sign.'",
                "gcCargroupId":"'.$str.'",
                "timestamp": "'.$date.'"

            }';

                return $this->sendString($data);

            }elseif($type='carNum'){
                $carNum=$str;
                //单独车辆的信息
                $date=date('Y-m-d H:i:s');
                $dataArr=[
                    'charset'=>$this->charset,
                    'partner'=>$this->patner,
                    'version'=>$this->version,
                    'service'=>'groups.gcOut.selectCarGroupCarList',
                    'timestamp'=>$date,
                    'carNum'=>$str,

                ];
                //获得签名
                $sign=$this->getSign($dataArr);

                $data = '{
                    "charset": "'.$this->charset.'",
                    "signType": "'.$this->encrypt.'",
                    "partner": "'.$this->patner.'",
                    "version": "'.$this->version.'",
                    "service": "groups.gcOut.selectCarGroupCarList",
                    "sign": "'.$sign.'",
                    "carNum":"'.$str.'",
                    "timestamp": "'.$date.'"

                }';

                $returnMsg = $this->sendString($data);


                $data=json_decode($returnMsg,true);

                if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                    return $data['dataList'];

                }

                return $data['returnMsg'];

            }else{

                return false;

            }

        }else{
            return false;
        }

    }

    /**
     * 查询集团车辆停车当前订单信息
     * $carNum （string）停车场订单id
     */

    public function stopCarOrder($carNum){
        $service='groups.gcOut.getCurrentParkingOrder';

        if(!empty($parkOrderId)){
            $date=date('Y-m-d H:i:s');

            $dataArr=[
                'charset'=>$this->charset,
                'partner'=>$this->patner,
                'version'=>$this->version,
                'service'=>$service,
                'timestamp'=>$date,
                'carNum'=>$carNum

            ];

            //获得签名
            $sign=$this->getSign($dataArr);

            $data = '{
            "charset": "'.$this->charset.'",
            "signType": "'.$this->encrypt.'",
            "partner": "'.$this->patner.'",
            "version": "'.$this->version.'",
            "service":"'.$service.'",
            "sign": "'.$sign.'",
            "carNum":"'.$carNum.'",
            "timestamp": "'.$date.'"

        }';

            $returnMsg = $this->sendString($data);


            $data=json_decode($returnMsg,true);

            if($data['returnCode'] == 'T' && $data['isSuccess']=='true'){

                return $data['dataList'];

            }


        }else{

            return array('0'=>array(
                'parkName'=>'该订单车辆未进入合租停车场',
                'inDatetime'=>'无',
                'outDatetime'=>'无',
                'parkAmount'=>'无')
            );


        }

    }

    /**
     * 查询集团车辆历史停车订单信息
     * $carNum 车牌号
     * $startDate 查询开始时间
     * $endDate 查询结束时间
     */
    public function stopCarOrderHistory($carNum,$startDate,$endDate){

        $service='groups.gcOut.getHistoryParkingOrderList';


        if(!empty($carNum) && !empty($startDate) && !empty($endDate)){

            $date=date('Y-m-d H:i:s');

            $dataArr=[
                'charset'=>$this->charset,
                'partner'=>$this->patner,
                'version'=>$this->version,
                'service'=>$service,
                'timestamp'=>$date,
                'carNum'=>$carNum,
                'startDate'=>$startDate,
                'endDate'=>$endDate


            ];


            //获得签名
            $sign=$this->getSign($dataArr);

            $data = '{
            "charset": "'.$this->charset.'",
            "signType":  "'.$this->encrypt.'",
            "partner": "'.$this->patner.'",
            "version": "'.$this->version.'",
            "service": "'.$service.'",
            "sign": "'.$sign.'",
            "carNum":"'.$carNum.'",
            "startDate":"'.$startDate.'",
            "endDate":"'.$endDate.'",
            "timestamp": "'.$date.'"

        }';

            $requestData=json_decode($this->sendString($data),true);

            if($requestData['returnCode'] == 'T' && $requestData['isSuccess']=='true'){


                return $requestData['dataList'];

            }


        }else{

            return array('0'=>array(
                'parkName'=>'订单查询不到',
                'inDatetime'=>'无',
                'outDatetime'=>'无',
                'parkAmount'=>'无')
            );

        }


    }



    public function getParkingOrderList($start = 0,$limit = 10000){

        $service='groups.gcOut.getParkingOrderList';

        $date=date('Y-m-d H:i:s',time()-73);

        $dataArr=[
            'charset'=>$this->charset,
            'partner'=>$this->patner,
            'version'=>$this->version,
            'service'=>$service,
            'timestamp'=>$date,
            'start'=>$start,
            'limit'=>$limit
        ];


        //获得签名
        $sign=$this->getSign($dataArr);

        $data = '{
            "charset": "'.$this->charset.'",
            "signType":  "'.$this->encrypt.'",
            "partner": "'.$this->patner.'",
            "version": "'.$this->version.'",
            "service": "'.$service.'",
            "sign": "'.$sign.'",
            "start":"'.$start.'",
            "limit":"'.$limit.'",
            "timestamp": "'.$date.'"

        }';

         $this->sendString($data);

        $requestData=json_decode($this->sendString($data),true);


        if($requestData['returnCode'] == 'T' && $requestData['isSuccess']=='true'){


            return $requestData['dataList'];

        }else{

            return false;


        }





    }




    //获得签名
    private function getSign($dataArr){

        $Arr=$dataArr;

        //对参数数组进行ASCII码从小到大排序（字典序）
        ksort($Arr);

        $queryStr = '';

        foreach ($Arr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }

        // 删除最后一位'&'符号
        $str = trim($queryStr, '&');

        //拼接API密钥
        $stringSingnTemp=$str.$this->key;

        // md5签名得到最终sign
        $sign=MD5($stringSingnTemp);

        return $sign;

    }


    //访问接口获得信息方法
    private function sendString($data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->hostUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    public function setRedisHelper($redisHelper){

        $this->redisHelper = $redisHelper;

    }

}