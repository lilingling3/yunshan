<?php

namespace Auto\Bundle\ApiBundle\Controller;

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
    const PER_PAGE = 20;
    const CAR_STATION_DISTANCE = 100;
    const CAR_STATION_DISTANCE_MAX = 100;
   
    /**
     * @Route("/getRentOrder", methods="POST")
     */
    public function getRentOrderAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

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

        }else{
            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'order'      =>  call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                    $order)
            ]);
        }

    }

    /**
     * @Route("/find", methods="POST",name="auto_api_order_find")
     */

    public function findAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

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
     * @Route("/useCar", methods="POST",name="auto_api_use_car")
     */
    public function useCarAction(Request $req){

        $rentalCar = $req->request->get('rentalCar');
        $action = $req->request->get('action');
        $member = $req->request->getInt('member');

        $result = $this->get("auto_manager.rental_car_helper")->operate($rentalCar,$action,$member,'');

        return $result;

    }




    /**
     * @Route("/lock", methods="POST",name="auto_api_order_lock")
     */

    public function lockAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');
        $status = $req->request->getInt('status');


        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);



        $order_status = $this->get("auto_manager.order_helper")->get_order_status($order);

        if(!($order_status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER||
             $order_status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER)) {

            return new JsonResponse([
                'errorCode' =>  self::E_NO_RIGHT,
                'errorMessage'      =>  self::M_NO_RIGHT
            ]);
        }

        if(empty($order->getRentalCar()->getBoxId())){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_CAR_START_DEVICE,
                'errorMessage'      =>  self::M_NO_CAR_START_DEVICE
            ]);
        }

        if($status==1){

            $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'close',$member,'');


            if($result){
                return new JsonResponse([
                    'errorCode' =>  self::E_OK,
                ]);
            }else{
                return new JsonResponse([
                    'errorCode' =>  self::E_CLOSE_DOOR,
                    'errorMessage'      =>  self::M_CLOSE_DOOR
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
                                    'license'  => $order->getRentalCar()->getLicense(),
                                    'password' => $box_password
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
        $uid = $req->request->get('userID');
        $cancel_reason = $req->request->get('cancelReason');

        /**
         * @var $order \Auto\Bundle\ManagerBundle\Entity\RentalOrder
         */
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member'=>$member,'id'=>$order_id]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        if($cancel_reason){
            $order->setCancelReason(json_decode($cancel_reason,true));
        }


        // 通知第三方车辆可租用
        $carPartnerData = [
            'carId' => $order->getRentalCar()->getId(),
            'orderId' => $order_id,
            'backRentalStation' => $order->getPickUpStation()->getId(),
        ];


        if($order->getCancelTime()){

            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'order'      =>  call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
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
            ->setParameter('member', $member)
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
            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_CANCEL_ORDER_COUNT,
                'errorMessage' =>self::M_CANCEL_ORDER_COUNT,
                'orderID'   =>$order->getId()
            ]);
        }


        $status = $this->get('auto_manager.order_helper')->get_order_status($order);

        if($member==$order->getMember()&&$status==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER&&!$order->getUseTime()){

            $order->setCancelTime(new \DateTime());
            if($order->getPickUpStation()->getBackType()
                ==\Auto\Bundle\ManagerBundle\Entity\RentalStation::DIFFERENT_PLACE_BACK){
                $order->getRentalCar()->setRentalStation($order->getPickUpStation());
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $this->get("auto_manager.sms_helper")->cancelRentalSMS($order->getMember()->getMobile());

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
     * @Route("/cancel", methods="POST")
     */

    public function cancelAction(Request $req)
    {
        $order_id = $req->request->get('orderID');
        $uid = $req->request->get('userID');

        /**
         * @var $order \Auto\Bundle\ManagerBundle\Entity\RentalOrder
         */

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member'=>$member,'id'=>$order_id]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        if($order->getCancelTime()){

            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'order'      =>  call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
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
            ->setParameter('member', $member)
            ->setParameter('ttime', (new \DateTime("+1 days"))->format('Y-m-d'))
            ->setParameter('gtime', date('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult()
        ;


        if($today_order>=2){
            return new JsonResponse([
                'errorCode' =>  self::E_CANCEL_ORDER_COUNT,
                'errorMessage' =>self::M_CANCEL_ORDER_COUNT,
                'orderID'   =>$order->getId()
            ]);
        }


        $status = $this->get('auto_manager.order_helper')->get_order_status($order);

        if($member==$order->getMember()&&$status==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER&&!$order->getUseTime()){

            $order->setCancelTime(new \DateTime());
            if($order->getPickUpStation()->getBackType()
                ==\Auto\Bundle\ManagerBundle\Entity\RentalStation::DIFFERENT_PLACE_BACK){
                $order->getRentalCar()->setRentalStation($order->getPickUpStation());
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            // 通知第三方车辆可租用
            $carPartnerData = [
                'carId' => $order->getRentalCar()->getId(),
                'orderId' => $order->getId(),
                'backRentalStation' => $order->getPickUpStation()->getId(),
            ];
            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return new JsonResponse([
                'errorCode' =>  self::E_OK
            ]);

        }else{

            return new JsonResponse([
                'errorCode'    => self::E_ORDER_PROGRESS,
                'errorMessage' => self::M_ORDER_PROGRESS,
            ]);

        }

    }


     /**
     * @Route("/list", methods="POST",name="auto_api_order_list")
     */

    public function listAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

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


        return new JsonResponse([
            'errorCode'   =>  self::E_OK,
            'pageCount'   =>ceil($orders->count() / self::PER_PAGE),
            'page'        =>$page,
            'orderCount'  =>$orders->count(),
            'totalMileage'=> $total_mileage?round($total_mileage/1000,2):0,
            'orders'      =>  array_map($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                $orders->getIterator()->getArrayCopy()),
        ]);

    }

    /**
     * 结算金额
     * @Route("/settleRentOrder", methods="POST",name="auto_api_order_settle_rental_order")
     */
    public function settleRentOrderAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');


        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

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


        if (!$order->getRentalCar()->getCar()) {

            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>  self::M_NO_RENTAL_CAR,
            ]);

        }

        $carLevel = $order->getRentalCar()->getCar()->getLevel();

        if(empty($order->getEndTime())){

            return new JsonResponse([
                'errorCode' =>  self::E_NOT_END_ORDER,
                'errorMessage' =>  self::M_NOT_END_ORDER,
            ]);

        }

        $offsetHour = floor(($order->getEndTime()->getTimeStamp()-$order->getCreateTime()->getTimeStamp())/3600);

        $order_normalizer = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);


        $amount = $order_normalizer['costDetail']['cost'];
        

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
            ->andWhere($qb->expr()->lte('k.needAmount', ':amount'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('k.carLevel', ':carlevel'),
                $qb->expr()->isNull('k.carLevel')
            ))
            ->setParameter('member', $member)
            ->setParameter('amount', $amount)
            ->setParameter('hour', $offsetHour)
            ->setParameter('carlevel', $carLevel)
            ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        ;


        $max_wallet_amount = 0;

        if($member->getWallet()>0){

            $max_wallet_amount = $member->getWallet() >
            $order_normalizer['costDetail']['cost']?$order_normalizer['costDetail']['cost']:$member->getWallet();

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
     * @Route("/endRentalOrder", methods="POST",name="auto_rental_order_car_back")
     */
    public function endRentalOrderAction(Request $req){

        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

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
                'errorCode'     => self::E_NO_CAR_START_DEVICE,
                'errorMessage'  => self::M_NO_CAR_START_DEVICE
            ]);
        }
//        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'gps');

        // 获取车辆gps数据
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('LINDEX',
            [
                $order->getRentalCar()->getDeviceCompany()->getEnglishName() . '-gps-'
                . $order->getRentalCar()->getBoxId(),
                0
            ]
        );
        $gps_json = $redis->executeCommand($redis_cmd);

        $gps_arr = json_decode($gps_json,true);

        if(!empty($gps_arr)&&((new \DateTime())->getTimestamp() - $gps_arr['time']< 15 * 60)){


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
        if($order->getRentalCar()->getDeviceCompany()->getEnglishName()=='carStart') {

            $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'status', $member);
            sleep(1);
            $redis = $this->container->get('snc_redis.default');

            $redis_cmd = $redis->createCommand('lindex', array($order->getRentalCar()->getDeviceCompany()
                    ->getEnglishName() . '-status-' . $order->getRentalCar()->getBoxId(), 0));

            $fire_json = $redis->executeCommand($redis_cmd);
            $fire_arr = json_decode($fire_json, true);

            if ((new \DateTime())->getTimestamp() - $fire_arr['time'] > 10) {

                return new JsonResponse([
                    'errorCode' => self::E_ON_FIRE,
                    'errorMessage' => self::M_ON_FIRE
                ]);
            }

            if ($fire_arr['status'] == '6F07') {
                return new JsonResponse([
                    'errorCode' => self::E_ON_FIRE,
                    'errorMessage' => self::M_ON_FIRE
                ]);
            }
        }

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

	    // 分时保险结单开始
        $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_under_write'), ['OrderID'=>$order->getId()]);
        // 分时保险结单结束

        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'off',$member);
        $password = $this->get_random_integer(4);

//        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'encode',$member,$password);

        $redis_cmd= $redis->createCommand('del',array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName().'-password-'.$order->getRentalCar()->getBoxId()));
        $redis->executeCommand($redis_cmd);


        $carPartnerData = [
            'carId'   => $order->getRentalCar()->getId(),
            'orderId' => $order_id,
            'backRentalStation' => $order->getReturnStation()->getId(),
        ];

        $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'order'     => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                $order),
        ]);

    }


    /**
     *检查付款信息是否正确
     * @Route("/rentOrderPay", methods="POST")
     */
    public function RentOrderPayAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->getInt('orderID');
        $coupon_id = $req->request->getInt('couponID');
        $wallet = $req->request->get('wallet');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if(empty($order)) {

            return new JsonResponse([
                'errorCode' => self::E_NO_ORDER,
                'errorMessage' => self::M_NO_ORDER,
            ]);

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


            if(empty($coupon)||$coupon->getMember()!=$member||$coupon->getEndTime()->format('Y-m-d H:i:s')<date('Y-m-d
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
        $orderAmount = $charge['cost'] - $coupon_amount;

        $order_wallet_amount = 0;


        if($member->getWallet()>0&&$wallet >0){

            if($member->getWallet()>$orderAmount){

                $order_wallet_amount = $orderAmount;
                $orderAmount=0;

            }else{
                $orderAmount = $orderAmount - $member->getWallet();
                $order_wallet_amount = $member->getWallet();
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
            'order'=>call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                $order),
        ]);

    }



    /**
     * @Route("/changeReturnStation", methods="POST",name="auto_api_order_changeReturnStation")
     */

    public function changeReturnStationAction(Request $req){

        $uid = $req->request->get('userID');
        $order_id = $req->request->getInt('orderID');
        $rental_station_id = $req->request->getInt('rentalStationID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        $this->checkMemberRentalOrder($member,$rentalOrder);

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

        if($rentalStation->getUsableParkingSpace()<=$rental_car_count){

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
