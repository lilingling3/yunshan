<?php

namespace Auto\Bundle\Api2Bundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayConfig;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayUnifiedOrder;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\PayNotifyCallBack;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayResults;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @Route("/order")
 */
class OrderController extends BaseController
{
    const PER_PAGE = 5;
    const CAR_STATION_DISTANCE = 100;
    const CAR_STATION_DISTANCE_MAX = 100;

    /**
     * @Route("/getCarProblemName", methods="POST")
     */
    public function getCarProblemNameAction()
    {
        $arr = array(
            1 => '车辆异常',
            2 => '车身剐蹭',
            3 => '前后挡风破损',
            4 => '反光镜破损',
            5 => '门窗破损',
            6 => '门窗未关',
            7 => '雨刷器破损',
            8 => '保险杠破损'
        );
        return new JsonResponse($arr);

    }

    /**
     * @Route("/list", methods="POST",name="auto_api_2_order_list")
     */

    public function listAction(Request $req)
    {

        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o')
        ;
        $orders =
            new Paginator(
                $qb
                    ->select('o')
                    ->orderBy('o.id', 'DESC')
                    ->where($qb->expr()->eq('o.member', ':member'))
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $total_mileage =
            $qb
                ->select('SUM(o.endMileage - o.startMileage)')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getSingleScalarResult()
        ;


        $order_data = array_map($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $orders->getIterator()->getArrayCopy());

        return new JsonResponse([
            'errorCode'   =>  self::E_OK,
            'pageCount'   =>ceil($orders->count() / self::PER_PAGE),
            'page'        =>$page,
            'orderCount'  =>$orders->count(),
            'totalMileage'=> $total_mileage?round($total_mileage, 2):0,
            'orders'      =>  $order_data,
        ]);

    }




    /**
     * @Route("/getRentOrder", methods="POST")
     */
    public function getRentOrderAction(Request $req)
    {

        $order_id = $req->request->get('orderID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$this->getUser()]);


        $check_order = $this->checkRentalOrder($order);

        $order_order_data = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $order);


        if(!empty($check_order)){

            return new JsonResponse($check_order);


        } else{
            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'order'     =>  $order_order_data
            ]);
        }

    }

    /**
     * @Route("/find", methods="POST")
     */

    public function findAction(Request $req)
    {

        $order_id = $req->request->get('orderID');

        $member = $this->getUser();

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }


        $order_status = $this->get("auto_manager.order_helper")->get_order_status($order);

        if(!($order_status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER||$order_status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_RIGHT,
                'errorMessage'      =>  self::M_NO_RIGHT
            ]);
        }


        $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'find',$member,'');



        if($result){
            return new JsonResponse([
                'errorCode' =>  self::E_OK,
            ]);
        }else{
            return new JsonResponse([
                'errorCode' =>  self::E_FIND_CAR,
                'errorMessage' =>  self::M_FIND_CAR
            ]);
        }


    }


    /**
     * @Route("/lock", methods="POST",name="auto_api_2_order_lock")
     */

    public function lockAction(Request $req)
    {

        $order_id = $req->request->get('orderID');
        $status = $req->request->getInt('status');

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);



        $order_status = $this->get("auto_manager.order_helper")->get_order_status($order);

        if(!($order_status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER ||
             $order_status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER)) {

            return new JsonResponse([
                'errorCode'    => self::E_NO_RIGHT,
                'errorMessage' => self::M_NO_RIGHT
            ]);
        }

        if(empty($order->getRentalCar()->getBoxId())){
            return new JsonResponse([
                'errorCode'    => self::E_NO_CAR_START_DEVICE,
                'errorMessage' => self::M_NO_CAR_START_DEVICE
            ]);
        }

        if($status==1){

            $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'close',$member,'');


            if($result){
                return new JsonResponse([
                    'errorCode' => self::E_OK,
                ]);
            }else{
                return new JsonResponse([
                    'errorCode'    => self::E_CLOSE_DOOR,
                    'errorMessage' => self::M_CLOSE_DOOR
                ]);
            }

        }elseif($status==0){

            if(!$order->getUseTime()){
                $order->setUseTime(new \DateTime());

                $redis = $this->container->get('snc_redis.default');

                $redis_cmd= $redis->createCommand('lindex',array($order->getRentalCar()->getDeviceCompany()->getEnglishName().'-mileage-'
                    .$order->getRentalCar()->getBoxId(),0));
                $mileage_arr = $redis->executeCommand($redis_cmd);
                $mileage_arr = json_decode($mileage_arr,true);
                if(!empty($mileage_arr)){
                    $order->setStartMileage($mileage_arr['mileage']);
                }

                $man = $this->getDoctrine()->getManager();
                $man->persist($order);
                $man->flush();



                $box_password = $this->get_random_integer(4);
                $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'encode',$member,
                    $box_password);

                if($result){

                    $redis_cmd= $redis->createCommand('set',array($order->getRentalCar()->getDeviceCompany()
                            ->getEnglishName().'-password-'.$order->getRentalCar()->getBoxId(),$box_password));
                    $redis->executeCommand($redis_cmd);

                    if($order->getRentalCar()->getDeviceCompany()->getEnglishName()=='carStart'){

                        //发短信

                        $this->get("auto_manager.sms_helper")->add(
                            $order->getMember()->getMobile(),
                            $this->renderView(
                                'AutoManagerBundle:Order:use.sms.twig',
                                [
                                    'license'=>$order->getRentalCar()->getLicense(),
                                    'password'=>$box_password
                                ]
                            ));

                    }

                }


                //通电
                $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'on',$member,'');

                $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'open',$member,'');

                // 该车是否需要缴纳分时租赁保险
                if ($order->getRentalCar()->getInsuranceType() == 1) {

                    $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
                    $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                        ('auto_api_insurance_add'), ['OrderID'=>$order->getId()]);
                }

                return new JsonResponse([
                    'errorCode' => self::E_OK,
                ]);

            }else{

                //通电

                $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'on',$member,'');

                if($result){
                    $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'open',
                        $member,'');

                    if($result){
                        return new JsonResponse([
                            'errorCode' => self::E_OK,
                        ]);
                    }else{
                        return new JsonResponse([
                            'errorCode'    => self::E_OPEN_DOOR,
                            'errorMessage' => self::M_OPEN_DOOR
                        ]);
                    }

                }else{
                    return new JsonResponse([
                        'errorCode'    => self::E_OPEN_DOOR,
                        'errorMessage' => self::M_OPEN_DOOR
                    ]);
                }

            }

        }

    }





    function get_random_integer($length)
    {
        $key='';
        for($i=0;$i<$length;$i++)
        {
            $key .= rand(1,5);    //生成php随机数
        }
        return $key;
    }


    /**
     * >1.5
     * @Route("/cancel_15", methods="POST")
     */

    public function cancel15Action(Request $req)
    {
        $order_id = $req->request->get('orderID');
        $cancel_reason = $req->request->get('cancelReason');

        /**
         * @var $order \Auto\Bundle\ManagerBundle\Entity\RentalOrder
         */

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member'=>$this->getUser(),'id'=>$order_id]);


        // 通知第三方车辆可租用
        $carPartnerData = [
            'carId' => $order->getRentalCar()->getId(),
            'orderId' => $order_id,
            'backRentalStation' => $order->getPickUpStation()->getId(),
        ];

        $check_order = $this->checkRentalOrder($order);

        if(!empty($check_order)){
            return new JsonResponse($check_order);
        }

        if($cancel_reason){
            $order->setCancelReason(json_decode($cancel_reason,true));
        }




        if($order->getCancelTime()){

            // 通知第三方车辆可租用
            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'order'      =>  call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
                    $order)
            ]);

        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $today_order = $qb
            ->select($qb->expr()->count('o'))
            ->where($qb->expr()->eq('o.member', ':member'))
            ->andWhere($qb->expr()->lte('o.cancelTime', ':ttime'))
            ->andWhere($qb->expr()->gte('o.cancelTime',':gtime'))
            ->setParameter('member', $this->getUser())
            ->setParameter('ttime', (new \DateTime("+1 days"))->format('Y-m-d'))
            ->setParameter('gtime', date('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult()
        ;


        if((new \DateTime())->getTimestamp()-$order->getCreateTime()->getTimestamp()>15*60){

            $order->setEndTime(new \DateTime());

            $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($order);

            $order->setDueAmount($cost_detail['cost']);
            $order->getRentalCar()->setRentalStation($order->getPickUpStation());

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            // 通知第三方车辆可租用
            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_OUT_CANCEL_FREE_TIME,
                'errorMessage' =>self::M_OUT_CANCEL_FREE_TIME,
                'orderID'   =>$order->getId()
            ]);

        }

        if($today_order>=2){
            $order->setEndTime(new \DateTime());
            $order->getRentalCar()->setRentalStation($order->getPickUpStation());

            $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($order);

            $order->setDueAmount($cost_detail['cost']);
            $order->getRentalCar()->setRentalStation($order->getPickUpStation());

            $man = $this->getDoctrine()->getManager();

            $man->persist($order);
            $man->flush();

            // 通知第三方车辆可租用
            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_CANCEL_ORDER_COUNT,
                'errorMessage' =>self::M_CANCEL_ORDER_COUNT,
                'orderID'   =>$order->getId()
            ]);
        }





        $status = $this->get('auto_manager.order_helper')->get_order_status($order);

        if($this->getUser()==$order->getMember()&&$status==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER&&!$order->getUseTime()){

            $order->setCancelTime(new \DateTime());
            if($order->getPickUpStation()->getBackType()
                ==\Auto\Bundle\ManagerBundle\Entity\RentalStation::DIFFERENT_PLACE_BACK){
                $order->getRentalCar()->setRentalStation($order->getPickUpStation());
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $this->get("auto_manager.sms_helper")->add(
                $order->getMember()->getMobile(),
                $this->renderView(
                    'AutoManagerBundle:Order:cancel.sms.twig'
                ));

            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'orderID'   =>$order->getId()

            ]);

        }else{

            return new JsonResponse([
                'errorCode'    => self::E_ORDER_PROGRESS,
                'errorMessage' => self::M_ORDER_PROGRESS,

            ]);

        }

    }





    /**
     * 结算金额
     * @Route("/settleRentOrder", methods="POST",name="auto_api_2_order_settle_rental_order")
     */
    public function settleRentOrderAction(Request $req)
    {
        $order_id = $req->request->get('orderID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$this->getUser()]);


        $check_order = $this->checkRentalOrder($order);

        if(!empty($check_order)){

            return new JsonResponse($check_order);

        }

        if(empty($order->getEndTime())){

            return new JsonResponse([
                'errorCode' =>  self::E_NOT_END_ORDER,
                'errorMessage' =>  self::M_NOT_END_ORDER,
            ]);

        }

        $offsetHour = floor(($order->getEndTime()->getTimeStamp()-$order->getCreateTime()->getTimeStamp())/3600);


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        $coupon = $qb
            ->select('c')
            ->join('c.kind','k')
            ->orderBy('k.amount','DESC')
            ->addOrderBy('c.endTime', 'ASC')
            ->where($qb->expr()->eq('c.member', ':member'))
            ->andWhere($qb->expr()->lte('k.needHour', ':hour'))
            ->andWhere($qb->expr()->gte('c.endTime',':endTime'))
            ->andWhere($qb->expr()->isNull('c.useTime'))
            ->setParameter('member', $this->getUser())
            ->setParameter('hour', $offsetHour)
            ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        ;

        $max_wallet_amount = 0;

        $order_normalizer = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $order);


        if($this->getUser()->getWallet()>0){

            $max_wallet_amount = $this->getUser()->getWallet() >
            $order_normalizer['costDetail']['cost']?$order_normalizer['costDetail']['cost']:$this->getUser()->getWallet();

        }


        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'order'      => $order_normalizer,
            'coupon'    =>  empty($coupon)?null:call_user_func($this->get('auto_manager.coupon_helper')
                    ->get_order_coupon_normalizer(),
                $coupon),
            'maxWalletAmount' =>$max_wallet_amount
        ]);


    }



    /**
     * 还车
     * @Route("/endRentalOrder", methods="POST",name="auto_api_2_rental_order_car_back")
     */
    public function endRentalOrderAction(Request $req){

        $order_id = $req->request->get('orderID');


        $member = $this->getUser();


        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }


        /**
         * @var $order \Auto\Bundle\ManagerBundle\Entity\RentalOrder
         */


        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);


        if($order->getEndTime()){
            return new JsonResponse([
                'errorCode'    =>  self::E_ORDER_END,
                'errorMessage' =>  self::M_ORDER_END,
                'orderID'      =>  $order_id
            ]);
        }

        if(empty($order->getRentalCar()->getBoxId())){
            return new JsonResponse([
                'errorCode'    => self::E_NO_CAR_START_DEVICE,
                'errorMessage' => self::M_NO_CAR_START_DEVICE
            ]);
        }
//        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'gps');

        // 获取gps数据
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('LINDEX',array($order->getRentalCar()->getDeviceCompany()
            ->getEnglishName().'-gps-'.$order->getRentalCar()->getBoxId(),0));
        $gps_json = $redis->executeCommand($redis_cmd);
        $gps_arr = json_decode($gps_json,true);

        if(!empty($gps_arr)&&((new \DateTime())->getTimestamp()-$gps_arr['time']<15*60)){

            $destination = [$order->getRentalCar()->getRentalStation()->getLongitude(),$order->getRentalCar()->getRentalStation()->getLatitude()];

            $distance = $this->get('auto_manager.amap_helper')->straight_distance($gps_arr['coordinate'],$destination);

            if((new \DateTime())->getTimestamp()-$gps_arr['time']<=5){

                if($distance>=self::CAR_STATION_DISTANCE){

                    return new JsonResponse([
                        'errorCode'    =>  self::E_STATION_CAR_DISTANCE,
                        'errorMessage' =>  self::M_STATION_CAR_DISTANCE
                    ]);
                }

            }else{

                if($distance>=self::CAR_STATION_DISTANCE_MAX){

                    return new JsonResponse([
                        'errorCode'    =>  self::E_STATION_CAR_DISTANCE,
                        'errorMessage' =>  self::M_STATION_CAR_DISTANCE
                    ]);
                }
            }

        }else{
            return new JsonResponse([
                'errorCode'    =>  self::E_STATION_CAR_DISTANCE,
                'errorMessage' =>  self::M_STATION_CAR_DISTANCE
            ]);

        }

        //熄火

        if($order->getRentalCar()->getDeviceCompany()->getEnglishName()=='carStart'){

            $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'status',$member);
            sleep(1);
            $redis = $this->container->get('snc_redis.default');

            $redis_cmd= $redis->createCommand('lindex',array($order->getRentalCar()->getDeviceCompany()
                    ->getEnglishName().'-status-'.$order->getRentalCar()->getBoxId(),0));

            $fire_json = $redis->executeCommand($redis_cmd);
            $fire_arr = json_decode($fire_json,true);

            if((new \DateTime())->getTimestamp()-$fire_arr['time']>10){

                return new JsonResponse([
                    'errorCode'    =>  self::E_ON_FIRE,
                    'errorMessage' =>  self::M_ON_FIRE
                ]);

            }

            if($fire_arr['status']=='6F07'){
                return new JsonResponse([
                    'errorCode'    =>  self::E_ON_FIRE,
                    'errorMessage' =>  self::M_ON_FIRE
                ]);
            }
        }

        // 分时租赁保险结单开始
        $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_under_write'), ['OrderID'=>$order->getId()]);
        // 分时租赁保险结单结束

        //关门
        $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'close',$member);

        if(!$result){

            return new JsonResponse([
                'errorCode'    => self::E_CLOSE_DOOR,
                'errorMessage' => self::M_CLOSE_DOOR
            ]);

        }else{

            $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'on',$member);
        }


        $man = $this->getDoctrine()->getManager();

        $range = $this->get('auto_manager.rental_car_helper')->get_rental_car_range($order->getRentalCar());

        if (!$order->getRentalCar()->getCar()) {

            return new JsonResponse([
                'errorCode'    => self::E_NO_RENTAL_CAR,
                'errorMessage' => self::M_NO_RENTAL_CAR,
            ]);
        }

        // 如果剩余里程小于该车型的'自动下线里程'，则
        // 该车自动下线
        if($range < $order->getRentalCar()->getCar()->getAutoOfflineMileage()){

            $onlineRecord = new RentalCarOnlineRecord();
            $onlineRecord->setStatus(0)
                ->setReason([16])
                ->setRentalCar($order->getRentalCar())
                ->setBackRange($range)
                ->setMember($member);
            $man->persist($onlineRecord);
            $man->flush();

            $order->getRentalCar()->setOnline($onlineRecord);
            // 车辆下线通知第三方
            $carPartnerData = [
                'carId' => $order->getRentalCar()->getId(),
                'operator' => '',
                'reason' => '',
                'remark' => '',
                'stationId' => $order->getReturnStation()->getId(),
            ];
            $this->get("auto_manager.partner_helper")->carOffline($carPartnerData);
        }

        // 获取车辆里程数据
        $redis_cmd= $redis->createCommand('lindex',array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName().'-mileage-'.$order->getRentalCar()->getBoxId(),0));
        $mileage_arr = $redis->executeCommand($redis_cmd);
        $mileage_arr = json_decode($mileage_arr,true);
        if(!empty($mileage_arr)){
            $order->setEndMileage($mileage_arr['mileage']);
        }

        $order->setEndTime(new \DateTime());

        $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($order);

        $order->setDueAmount($cost_detail['cost']);
        $man->persist($order);
        $man->flush();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder'=>$order]);

        if(!empty($dispatch)){

            $dispatch->setStatus(1);

            $man->persist($dispatch);
            $man->flush();
        }
        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'off',$member);
        $password = $this->get_random_integer(4);

        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'encode',$member,$password);

        $redis_cmd= $redis->createCommand('del',array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName().'-password-'.$order->getRentalCar()->getBoxId()));
        $redis->executeCommand($redis_cmd);


        $car_order_data = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $order);


        // 通知第三方车辆可租用
        $carPartnerData = [
            'carId' => $order->getRentalCar()->getId(),
            'orderId' => $order_id,
            'backRentalStation' => $order->getPickUpStation()->getId(),
        ];
        $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'order'=> $car_order_data,

        ]);

    }




    /**
     *检查付款信息是否正确
     * @Route("/rentOrderPay", methods="POST")
     */
    public function RentOrderPayAction(Request $req)
    {
        $order_id = $req->request->getInt('orderID');
        $coupon_id = $req->request->getInt('couponID');
        $wallet = $req->request->get('wallet');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$this->getUser()]);

        $check_order = $this->checkRentalOrder($order);

        if(!empty($check_order)){

            return new JsonResponse($check_order);

        }

        if($order->getPayTime()) {

            return new JsonResponse([
                'errorCode' => self::E_ORDER_PAYED,
                'errorMessage' => self::M_ORDER_PAYED,
            ]);

        }



        $coupon_amount = 0;
        if($coupon_id){
            $coupon = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->find($coupon_id);

            $coupon_amount = empty($coupon)?0:$coupon->getKind()->getAmount();


            if(empty($coupon)||$coupon->getMember()!=$this->getUser()||$coupon->getEndTime()->format('Y-m-d H:i:s')<date('Y-m-d
                H:i:s')) {

                return new JsonResponse([
                    'errorCode' => self::E_NO_COUPON,
                    'errorMessage' => self::M_NO_COUPON,
                ]);

            }
            $order->setCoupon($coupon);

        }else{
            $order->setCoupon(null);
        }




        $charge = $this->get("auto_manager.order_helper")->get_rental_order_cost($order);

        // 要与/free/order处算法一致 
        $charge['cost'] = number_format($charge['cost'], 2, '.', '');

        $orderAmount = $charge['cost'] - $coupon_amount;

        $order_wallet_amount = 0;


        if($this->getUser()->getWallet()>0&&$wallet >0){

            if($this->getUser()->getWallet()>$orderAmount){

                $order_wallet_amount = $orderAmount;
                $orderAmount=0;

            }else{
                $orderAmount = $orderAmount - $this->getUser()->getWallet();
                $order_wallet_amount = $this->getUser()->getWallet();
            }


        }
        $orderAmount = $orderAmount>=0?$orderAmount:0;


        $order->setWalletAmount($order_wallet_amount);

        $man = $this->getDoctrine()->getManager();
        $man->persist($order);
        $man->flush();


        $order->setAmount($orderAmount);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'order'=>call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
                $order),
        ]);

    }



    /**
     * @Route("/changeReturnStation", methods="POST",name="auto_api_2_order_changeReturnStation")
     */

    public function changeReturnStationAction(Request $req){

        $order_id = $req->request->getInt('orderID');
        $rental_station_id = $req->request->getInt('rentalStationID');

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$this->getUser()]);

        $this->checkMemberRentalOrder($this->getUser(),$rentalOrder);

        if($rentalOrder->getPickUpStation()->getBackType()
            ==\Auto\Bundle\ManagerBundle\Entity\RentalStation::SAME_PLACE_BACK){
            return new JsonResponse([
                'errorCode' => self::E_UNABLE_CHANGE_STATION,
                'errorMessage' => self::M_UNABLE_CHANGE_STATION,
            ]);
        }

        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($rental_station_id);

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $rental_car_count =
            $qb
                ->select($qb->expr()->count('c'))
                ->where($qb->expr()->eq('c.rentalStation', ':station'))
                ->setParameter('station', $rentalStation)
                ->getQuery()
                ->getSingleScalarResult()
        ;

        if($rentalStation->getParkingSpaceTotal()<=$rental_car_count){

            return new JsonResponse([
                'errorCode' => self::E_UNABLE_CHANGE_STATION,
                'errorMessage' => self::M_UNABLE_CHANGE_STATION,
            ]);
        }


        $man = $this->getDoctrine()->getManager();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder'=>$rentalOrder->getId()]);

        if(!empty($dispatch)){
            $dispatch->setRentalStation($rentalStation);
            $man->persist($dispatch);
            $man->flush();
        }

        $rentalOrder->setReturnStation($rentalStation);
        $rentalOrder->getRentalCar()->setRentalStation($rentalStation);

        $man->persist($rentalOrder);
        $man->flush();

        return new JsonResponse([
            'errorCode' => self::E_OK
        ]);

    }


}
