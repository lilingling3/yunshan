<?php

namespace Auto\Bundle\WebBundle\Controller;

use Auto\Bundle\ApiBundle\Controller\BaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Util\SecureRandom;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayResults;

/**
 * @Route("/")
 */
class DefaultController extends BaseController
{

    /**
     * @Route("/ecolink", methods="GET")
     * @Template()
     */

    public function ecolinkAction()
    {
        if($this->get('auto_manager.wechat_helper')->isWeChat()){

            $wechat = $this->get('auto_manager.wechat_helper')->GetOpenid();

echo $wechat['openid'];exit;


        }

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find(1);

        $status = $this->get('auto_manager.fee_zu_helper')->operate($rentalCar->getBoxId(),2);
        var_dump($status);exit;


        return [];

    }

    /**
     * @Route("/finish", methods="GET")
     * @Template()
     */

    public function finishAction()
    {

        return [];

    }

    /**
     * @Route("/denied", methods="GET")
     * @Template()
     */

    public function accessDeniedAction(){

    //    $logger = $this->get('monolog.logger.admin')->info('test info');
        return [];

    }


    /**
     * @Route("/cloud", methods="GET")
     * @Template()
     */
    public function cloudAction(){

        $redis = $this->container->get('snc_redis.default');

        $redis_cmd= $redis->createCommand('llen',array('cloud_box_id_list'));
        $length = $redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('lrange',array('cloud_box_id_list',0,$length));
        $box_list = $redis->executeCommand($redis_cmd);

        return ['box_list'=>$box_list];

    }



    /**
     * 获得签名
     */
    public function getSign($dataArr,$key){

        krsort($dataArr);

        $queryStr = '';

        foreach ($dataArr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }

        $resultStr=$queryStr.$key;

        // 拼接签名串
        $signature =urlencode($resultStr);

        // md5加密

        $signValue =md5($signature);

        return $signValue;
    }

    /**
     *@Route("/curl",methods="POST")
     */
    public function getCurlDataAction(){


        $data=$_POST;


        return  $this->checkSign($data);

    }

//验证签名
    public function checkSign($data){

        if (empty($data['userID'])) {

            $kString = md5('lingpailexiang');

            $ky = substr($kString, 8, 16);

            $key=$ky.$data['timestamp'];


        } else {

            $uid=$data['userID'];

            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['token' => $uid]);

            if (empty($member)) {

                return new JsonResponse([

                    'errorCode' => self::E_NO_ORDER,

                    'errorMessage' => self::M_NO_ORDER,

                ]);

            }

                $kString = $data['userID'];

                $ky = substr($kString, 8, 16);

                $key = $ky.$data['timestamp'];


        }

        $sign=$data['sign'];


        unset($data['sign']);

        $useSign= $this->getSign($data, $key);


        if ($sign == $useSign) {

            return new JsonResponse([

                'errorCode' => self::E_OK,

            ]);

        }
            return new JsonResponse([

                'errorCode' => self::E_NO_CHECKING_SIGN,

                'errorMessage' => self::M_NO_CHECKING_SIGN,

            ]);


    }


    /**
     * @Route("/test", methods="GET")
     * @Template()
     */

    public function testAction(){


        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find(1);

        $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'off',$order->getMember
        ());


        echo $result;exit;




        echo  base64_encode((new SecureRandom())->nextBytes(18));
        exit;


        $stations= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();

        $o= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->find(11);

        $arr = [];
        foreach($o->getStations() as $station){
            $arr[] = $station->getId();
        }


        foreach($stations as $s){

            if(!in_array($s->getId(),$arr)){
                $o->addStation($s);
            }



        }

        $man = $this->getDoctrine()->getManager();
        $man->persist($o);
        $man->flush();


        exit;



        echo date('Y-m-d H:i:s' ,1456361644);exit;

        echo $this->get('auto_manager.push_helper')->pushIOS();

        exit;
        echo date('Y-m-d H:i:s',1450232745);
        exit;
        $process = new Process(
            'cd ../; app/console auto:carstart:operate --id=35ffd8054342363838582543 --operate=close;');

        try{
            $process->setTimeout(5);
            $process->run();
            $str = $process->getOutput();
            echo $str;
        }catch (RuntimeException $e) {
            echo "false";
        }

        exit;
    }

    //经纬度之间距离计算
    function rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }
    function GetDistance($lat1, $lng1, $lat2, $lng2)
    {
        $EARTH_RADIUS = 6378.137;
        $radLat1 = $this->rad($lat1);
        //echo $radLat1;
        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) +
                cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
        $s = $s *$EARTH_RADIUS;

        $s = round($s * 1000) ;
        echo $s;exit;
        return $s;
    }



    /**
     * @Route("/map", methods="GET")
     */

    public function mapAction()
    {

        echo (new \DateTime('today'))->format('Y-m-d H:i:s');exit;

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find(120);

        echo $this->get("auto_manager.rental_car_helper")->operate($rentalCar,'off');
        exit;


        $redis = $this->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('set',array("abc",'34567890'));
        $box_json = $redis->executeCommand($redis_cmd);
        echo $box_json;exit;

        $json = file_get_contents('http://www.gx-zuche.com/parkinglots/parkinglotsJson');
        $arr = json_decode($json,true);

        return $this->render('AutoWebBundle:Default:map.html.twig', ['arr'=>$arr]);
    }

    public function getlatlng($out){

        $out_arr = explode("|",$out);

        //    var_dump($str);

        $gps_str = $out_arr[2];
        $gps_arr = explode(',',$gps_str);
        $lat = $gps_arr[2];
        $lng =  $gps_arr[4];
        $latg = substr($lat,0,2)+substr($lat,2)/60;
        $lngg = substr($lng,0,3)+substr($lng,3)/60;

        $latg = $latg+0.001233666667;
        $lngg = $lngg+0.005909;
        return [$latg,$lngg];


    }



}
