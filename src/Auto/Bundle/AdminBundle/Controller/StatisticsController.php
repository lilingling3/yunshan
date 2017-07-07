<?php

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Process\Process;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Auto\Bundle\ManagerBundle\Form\RentalCarType;
use Doctrine\ORM\EntityManager;

/**
 * @Route("/statistics")
 */

class StatisticsController extends Controller
{
    /**
     * @Route("/list", methods="GET", name="auto_admin_statistics_list")
     * @Template()
     */
    public function listAction(Request $req)
    {
        $starttime = $req->query->get('starttime');
        //$endtimeOrigin= $req->query->get('endtime');
        $endtime = $req->query->get('endtime');

        $qbm =$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->createQueryBuilder('m');
        //总注册用户
        $members =$qbm->select($qbm->expr()->count('m'))
            ->getQuery()
            ->getSingleScalarResult();

        $qbam =$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->createQueryBuilder('am') ;
        //总提交审核用户
        $verifications = $qbam
            ->select($qbam->expr()->count('am'))
            ->getQuery()
            ->getSingleScalarResult();
        //总审核通过用户
        $verifieds = $qbam
            ->select($qbam->expr()->count('am'))
            ->where($qbam->expr()->eq('am.licenseAuthError', ':licenseAuthError'))
            ->setParameter('licenseAuthError', 0)
            ->getQuery()
            ->getSingleScalarResult();

        $qbrco =$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->createQueryBuilder('rco') ;
        //总充值用户数
        $recharge_members = $qbrco
            ->select($qbrco->expr()->countDistinct('rco.member'))
            ->where($qbrco->expr()->isNotNull('rco.payTime'))
            ->getQuery()
            ->getSingleScalarResult();
        //总充值金额
        $recharge_fee = $qbrco
            ->select('sum(rco.actualAmount)')
            ->where($qbrco->expr()->isNotNull('rco.payTime'))
            ->getQuery()
            ->getSingleScalarResult();

        $qbro =$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->createQueryBuilder('ro') ;
        //总订单数
        $order_amount = $qbro
            ->select($qbro->expr()->count('ro'))
            ->getQuery()
            ->getSingleScalarResult();
        //总取消订单
        $cancel_orders_amount = $qbro
            ->select($qbro->expr()->count('ro'))
            ->where($qbro->expr()->isNotNull('ro.cancelTime'))
            ->getQuery()
            ->getSingleScalarResult();
        //总完成订单应收费用（元）
        $finished_orders_amount = $qbro
            ->select('sum(ro.dueAmount)')
            ->where($qbro->expr()->isNotNull('ro.payTime'))
            ->getQuery()
            ->getSingleScalarResult();
        //总完成订单实收费用（元）
        $finished_orders_actual_fee_amount = $qbro
            ->select('sum(ro.amount)')
            ->where($qbro->expr()->isNotNull('ro.payTime'))
            ->getQuery()
            ->getSingleScalarResult();

        //总优惠券抵用
        $finished_orders_coupon_fee_amount = $finished_orders_amount-$finished_orders_actual_fee_amount;
        return ['starttime'=>$starttime,'endtime'=>$endtime,
            'members'=>$members,
            'verifications'=>$verifications,
            'verifieds'=>$verifieds,
            'recharge_members'=>$recharge_members,
            'recharge_fee'=>$recharge_fee,
            "order_amount"=>$order_amount,
            "cancel_orders_amount"=>$cancel_orders_amount,
            "finished_orders_amount"=>$finished_orders_amount,
            "finished_orders_coupon_fee_amount"=>$finished_orders_coupon_fee_amount,
            "finished_orders_actual_fee_amount"=>$finished_orders_actual_fee_amount
        ];
    }

    /**
     * @Route("/amount", methods="GET", name="auto_admin_statistics_amount")
     * @Template()
     */
    public function amountAction(Request $req)
    {
        $starttime = $req->query->get('starttime');
        $endtime = $req->query->get('endtime');
        $startAmount = null;
        $endAmount = null;
        if($starttime || $endtime){
            $startAmount = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:StatisticsAmountRecord')
                ->findOneBy(['dateTime'=>new \DateTime(date('Y-m-d',strtotime($starttime)))]);
            $endAmount = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:StatisticsAmountRecord')
                ->findOneBy(['dateTime'=>new \DateTime(date('Y-m-d',strtotime($endtime)))]);
        }
        $amount = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:StatisticsAmountRecord')
            ->findOneBy([],['dateTime' => 'DESC']);
        
        return ['starttime'=>$starttime,'endtime'=>$endtime,'startAmount'=>$startAmount,'endAmount'=>$endAmount,'amount'=>$amount ];
    }



    /**
     * @Route("/search", methods="POST", name="auto_admin_statistics_search")
     * @Template()
     */
    public function statisticsSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_statistics_amount',
                [
                    'starttime' => $req->request->get('starttime'),
                    'endtime' => $req->request->get('endtime'),
                ]
            )
        );

    }

    /**
     * @Route("/operateSearch", methods="POST", name="auto_admin_statistics_operate_search")
     * @Template()
     */
    public function operateSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_statistics_operate',
                [
                    'rental_station'=>$req->request->get('rental_station'),
                    'start_time' => $req->request->get('start_time'),
                    'end_time' => $req->request->get('end_time'),
                ]
            )
        );

    }
    /**
     * @Route("/operate", methods="GET", name="auto_admin_statistics_operate")
     * @Template()
     */
    public function operateAction(Request $req)
    {
        $rental_station = $req->query->get('rental_station');
        $start_Time = $req->query->get('start_time');
        $end_Time = $req->query->get('end_time');
      // echo ((new \DateTime('2016-04-12 10:00:00'))->getTimestamp()-(new \DateTime('2016-04-11 17:31:11'))->getTimestamp())/3600;
        $start_timestamp = (new \DateTime($start_Time))->getTimestamp();
        $end_timestamp = (new \DateTime($end_Time))->getTimestamp();
        function diffBetweenTwoDays($day1, $day2)
        {
            $second1 = strtotime($day1);
            $second2 = strtotime($day2);

            if ($second1 < $second2) {
                $tmp = $second2;
                $second2 = $second1;
                $second1 = $tmp;
            }
            return round(($second1 - $second2) / 86400);
        }

        $days = diffBetweenTwoDays($start_Time, $end_Time);

        date_default_timezone_set("PRC");
        $start_time = $start_Time ? $start_Time : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 * 7));
        $end_time = $end_Time ? $end_Time : date("Y-m-d H:i:s");
        $endTime = $end_time ? $end_time : date("Y-m-d H:i:s");


        //全部城市
        $citys = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->findBy(['parent' => null], ['id' => 'ASC']);
        //全部车型
        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();

        //租赁点
        $rentalStations = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $rentalPayOrders =

            $qb
                ->select('o')
                ->where($qb->expr()->isNull('o.cancelTime'))
               // ->andWhere($qb->expr()->isNotNull('o.useTime'))
                ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->getQuery()
                ->getResult();


        $rental_time = [];
        $rental_count = [];
        $rental_amount = [];
        $coupon_amount = [];
        $rental_revenue = [];
        $day_time = [];
        $night_time = [];
        $day_count = [];
        $night_count = [];
        if ($rental_station) {
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb->select('o')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
//                    ->andWhere($qb->expr()->orX(
//                        $qb->expr()->gte('o.endTime', ':startTime'),
//                        $qb->expr()->isNull('o.endTime')
//                    ))
                    ->andWhere($qb->expr()->eq('o.pickUpStation', ':rentalStation'))
                    ->setParameter('rentalStation', $rental_station)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();

            $rentalStation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findOneBy(['id' => $rental_station]);

        } else {
            $rentalCars = null;
            $rentalStation = null;
            $carTypes = null;

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');

            $rentalOrders =
                $qb
                    ->select('o')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('endTime', $end_time)
                    ->setParameter('startTime', $start_time)
                    ->getQuery()
                    ->getResult();
        }



        foreach ($rentalPayOrders as $order) {
            if (!isset($rental_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $rental_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }

            if (!isset($coupon_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $coupon_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }

            if (!isset($rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }
            if($order->getPayTime()){
                if ($order->getPayTime()->format('Y-m-d H:i:s') > $start_time && $order->getPayTime()->format('Y-m-d H:i:s')
                    < $end_time
                ) {
                    $rental_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += $order->getAmount();

                    if (!empty($order->getCoupon()) && $order->getCoupon()->getUseTime()) {
                        $coupon_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += $order->getCoupon()->getKind()->getAmount();
                    }

                }
            }else{
                $rental_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] =0;
                $coupon_amount[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }
        }

        foreach ($rentalOrders as $order) {
            if (!isset($rental_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $rental_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }

            if (!isset($rental_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $rental_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }


            if (!isset($day_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $day_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }

            if (!isset($night_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                $night_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
            }


            $start =  $order->getCreateTime()->getTimestamp() ;
            $end = $order->getEndTime() ? $order->getEndTime()->getTimestamp(): ($end_timestamp>strtotime(date("Y-m-d H:i:s"))?strtotime(date("Y-m-d H:i:s")):$end_timestamp);

            if ($order->getEndTime()) {
                if ($order->getEndTime()->getTimestamp() >= $end_timestamp) {
                    $end = $end_timestamp;
                }
            }
            $rental_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += ($end - $start);

            $rental_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += 1;

            if ($order->getCreateTime()->format('H:i:s') >= '08:00:00' && $order->getCreateTime()->format('H:i:s')
                <= '20:00:00'
            ) {
                $day_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += 1;
            } else {
                $night_count[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += 1;

            }


            if ($order->getEndTime() && ($order->getEndTime()->getTimestamp() > $end_timestamp)) {
                $order1 = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
                $order1->setEndTime(new \DateTime($end_time));

                $order1->setRentalCar($order->getRentalCar());
                $order1->setCreateTime($order->getCreateTime());
                $order1->setPickUpStation($order->getPickUpStation());
                if (!isset($day_time[$order1->getPickUpStation()->getId()][$order1->getRentalCar()->getId()])) {
                    $day_time[$order1->getPickUpStation()->getId()][$order1->getRentalCar()->getId()] = 0;
                }
                if (!isset($night_time[$order1->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                    $night_time[$order1->getPickUpStation()->getId()][$order1->getRentalCar()->getId()] = 0;
                }
                $order_cost = $this->get('auto_manager.order_helper')->get_charge_details($order1);
                foreach ($order_cost['charge']['rentalPrice'] as $cost) {
                    if ($cost['startTime'] == '08:00') {
                        $day_time[$order1->getPickUpStation()->getId()][$order1->getRentalCar()->getId()] += $cost['time'];
                    } elseif ($cost['startTime'] == '20:00') {
                        $night_time[$order1->getPickUpStation()->getId()][$order1->getRentalCar()->getId()] += $cost['time'];
                    }
                }

                $order_cost = $this->get('auto_manager.order_helper')->get_charge_details($order1);
                if(!isset($rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])){
                    $rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()]=0;
                }
                $rental_revenue[$order1->getPickUpStation()->getId()][$order1->getRentalCar()->getId()] += $order_cost['cost'];
            } else {
                if (!isset($day_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                    $day_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
                }
                if (!isset($night_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])) {
                    $night_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] = 0;
                }
                $order_cost = $this->get('auto_manager.order_helper')->get_charge_details($order);
                foreach ($order_cost['charge']['rentalPrice'] as $cost) {
                    if ($cost['startTime'] == '08:00') {
                        $day_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += $cost['time'];
                    } elseif ($cost['startTime'] == '20:00') {
                        $night_time[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += $cost['time'];
                    }
                }
                if(!isset($rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()])){
                    $rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()]=0;
                }
                $rental_revenue[$order->getPickUpStation()->getId()][$order->getRentalCar()->getId()] += $order_cost['cost'];

            }
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DispatchRentalCar')
                ->createQueryBuilder('dr');
        $dispatchAlls =
            $qb
                ->select('dr')
                ->andWhere($qb->expr()->lte('dr.createTime', ':endTime'))
                ->andWhere($qb->expr()->eq('dr.status', ':status'))
                ->setParameter('status', 1)
                ->setParameter('endTime', $end_time)
                ->orderBy('dr.createTime', 'ASC')
                ->getQuery()
                ->getResult();

        $dispatch_count=[];$countTime=[];$count=[];$stay_time = [];
        if($dispatchAlls){
            foreach($dispatchAlls as $car){
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:DispatchRentalCar')
                        ->createQueryBuilder('dr');
                $dispatchs =
                    $qb
                        ->select('dr')
                        ->andWhere($qb->expr()->eq('dr.rentalCar', ':car'))
                        ->andWhere($qb->expr()->lte('dr.createTime', ':endTime'))
                        ->andWhere($qb->expr()->eq('dr.status', ':status'))
                        ->setParameter('status', 1)
                        ->setParameter('endTime', $end_time)
                        ->setParameter('car', $car->getRentalCar()->getId())
                        ->orderBy('dr.createTime', 'ASC')
                        ->getQuery()
                        ->getResult();
                $cars=array();$n=0;$time=[];$tid=null;$t=[];
                if($dispatchs){
                    $count_dispatch=[];
                    if(count($dispatchs)==1){
                        foreach($dispatchs as $dispatch){
                            if($dispatch->getRentalStation()->getId()==$rental_station){
                                if($dispatch->getKind()==1){
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                    $t['startTime']=$dispatchTime->getTimestamp()>$dispatch->getCreateTime()->getTimestamp()?$dispatchTime:$dispatch->getCreateTime();
                                }
                                if($dispatch->getKind()==2){
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                    $t['startTime']=$dispatchTime->getTimestamp()>$dispatch->getRentalOrder()->getEndTime()->getTimestamp()?$dispatchTime:$dispatch->getRentalOrder()->getEndTime();
                                }
                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                $t['endTime']=$dispatchTime;
                            }

                            $time[]=$t;
                        }
                        foreach($dispatchs as $dispatch){
                            if($dispatch->getRentalStation()->getId()==$rental_station){
                                if (!isset($stay_time[$rental_station][$car->getRentalCar()->getId()])) {
                                    $stay_time[$rental_station][$car->getRentalCar()->getId()] = 0;
                                }
                                if(!empty($stay_time[$rental_station][$car->getRentalCar()->getId()])){
                                    $stay_time[$rental_station][$car->getRentalCar()->getId()]=0;
                                }
                                foreach($time as $tim) {
                                    if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                        $stay_time[$rental_station][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                ->getTimestamp());
                                    }
                                }
                            }
                        }
                    }else{
                        $Timedispatchs=array();
                        foreach($dispatchs as $dispatch){
                            if(!isset($dispatch_count[$dispatch->getRentalCar()->getId()])){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]=0;
                            }
                            if($dispatch->getCreateTime()->getTimestamp()>=(new \DateTime($start_time))->getTimestamp() && $dispatch->getCreateTime()->getTimestamp()<=(new \DateTime($end_time))->getTimestamp()){
//                                echo '移库时间'.var_dump($dispatch->getCreateTime());
                                $Timedispatchs[]=$dispatch;
                            }
                        }

                        if($Timedispatchs){
                        if(count($Timedispatchs)==1){
                            foreach($Timedispatchs as $Timedispatch){
                                if($Timedispatch->getRentalStation()->getId()==$rental_station){
                                    if($Timedispatch->getKind()==1){
                                        $t['startTime']=$Timedispatch->getCreateTime();
                                    }
                                    if($Timedispatch->getKind()==2){
                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                        $t['startTime']=$dispatchTime->getTimestamp()>$Timedispatch->getRentalOrder()->getEndTime()->getTimestamp()?$Timedispatch->getRentalOrder()->getEndTime():$dispatchTime;
//                                    $t['startTime']=$dispatch->getRentalOrder()->getEndTime();
                                    }
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                    $t['endTime']=$dispatchTime;
                                }
                                if($Timedispatch->getRentalStation()->getId()!=$rental_station){
                                    if($Timedispatch->getKind()==1){
                                        $t['endTime']=$Timedispatch->getCreateTime();
                                    }
                                    if($Timedispatch->getKind()==2){
                                        if($Timedispatch->getRentalOrder()->getEndTime()){
                                            $t['endTime']=$Timedispatch->getRentalOrder()->getEndTime();
                                        }else{
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime;
                                        }

                                    }

                                    $dispatchTime=(new \DateTime($start_time));
                                    $t['startTime']=$dispatchTime;
                                }
                                $time[]=$t;
                            }
                            foreach($Timedispatchs as $Timedispatch){
                                if($Timedispatch->getRentalStation()->getId()==$rental_station){
                                    if(!empty($stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                        $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                                    }
                                    foreach($time as $tim){
                                        if(!isset($stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                            $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                                        }
                                        if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                            $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                    ->getTimestamp());
                                        }
                                       }
                                }else{
                                    $n=1;
                                    foreach($dispatchs as $dispatch){
                                        if($n==(count($dispatchs)-1) && $dispatch->getRentalStation()->getId()==$rental_station){
                                            if(!empty($stay_time[$rental_station][$Timedispatch->getRentalCar()->getId()])){
                                                $stay_time[$rental_station][$Timedispatch->getRentalCar()->getId()]=0;
                                            }
                                            foreach($time as $tim){
                                                if(!isset($stay_time[$rental_station][$Timedispatch->getRentalCar()->getId()])){
                                                    $stay_time[$rental_station][$Timedispatch->getRentalCar()->getId()]=0;
                                                }
                                                if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                                    $stay_time[$rental_station][$Timedispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                            ->getTimestamp());
                                                }
                                            }
                                        }
                                        $n+=1;
                                    }

                                }
                            }
                        }else{
                        if(!isset($count_dispatch[$rental_station][$car->getRentalCar()->getId()])){
                            $count_dispatch[$rental_station][$car->getRentalCar()->getId()]=0;
                        }
                        if(!empty($count_dispatch[$rental_station][$car->getRentalCar()->getId()])){
                            $count_dispatch[$rental_station][$car->getRentalCar()->getId()]=0;
                        }
                        foreach($Timedispatchs as $Timedispatch){

                                $cars[$n]=$Timedispatch;
                                $n+=1;

                            if(!isset($count_dispatch[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                $count_dispatch[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                            }
                            $count_dispatch[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]+=1;
                        }
//                        echo '$count_dispatch'.var_dump($count_dispatch);
                        if($count_dispatch[$rental_station][$car->getRentalCar()->getId()]==1){
//                            echo 'count($dispatchs)'.count($dispatchs);
                            for($i=0;$i<count($Timedispatchs);$i++){
                                if(($rental_station==$cars[$i]->getRentalStation()->getId())&&($car->getRentalCar()->getId()==$cars[$i]->getRentalCar()->getId())){
                                    if($i==0){
                                        if($cars[$i]->getKind()==1){
                                            $t['startTime']=$cars[$i]->getCreateTime();
                                        }
                                        if($cars[$i]->getKind()==2){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                            $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$i]->getRentalOrder()->getEndTime():$dispatchTime;
                                        }
                                        if($cars[$i+1]->getKind()==1){
                                            $t['endTime']=$cars[$i+1]->getCreateTime();
                                        }
                                        if($cars[$i+1]->getKind()==2){
                                            if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime->getTimestamp()>$cars[$i+1]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$i+1]->getRentalOrder()->getEndTime():$dispatchTime;
                                            }else{
                                                $t['endTime']=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            }
                                        }
                                    }else if($i==(count($Timedispatchs)-1)){
                                        if($cars[$i]->getKind()==1){
                                            if(isset($cars[$i+1])){
                                                $dTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                $dispatchTime=$dTime->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?$dTime:$cars[$i+1]->getCreateTime();
                                            }else{
                                           $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            }
                                            $t['endTime']=$dispatchTime;
                                        }
                                        if($cars[$i]->getKind()==2){
                                            if($cars[$i]->getRentalOrder()->getEndTime()){
                                                $dTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                $dispatchTime=$dTime->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?$dTime:$cars[$i]->getRentalOrder()->getEndTime();
                                            }else{
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();

                                            }
                                            $t['endTime']=$dispatchTime;
                                        }
                                        if($cars[$i]->getKind()==1){
                                            $t['startTime']=$cars[$i]->getCreateTime();
                                        }
                                        if($cars[$i]->getKind()==2){
                                            if($cars[$i]->getRentalOrder()->getEndTime()){
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                                $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?$dispatchTime:$cars[$i]->getRentalOrder()->getEndTime();

                                            }else{
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                                $t['startTime']=$dispatchTime;

                                            }
                                        }

                                    }else{
                                        if($cars[$i]->getKind()==1){
                                            $t['startTime']=$cars[$i]->getCreateTime();
                                        }
                                        if($cars[$i]->getKind()==2){
                                            $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                        }
                                        if($cars[$i+1]->getKind()==1){
                                            $t['endTime']=$cars[$i+1]->getCreateTime();
                                        }
                                        if($cars[$i+1]->getKind()==2){
                                            if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                            }else{
                                                $t['endTime']=new \DateTime();
                                            }

                                        }

                                    }

                                    $time[]=$t;
                                }
                            }
                            foreach($Timedispatchs as $Timedispatch){
                                $dispatch_count[$Timedispatch->getRentalCar()->getId()]+=1;
                                    if(!empty($stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                        $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                                    }
                                    foreach($time as $tim){
                                        if(!isset($stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                            $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                                        }
                                        if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                            $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                    ->getTimestamp());
                                        }
                                    }

                            }

                        }else
                        {

                        for($i=0;$i<count($Timedispatchs);$i++){
                            if(isset($t['endTime'])){
                                $t=[];
                            }
                                if($cars[$i]->getRentalStation()->getId()==$rental_station){
                                    if($i==0){
                                        if($cars[$i]->getKind()==1){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?new \DateTime():(new \DateTime($start_time));
                                            $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getCreateTime()->getTimestamp()?$cars[$i]->getCreateTime():$dispatchTime;
                                      //  echo '$t["startTime"]'.var_dump($t['startTime']);
                                        }
                                        if($cars[$i]->getKind()==2){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                            if($cars[$i]->getRentalOrder()->getEndTime()) {
                                                $t['startTime'] = $dispatchTime->getTimestamp() > $cars[$i]->getRentalOrder()->getEndTime()->getTimestamp() ?$cars[$i]->getRentalOrder()->getEndTime()  :$dispatchTime ;
                                            }else{
                                                $t['startTime']=$dispatchTime;
                                            }
                                        }
                                    }else{
                                        if($cars[$i]->getKind()==1){
                                            $t['startTime']=$cars[$i]->getCreateTime();
                                        }
                                        if($cars[$i]->getKind()==2){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$i]->getRentalOrder()->getEndTime():$dispatchTime;
                                        }
                                    }
                                }

                            if(($i+1)==count($Timedispatchs)){
                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                $t['endTime']=$dispatchTime;
                                $time[]=$t;
                                break;
                            }
                            for($j=$i+1;$j<count($Timedispatchs);$j++){
                                if($cars[$j]->getRentalStation()->getId()==$rental_station){
                                    //array_push($arr_index,$j);
                                    if($j==(count($Timedispatchs)-1)){
                                        if($cars[$j]->getKind()==1){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                            $time[]=$t;
                                            break;
                                        }
                                        if($cars[$j]->getKind()==2){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            if($cars[$j]->getRentalOrder()->getEndTime()){
                                                $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                            }else{
                                                $t['endTime']=$dispatchTime;

                                            }

                                            $time[]=$t;
                                            break;
                                        }
                                    }else{
                                        if($cars[$j]->getKind()==1){
                                            $t['endTime']=$cars[$j]->getCreateTime();
                                            $time[]=$t;
                                            break;
                                        }
                                        if($cars[$j]->getKind()==2){
                                            $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                            $time[]=$t;
                                            break;
                                        }
                                    }

                                }
                                if($cars[$j]->getRentalStation()->getId()!=$rental_station){
                                    if($j==(count($Timedispatchs)-1)){
                                        if($cars[$j]->getKind()==1){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                            $time[]=$t;
                                            break;
                                        }
                                        if($cars[$j]->getKind()==2){
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            if($cars[$j]->getRentalOrder()->getEndTime()){
                                                $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                            }else{
                                                $t['endTime']=$dispatchTime;

                                            }
                                            $time[]=$t;
                                            break;
                                        }
                                    }else{
                                        if($cars[$j]->getKind()==1){
                                            $t['endTime']=$cars[$j]->getCreateTime();
                                            $time[]=$t;
                                            break;
                                        }
                                        if($cars[$j]->getKind()==2){
                                            $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                            $time[]=$t;
                                            break;
                                        }
                                    }

                                }
                            }
                        }}
                        foreach($Timedispatchs as $Timedispatch){
                            $dispatch_count[$Timedispatch->getRentalCar()->getId()]+=1;
                            if(!isset($countTime[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                $countTime[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                            }
                            if(!empty($stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;

                            }
                            foreach($time as $tim){

                                if(!isset($stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()])){
                                    $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]=0;
                                }
                                if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                    $stay_time[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                            ->getTimestamp());
                                }


                                $countTime[$Timedispatch->getRentalStation()->getId()][$Timedispatch->getRentalCar()->getId()]+=1;
                            }

                        }
                    }
                    }else{
                            $n=1;
                            foreach($dispatchs as $dispatch){
                                if($n==count($dispatchs) && $dispatch->getRentalStation()->getId()==$rental_station){
                                    $stay_start = $car->getRentalCar()->getCreateTime()->format('Y-m-d H:i:s')>$start_time?$car->getRentalCar()->getCreateTime()->format
                                    ('Y-m-d H:i:s'):$start_time;
                                    $stay_end = (new \DateTime())->format('Y-m-d H:i:s')>$end_time?
                                        $end_time:(new \DateTime())->format('Y-m-d  H:i:s');
                                    if(!isset($stay_time[$rental_station][$car->getRentalCar()->getId()])){
                                        $stay_time[$rental_station][$car->getRentalCar()->getId()]=0;
                                    }

                                    $stay_time[$rental_station][$car->getRentalCar()->getId()] = (new \DateTime($stay_end))
                                            ->getTimestamp() -(new \DateTime($stay_start))->getTimestamp();
                                }
                                $n+=1;
                            }
                    }
                    }
                }
            }
        }else{
            $stay_time=null;
        }

        $rentalCarsOne=[];
        foreach($dispatchAlls as $dispatchAll){
            $rentalCarsOne[]=[
                "rentalCarId"=>$dispatchAll->getRentalCar()->getId(),
                "carId"=> $dispatchAll->getRentalCar()->getCar()->getId(),
                "carName"=> $dispatchAll->getRentalCar()->getCar()->getName(),
                "rentalCarLicense"=> $dispatchAll->getRentalCar()->getLicense(),

            ];
        }
        function array_unique_fb($array2D)
        {
            foreach ($array2D as $k=>$v)
            {
                $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
                $temp[$k] = $v;
            }
            $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组
            foreach ($temp as $k => $v)
            {
                $array=explode(",",$v); //再将拆开的数组重新组装
                $temp2[$k]["rentalCarId"] =$array[0];
                $temp2[$k]["carId"] =$array[1];
                $temp2[$k]["carName"] =$array[2];
                $temp2[$k]["rentalCarLicense"] =$array[3];
            }
            return $temp2;
        }


        $rentalsCar=array_unique_fb($rentalCarsOne);






        return ['rentalCars'=>$rentalsCar,'rental_time'=>$rental_time,'stay_time'=>$stay_time,
            'rental_count'=>$rental_count,'rental_amount'=>$rental_amount,'coupon_amount'=>$coupon_amount,
            'rental_revenue'=>$rental_revenue,'night_time'=>$night_time,'day_time'=>$day_time,'day_count'=>$day_count,'night_count'=>$night_count,
            'start_time'=>$start_time,'end_time'=>$endTime,'rentalStations'=>$rentalStations,'rentalStation'=>$rentalStation,
            'count_rentalCars'=>count($dispatchAlls),'citys'=>$citys,'cars'=>$cars,'days'=>$days,'dispatch_count'=>$dispatch_count,'count'=>$count];

    }

    /**
     * @Route("/car/operate", methods="GET", name="auto_admin_statistics_car_operate")
     * @Template()
     */
    public function carOperateAction(Request $req){
        $rental_station=$req->query->get('rental_station');
        $start_time = $req->query->get('start_time');
        //$end_time = $req->query->get('end_time');
        $endtimeOrigin= $req->query->get('end_time');
        if($endtimeOrigin){$end_time = date("Y-m-d H:m:s",strtotime($endtimeOrigin)+86400);}
        else{$end_time=null;}


        $start_time = $start_time?$start_time:date("Y-m-d H:m:s",(strtotime(date("Y-m-d H:m:s"))-86400*7));
        $end_time = $end_time?$end_time:date("Y-m-d H:m:s");

        $endTime=$endtimeOrigin?$endtimeOrigin:date("Y-m-d H:m:s");



        $rentalStations =$this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();

        $rentalStation=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findOneBy(['id'=>$rental_station]);
        if($rental_station){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.rentalStation','oc')
            ;
            $rentalCars =
                $qb
                    ->Where($qb->expr()->eq('oc.id',':rentalStation'))
                    ->setParameter('rentalStation', $rental_station)
                    ->getQuery()
                    ->getResult() ;

            $carTypes =$qb->select('COUNT(DISTINCT o.car)')
                ->getQuery()
                ->getSingleScalarResult();
        }else{
            $rentalCars=[];
            $carTypes=null;
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
                ->createQueryBuilder('r');

        $onlineRecords =

            $qb
                ->select('r')
                ->where($qb->expr()->gte('r.createTime',':startTime'))
                ->andWhere($qb->expr()->lte('r.createTime',':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->orderBy('r.createTime','ASC')
                ->getQuery()
                ->getResult() ;


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $rentalOrders =

            $qb
                ->select('o')
                ->where( $qb->expr()->isNull('o.cancelTime') )
                ->andWhere($qb->expr()->gte('o.endTime',':startTime'))
                ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->orderBy('o.createTime','ASC')

                ->getQuery()
                ->getResult() ;

        $rental_time = [];
        $offline_time = [];
        $online_time = [];

        if($rentalCars){
            foreach($rentalCars as $car){

                if(!isset($rental_time[$car->getId()])){

                    $rental_time[$car->getId()] = 0;

                }

            }
        }


        $online_records = [];
        foreach($onlineRecords as $record){
            $online_records[$record->getRentalCar()->getId()][]=['status'=>$record->getStatus(),
                'timestamp'=>$record->getCreateTime()->getTimestamp()];
        }


        foreach($online_records as $key=>$records){

            $record_count = count($online_records[$key]);
            $offline_time[$key] = 0;
            $online_time[$key] = 0;

            foreach($records as $k=>$value){
                if($k==0){
                    if($value['status'] == 1)
                        $offline_time[$key] += $value['timestamp'] - (new \DateTime($start_time))->getTimestamp();

                    if($value['status']==0){
                        $online_time[$key] += $value['timestamp'] - (new \DateTime($start_time))->getTimestamp();
                    }
                }elseif($key==$record_count){

                    if($value['status']== 1)
                        $online_time[$key] += (new \DateTime($end_time))->getTimestamp() -$value['timestamp'];

                    if($value['status']==0)
                        $offline_time[$key] += (new \DateTime($end_time))->getTimestamp() -$value['timestamp'];

                }else{

                    foreach($online_records[$key] as $r){

                        if($r['timestamp']>$value['timestamp']&&$value['status']!=$r['status']){

                            if($record->getStatus() == 1)
                                $online_time[$key] += $r['timestamp'] - $value['timestamp'];

                            if($record->getStatus()==0){
                                $offline_time[$key] += $r['timestamp'] - $value['timestamp'];
                            }
                        }

                    }
                }
            }
        }

        foreach($rentalOrders as $order){
            if(!isset($rental_time[$order->getRentalCar()->getId()])){

                $rental_time[$order->getRentalCar()->getId()]=0;

            }
            $end = $order->getEndTime()?$order->getEndTime():(new \DateTime($end_time));
            $start = $order->getCreateTime()->getTimestamp()>(new \DateTime($start_time))->getTimestamp()
                ?$order->getCreateTime():(new \DateTime($start_time));

            $rental_time[$order->getRentalCar()->getId()]+=($end->getTimestamp()-$start->getTimestamp());

        }

        $wait_rental_time = [];

        //待租赁时长
        foreach($onlineRecords as $record){


            if($record->getStatus()==1){


                if(!isset($wait_rental_time[$record->getRentalCar()->getId()])){

                    $wait_rental_time[$record->getRentalCar()->getId()] = 0;

                }

                $s = $record->getCreateTime()->getTimestamp();
                $order_end = $online_end = $e = 0;

                foreach($onlineRecords as $v){

                    if($v->getStatus()==0&&$v->getCreateTime()->getTimestamp()>$record->getCreateTime()->getTimestamp()
                        &&$record->getRentalCar() == $v->getRentalCar()){
                        $online_end = $v->getCreateTime()->getTimestamp();
                        break;
                    }

                }

                foreach($rentalOrders as $o){

                    if($o->getCreateTime()->getTimestamp()>$record->getCreateTime()->getTimestamp()
                        &&$record->getRentalCar() == $o->getRentalCar()){
                        $order_end = $o->getCreateTime()->getTimestamp();
                        break;
                    }

                }

                if($order_end){
                    $e = $order_end;
                }
                if($online_end&&$online_end<$order_end){
                    $e = $online_end;
                }

                if(!$e){
                    $e = (new \DateTime($end_time))->getTimestamp();
                }

                $wait_rental_time[$record->getRentalCar()->getId()] += $e - $s;

            }

        }


        //整备时长 - 用户还车人工还车时间到上线时间

        $setup_time = [];

        foreach($onlineRecords as $record){
            if($record->getStatus()==0&&(in_array(13,$record->getReason())||in_array(15,$record->getReason()))){
                if(!isset($setup_time[$record->getRentalCar()->getId()])){
                    $setup_time[$record->getRentalCar()->getId()] = 0;
                }

                foreach($onlineRecords as $r){

                    if($r->getStatus()==1&&$record->getRentalCar()==$r->getRentalCar()&&$r->getCreateTime()->getTimestamp()>$record->getCreateTime()->getTimestamp()){

                        $setup_time[$record->getRentalCar()->getId()]+=$r->getCreateTime()->getTimestamp()
                            -$record->getCreateTime()->getTimestamp();
                        break;

                    }

                }
            }

        }

        return ['rentalCars'=>array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars),'rental_time'=>$rental_time,'wait_rental_time'=>$wait_rental_time,'online_time'=>$online_time,'offline_time'=>$offline_time,'setup_time'=>$setup_time,'rentalStations'=>$rentalStations,'rentalStation'=>$rentalStation,
            'start_time'=>$start_time,'end_time'=>$endTime,'carTypes'=>$carTypes,'count_rentalCars'=>count($rentalCars)];

    }




    /**
     * @Route("/location", methods="get", name="auto_admin_statistics_location")
     * @Template()
     */
    public function locationAction(Request $req){
        $city=$req->query->get('city');
        $rental_station=$req->query->get('rental_station');
        $plate_number=$req->query->get('plate_number');
        $licensePlace=$req->query->get('license_place');
        $area =$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->findBy(['parent'=>null], ['id' => 'ASC']);
        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        if($city){
            //租赁点
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->createQueryBuilder('r');
            $rentalStations =
                $qb
                    ->select('r')
                    ->join('r.area','a')
                    ->where( $qb->expr()->eq('a.parent',':city') )
                    ->andWhere( $qb->expr()->eq('r.online',':online') )
                    ->setParameter('online', 1)
                    ->setParameter('city', $city)
                    ->getQuery()
                    ->getResult() ;

            $city_now=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['id'=>$city]);
        }else{
            $rentalStations =
                $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->findBy(['online'=>1]);
            $city_now=[];
        }
        if($plate_number&&$licensePlace){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('r');
            $rentalCars =
                $qb
                    ->select('r')
                    ->where( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                    ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                    ->setParameter('licensePlate', $plate_number)
                    ->setParameter('licensePlace', $licensePlace)
                    ->getQuery()
                    ->getResult() ;

//            $rentalCars =$this
//                ->getDoctrine()
//                ->getRepository('AutoManagerBundle:RentalCar')
//                ->findBy(['licensePlate'=>$plate_number],['licensePlace'=>$licensePlace]) ;
            $moveData=$rentalCars;
            $viableCars=0;
        }else{
            $moveData=[];
            if($rental_station){
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalCar')
                        ->createQueryBuilder('o')
                        ->select('o')
                        ->join('o.rentalStation','oc')
                ;
                $rentalCars =
                    $qb
                        ->Where($qb->expr()->eq('oc.id',':rentalStation'))
                        ->setParameter('rentalStation', $rental_station)
                        ->getQuery()
                        ->getResult() ;
                $viableCars=$rentalCars;
            }else{
                $rentalCars=$this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->findAll();
                //运营车辆
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalCar')
                        ->createQueryBuilder('o')
                        ->select('o')
                        ->join('o.rentalStation','oc')
                ;
                $viableCars=$qb
                    ->Where($qb->expr()->eq('oc.online',':online'))
                    ->setParameter('online', 1)
                    ->getQuery()
                    ->getResult() ;
            }
        }
        $car_count=count($rentalCars);
        $viableCars_count=count($viableCars);
        if($rentalCars){
            if(is_array($rentalCars)){

                $rentalCar= array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars);
            }else{
                $rentalcar=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars);
                $rentalCar=array($rentalcar);
            }
        }else{
            $rentalCar=null;
        }
        return ['area'=>$area,'viableCars_count'=>$viableCars_count,'car_count'=>$car_count,'rentalStations'=>$rentalStations,'city_now'=>$city_now,'rentalCars'=>$rentalCar,
            'plate_number'=>$plate_number,'moveData'=>$moveData,'licensePlaces'=>$licensePlaces];
    }

    /**
     * @Route("/car", methods="get", name="auto_admin_statistics_car")
     */

    public function carTypeAction(Request $req){
        $rental_station = $req->query->get('rentalStation');
        $status = $req->query->get('status');
        if($rental_station){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.rentalStation','oc');
            $rentalCars =
                $qb
                    ->Where($qb->expr()->eq('oc.id',':rentalStation'))
                    ->andWhere( $qb->expr()->eq('oc.online',':online') )
                    ->setParameter('online', 1)
                    ->setParameter('rentalStation', $rental_station)
                    ->getQuery()
                    ->getResult() ;
        }else{
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.rentalStation','oc');
            $rentalCars=$qb
                ->Where($qb->expr()->eq('oc.online',':online'))
                ->setParameter('online', 1)
                ->getQuery()
                ->getResult() ;
        }
        if($rentalCars){
            $rental_cars=array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
                $rentalCars);
            $cars = [];$Carstatus=0;
            if($status==300){
                foreach($rental_cars as $car){
                    if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE){
                        $cars[] = $car;
                    }
                }
                $Carstatus=$status;
            }elseif($status==301){
                foreach($rental_cars as $car){
                    if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_UNABLE){
                        $cars[] = $car;
                    }
                }
                $Carstatus=$status;
            }elseif($status==702){
                foreach($rental_cars as $car){
                    if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_CAR_OFFLINE){
                        $cars[] = $car;
                    }
                }
                $Carstatus=$status;
            }elseif($status==404){
                foreach($rental_cars as $car){
                    if(!$car['mileage']){
                        $cars[] = $car;
                    }
                }
                $Carstatus=$status;
            }elseif($status==703){
                foreach($rental_cars as $car){
                    if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_UNABLE && $car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_CAR_OFFLINE){
                        $cars[] = $car;
                    }
                }
                $Carstatus=$status;
            }
        }
        $cars_count=count($cars);
        return new JsonResponse([
            'CarCount' =>  $cars_count,
            'status'=>$Carstatus
        ]);
    }

    /**
     * @Route("/getMileageRecords", methods="get", name="auto_admin_statistics_mileage_records")
     * @Template()
     */
    public function getMileageRecordsAction(Request $req){

        $start_time = $req->query->get('startTime');
        $end_time = $req->query->get('endTime');
        $rental_car_id = $req->query->get('rentalCarID');
        $mileage_list = [];
        if($rental_car_id){
            $rentalCar=$this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->find($rental_car_id) ;

            $redis = $this->container->get('snc_redis.default');
            $redis_cmd= $redis->createCommand('llen',array($rentalCar->getDeviceCompany()->getEnglishName().'-range-'.$rentalCar->getBoxId()));
            $length=$redis->executeCommand($redis_cmd);

            $redis_cmd= $redis->createCommand('lrange',array($rentalCar->getDeviceCompany()->getEnglishName().'-range-'.$rentalCar->getBoxId(),0,$length));
            $mileages=$redis->executeCommand($redis_cmd);
            foreach($mileages as $mileage){

                $m = json_decode($mileage,true);
                if($start_time && $end_time){
                    if((new \DateTime($start_time))->getTimestamp()<=$m['time']&&$m['time']<=(new \DateTime($end_time))->getTimestamp()){
                        $data = ['mileage'=>$m['range'],'time'=>date('Y-m-d H:i:s',$m['time'])];
                        $mileage_list[]=$data;
                    }
                }else{
                    $data = ['mileage'=>$m['range'],'time'=>date('Y-m-d H:i:s',$m['time'])];
                    $mileage_list[]=$data;
                    if(count($mileage_list)>100){
                        break;
                    }
                }


            }
        }

        return ['mileage_list'=>$mileage_list];

    }




    /**
     * @Route("/locationInterface", methods="get", name="auto_admin_statistics_locationInterface")
     * @Template()
     */
    public function locationInterfaceAction(Request $req){
        $city=$req->query->get('city');
        $rental_station=$req->query->get('rental_station');
        $plate_number=$req->query->get('plate_number');
        if($plate_number){
            $rentalCars =$this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findOneBy(['licensePlate'=>$plate_number]) ;
        }else{
            if($rental_station){
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalCar')
                        ->createQueryBuilder('o')
                        ->select('o')
                        ->join('o.rentalStation','oc')
                ;
                $rentalCars =
                    $qb
                        ->Where($qb->expr()->eq('oc.id',':rentalStation'))
                        ->setParameter('rentalStation', $rental_station)
                        ->getQuery()
                        ->getResult() ;
            }else{
                $qb=$this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('rc')
                    ->select('rc')
                    ->join('rc.rentalStation','rs');
                $rentalCars =
                    $qb->andWhere( $qb->expr()->eq('rs.online',':online') )
                        ->setParameter('online', 1)
                        ->getQuery()
                        ->getResult() ;
            }
        }
        if(is_array($rentalCars)){
            $rentalCar= array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars);
        }else{
            $rentalcar=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars);
            $rentalCar=array($rentalcar);
        }
//        $rentalCar=array(
//        array("position"=>array("latitude"=>"39.935367","longitude"=>"116.49285733333"),"mileage"=>"104","status"=>300),
//        array("position"=>array("latitude"=>"39.935367","longitude"=>"116.49285733333"),"mileage"=>"104","status"=>301),
//            );
        return new JsonResponse([
            'rentalCars' =>  $rentalCar,
            'status'=>1
        ]);
    }

    /**
     * @Route("/playBackInterface", methods="get", name="auto_admin_statistics_playBackInterface")
     * @Template()
     */
    public function playBackInterfaceAction(Request $req){
        $plate_number=$req->query->get('plate_number');
        $start_time=$req->query->get('start_time');
        $end_time=$req->query->get('end_time');
        $licensePlace=$req->query->get('plate_place');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('r');
//        $rentalCar =
//            $qb
//                ->select('r')
//                ->where( $qb->expr()->eq('r.licensePlate',':licensePlate') )
//                ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
//                ->setParameter('licensePlate', $plate_number)
//                ->setParameter('licensePlace', $licensePlace)
//                ->getQuery()
//                ->getResult() ;


        $rentalCar=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findOneBy(['licensePlate'=>$plate_number,'licensePlace'=>$licensePlace]) ;

//        $box = $this
//            ->getDoctrine()
//            ->getRepository('AutoManagerBundle:CarStartTbox')
//            ->findOneBy(['rentalCar'=>$rentalCar]) ;
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('llen',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId()));
        $length=$redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('lrange',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId(),0,$length));
        $positions=$redis->executeCommand($redis_cmd);
        $gps_arr=[];

        $mileage = 0;
        if($positions){
            foreach($positions as $key=>$position){

                $gps = json_decode($position,true);
                $gps_arr []= $gps;

                if(isset($positions[$key+1])){
                    $next_gps = json_decode($positions[$key+1],true);
                    $mileage+=$this->get('auto_manager.amap_helper')->straight_distance($gps['coordinate'],$next_gps['coordinate']);

                }



            }
        }
        //var_dump($gps_arr);
        $position_collection=array();
        if($gps_arr){
            foreach($gps_arr as $gps){
                if((new \DateTime($start_time))->getTimestamp()<=$gps['time']&&$gps['time']<=(new \DateTime($end_time))->getTimestamp()){
                    $position_collection[]=$gps;
                }
            }
        }



        return new JsonResponse([
            'position' =>  $position_collection,
            'mileage' => $mileage
        ]);
    }

    /**
     * @Route("/operateTwo", methods="GET", name="auto_admin_statistics_operate_two")
     * @Template()
     */
    public function operateTwoAction(Request $req)
    {
        date_default_timezone_set("PRC");
        $rental_station = $req->query->get('rental_station');
        $start_time = $req->query->get('start_time');
        $end_time = $req->query->get('end_time');
        if(!$start_time && !$end_time){
            $end_time = $start_time = date('Y-m-d',strtotime("-1 day"));
        }
        $day = (strtotime($end_time)-strtotime($start_time))/(3600*24) + 1;
        //全部城市
//        $citys = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:Area')
//            ->findBy(['parent' => null], ['id' => 'ASC']);
        //全部车型
//        $cars = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:Car')
//            ->findAll();

        //租赁点
//        $rentalStations = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:RentalStation')
//            ->findBy(['online' => 1]);
        //dump($rentalStations);die;
        //初始化数据
        $rentalsCar = $rentalStation = array();
        if($rental_station){
            $rentalStation =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->find($rental_station);
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:StatisticsOperateRecord')
                    ->createQueryBuilder('o');
            //获取当前租赁点，start_time与end_time之间、状态为1的所有移库车辆
            $statisticsAlls =
                $qb
                    ->select('o')
                    ->andWhere($qb->expr()->gte('o.dateTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.dateTime', ':endTime'))
                    ->andWhere($qb->expr()->eq('o.rentalStation', ':rentalStation'))
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->setParameter('rentalStation', $rentalStation)
                    ->orderBy('o.dateTime', 'ASC')
                    ->getQuery()
                    ->getResult();
            //dump($statisticsAlls);die;
            foreach ($statisticsAlls as $key => $value) {
                if(!$value->getRentalCar()){
                    continue;
                }
                $rentalCarId = $value->getRentalCar()->getId();
                $car = $value->getRentalCar()->getCar();
                //dump($car);
                $rentalsCar[$rentalCarId]['rentalCarId'] = $rentalCarId;
                $rentalsCar[$rentalCarId]['car'] = $car;
                $rentalsCar[$rentalCarId]['carName'] = $value->getRentalCar()->getCar()->getName();
                $rentalsCar[$rentalCarId]['licensePlate'] = $value->getRentalCar()->getLicensePlate();
                if(isset($rentalsCar[$rentalCarId]['stayTime'])){
                    $rentalsCar[$rentalCarId]['stayTime'] += $value->getStayTime();
                }else{
                    $rentalsCar[$rentalCarId]['stayTime'] = $value->getStayTime();
                }
                if(isset($rentalsCar[$rentalCarId]['rentalTime'])){
                    $rentalsCar[$rentalCarId]['rentalTime'] += $value->getRentalTime();
                }else{
                    $rentalsCar[$rentalCarId]['rentalTime'] = $value->getRentalTime();
                }
                if(isset($rentalsCar[$rentalCarId]['dayRentalTime'])){
                    $rentalsCar[$rentalCarId]['dayRentalTime'] += $value->getDayRentalTime();
                }else{
                    $rentalsCar[$rentalCarId]['dayRentalTime'] = $value->getDayRentalTime();
                }
                if(isset($rentalsCar[$rentalCarId]['nightRentalTime'])){
                    $rentalsCar[$rentalCarId]['nightRentalTime'] += $value->getNightRentalTime();
                }else{
                    $rentalsCar[$rentalCarId]['nightRentalTime'] = $value->getNightRentalTime();
                }
                if(isset($rentalsCar[$rentalCarId]['orderCount'])){
                    $rentalsCar[$rentalCarId]['orderCount'] += $value->getOrderCount();
                }else{
                    $rentalsCar[$rentalCarId]['orderCount'] = $value->getOrderCount();
                }
                if(isset($rentalsCar[$rentalCarId]['dayOrderCount'])){
                    $rentalsCar[$rentalCarId]['dayOrderCount'] += $value->getDayOrderCount();
                }else{
                    $rentalsCar[$rentalCarId]['dayOrderCount'] = $value->getDayOrderCount();
                }
                if(isset($rentalsCar[$rentalCarId]['nightOrderCount'])){
                    $rentalsCar[$rentalCarId]['nightOrderCount'] += $value->getNightOrderCount();
                }else{
                    $rentalsCar[$rentalCarId]['nightOrderCount'] = $value->getNightOrderCount();
                }
                if(isset($rentalsCar[$rentalCarId]['revenueAmount'])){
                    $rentalsCar[$rentalCarId]['revenueAmount'] += $value->getRevenueAmount();
                }else{
                    $rentalsCar[$rentalCarId]['revenueAmount'] = $value->getRevenueAmount();
                }
                if(isset($rentalsCar[$rentalCarId]['couponAmount'])){
                    $rentalsCar[$rentalCarId]['couponAmount'] += $value->getCouponAmount();
                }else{
                    $rentalsCar[$rentalCarId]['couponAmount'] = $value->getCouponAmount();
                }
                if(isset($rentalsCar[$rentalCarId]['rentalAmount'])){
                    $rentalsCar[$rentalCarId]['rentalAmount'] += $value->getRentalAmount();
                }else{
                    $rentalsCar[$rentalCarId]['rentalAmount'] = $value->getRentalAmount();
                }

            }

        }
        return [
            'rentalCars'=>$rentalsCar,'start_time'=>$start_time,'end_time'=>$end_time,
            'rentalStation'=>$rentalStation, 'count_rentalCars'=>count($rentalsCar),'day'=>$day
            //'rentalStations'=>$rentalStations,'citys'=>$citys,'cars'=>$cars,
        ];
    }

    /**
     * @Route("/station", methods="GET", name="auto_admin_statistics_stations")
     * @Template()
     */
    public function stationAction(Request $req)
    {
        $areaid = $req->query->getInt('areaid');
        $gettype = $req->query->get('gettype');
        $area =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->find($areaid);
        if($gettype == 'all'){
            //租赁点
            $rentalStations = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findBy(['area'=>$area]);
        }else{
            //租赁点
            $rentalStations = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findBy(['online' => 1,'area'=>$area]);
        }

        $arr = array();
        foreach ($rentalStations as $station) {
            $arr[] = array(
                'id'=>$station->getId(),
                'name'=>$station->getName()
            );
        }
        $data = array('data'=>$arr,'count'=>count($arr));
        return new JsonResponse($data);

    }


}
