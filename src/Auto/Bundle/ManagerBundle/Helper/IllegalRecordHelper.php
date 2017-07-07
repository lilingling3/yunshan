<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/21
 * Time: 上午10:05
 */

namespace Auto\Bundle\ManagerBundle\Helper;


use Symfony\Component\Validator\Constraints\DateTime;

class IllegalRecordHelper extends AbstractHelper{


    private $bathPath = "http://webapi.cz.yiche.com";

    private $secretKey = "F4912DB5-27C7-4FDF-B03F-7BDCD225D54B";
    private $appId = "winsky";

    /**
     * 获取违章查询城市列表
     * @return request
     */
    public function getCityList() {

        $apiUrl = "/weizhang2/GetCityList";


        $dataArr = [
            'appid' => $this->appId
        ];

        // 获得签名
        $sign = $this->getSign($dataArr, $this->bathPath.$apiUrl);

        // 查询参数字符串
        $queryStr = '';
        foreach ($dataArr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }
        // 删除最后一位'&'符号
        $queryStr = trim($queryStr, '&');

        $returnMsg = $this->curl_post($this->bathPath.$apiUrl ."?".$queryStr, $sign);

        return $returnMsg;

    }

    /**
     * 获取违章记录
     * @param string $mobile REQUIRE
     * @param int    $carId  REQUIRE
     * @param string $cityId REQUIRE
     * @param string $plateNum REQUIRE
     * @param string $ecode
     * @param string $vcode
     */
    public function IllegalRecordQuery($mobile, $carId, $cityId, $plateNum, $ecode, $vcode) {

        $apiUrl = "/weizhang2/Query";

        $dataArr = [
            'appid' => $this->appId,
            'Phone' => $mobile,
            'CarId' => $carId,
            'ApiKey'=> $cityId,
            'PlateNumber' => strtoupper(urlencode($plateNum)),
            'Ecode' => $ecode,
            'Vcode' => $vcode
        ];


        // 获得签名
        $sign = $this->getSign($dataArr, $this->bathPath.$apiUrl);

        // 查询参数字符串
        $queryStr = '';
        foreach ($dataArr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }
        // 删除最后一位'&'符号
        $queryStr = trim($queryStr, '&');

        $returnMsg = $this->curl_post($this->bathPath.$apiUrl ."?".$queryStr, $sign);

        return $returnMsg;

    }

    /**
     * 构造POST请求
     * @param string $url
     * $param string $data
     * @return mixed
     */
    private function curl_post($url, $sign) {

        $header = [
            "UserToken:" . $sign,
        ];

        $curlObj = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            // CURLOPT_POST => TRUE, //使用post提交
            CURLOPT_RETURNTRANSFER => TRUE, //接收服务端范围的html代码而不是直接浏览器输出
            CURLOPT_BINARYTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HTTPHEADER => $header,
            // CURLOPT_POSTFIELDS => $data //post的数据
            // CURLOPT_POSTFIELDS => http_build_query($data) //post的数据
        );

        curl_setopt_array($curlObj, $options);
        $response = curl_exec($curlObj);

        file_put_contents('/data/www/data/ill.log', "|=|". $sign . "|=|".$url, FILE_APPEND);
        curl_close($curlObj);
        return $response;
    }

    /**
     * 违章查询接口签名生成
     * @param array $data
     * @param string $url
     * @return string
     */
    private function getSign($data, $url) {

        // ksort($data);

        // 查询参数字符串
        $queryStr = '';
        foreach ($data as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }
        // 删除最后一位'&'符号
        $queryStr = trim($queryStr, '&');

        // 拼接签名串
        $signature = $url. '?' .$queryStr;

        //生成签名

        $signValue = md5($signature . $this->secretKey);

        return $signValue;


    }

    public function get_illegal_record_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\IllegalRecord $r) {

            $message= '';
            $time = $r->getCreateTime()->format('Y/m/d');
            $handle_time = $r->getCreateTime()->modify("+4 day")->format('Y年m月d日');

            if($r->getIllegalAmount()>0){
                $message='请您于'.$handle_time.'前处理完毕。您可以选择离您最近的交通队进行处理，同时还可以凭牡丹交通卡在工商银行自助缴费机处理。
违章处理后我们将根据交通部门系统处理结果24小时内变更您的违章状态。逾期未处理我们将会取消您的用车资格，感谢您的理解与配合！';
            }
            if($r->getIllegalScore()>0){
                $message='请您于'.$handle_time.'前处理完毕。处理违章前，请您提前联系网点运营人员取得行驶本原件后，选择离您最近的交通队进行处理。另外，领取行驶本时您要向运营人员支付200元押金，并且在4小时内归还，超期未归还我们将扣除押金费用。违章处理后我们将根据交通部门系统处理结果24小时内变更您的违章状态。违章逾期未处理我们将会取消您的用车资格，感谢您的理解与配合！';
            }


            $illegal = array(
                'illegalRecordID' =>$r->getId(),
                'illegalTime'     =>$r->getIllegalTime()->format('Y/m/d H:i'),
                'createTime'      =>$time,
                'illegalScore'    =>$r->getIllegalScore(),
                'illegalAmount'   =>$r->getIllegalAmount(),
                'handleText'      =>$message,
                'handleTime'      =>$r->getHandleTime()?$r->getHandleTime()->format('Y/m/d H:i'):null,
                'illegalPlace'    =>$r->getIllegalPlace(),
                'illegalReason'   =>$r->getIllegal(),
                'rentalCar'       =>call_user_func($this->rentalCarHelper->get_rental_car_normalizer(),
                    $r->getOrder()->getRentalCar()),
                //违章车辆订单数据
                'order'         =>call_user_func($this->orderHelper->get_rental_order_normalizer(),
                    $r->getOrder()),
            );


            return $illegal;
        };
    }

    public function get_member_illegal_order($member){

        $qb = $this->em->createQueryBuilder();

        $time = (new \DateTime())->modify("-3 days")->format('Y-m-d');
        $illegal =
            $qb
                ->select('i')
                ->from('AutoManagerBundle:IllegalRecord', 'i')
                ->join('i.order','o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNull('i.handleTime'))
                ->andWhere($qb->expr()->lt('i.createTime',':time'))
                ->setParameter('member', $member)
                ->setParameter('time', $time)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        return $illegal;

    }


    public function setRentalCarHelper($rentalCarHelper)
    {
        $this->rentalCarHelper = $rentalCarHelper;
    }

    public function setOrderHelper($orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }


}