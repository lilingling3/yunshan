<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/2/16
 * Time: 上午9:52
 */

namespace Auto\Bundle\ApiBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @Route("/operator")
 */
class OperatorController  extends BaseController{
    const PER_PAGE = 20;


    /**
     * @Route("/index", methods="POST", name="auto_api_operator_index")
     */
    public function indexAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        if(empty($operator->getStations())){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'stationCount' => 0,
                'rentalCarCount' =>0,
                'illegalCount' =>0,
                'outTimeOrderCount' =>0,
                'noPayOrderCount' =>0
            ]);
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }
        $redis = $this->container->get('snc_redis.default');

        $redis_cmd= $redis->createCommand('get',array('overload-rental-station'));
        $overload_rental_stations = explode(',',$redis->executeCommand($redis_cmd));
        $overload_rental_station_count = count(array_intersect($overload_rental_stations,$station_ids));

        $redis_cmd= $redis->createCommand('get',array('empty-rental-station'));
        $empty_rental_stations = explode(',',$redis->executeCommand($redis_cmd));
        $empty_rental_station_count = count(array_intersect($empty_rental_stations,$station_ids));


        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->createQueryBuilder('c');

        $rental_cars =
            $qb
            ->select('c')
            ->join('c.rentalStation','s')
            ->where($qb->expr()->in('s.id', ':station_ids'))
            ->setParameter('station_ids', $station_ids)
            ->getQuery()
            ->getResult();

        if(empty($rental_cars)){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'stationCount' => count($operator->getStations()),
                'rentalCarCount' =>0,
                'illegalCount' =>0,
                'outTimeOrderCount' =>0,
                'noPayOrderCount' =>0
            ]);
        }


        $redis_cmd= $redis->createCommand('lindex',array('broken-rental-car',0));
        $broken_rental_car = json_decode($redis->executeCommand($redis_cmd),true);

        if(!isset($broken_rental_car['ids'])){
            $broken_rental_car['ids'] = [];
        }

        $rental_car_ids = [];

        $broken_cars = [];

        foreach($rental_cars as $car){
            $rental_car_ids[] = $car->getId();
            if(in_array($car->getId(),$broken_rental_car['ids'])){

                $broken_cars[]=['rentalCarID'=>$car->getId(),'license'=>$car->getLicense(),
                    'online'=>empty($car->getOnline())?0:$car->getOnline()->getStatus()];
            }
        }

        uasort($rental_cars,function($a,$b){
            if(empty($a->getOnline())) return -1;
            if(empty($b->getOnline())) return -1;
            if ($a->getOnline()->getStatus() == $b->getOnline()->getStatus()) return 0;
            return ($a->getOnline()->getStatus() > $b->getOnline()->getStatus()) ? 1 : -1;
        });

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('i');

        $illegal_count=
            $qb->select($qb->expr()->count('i'))
                ->where($qb->expr()->in('i.rentalCar',$rental_car_ids))
                ->andWhere($qb->expr()->isNull('i.handleTime'))
                ->getQuery()
                ->getSingleScalarResult() ;

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $out_time_order_count=

            $qb->select($qb->expr()->count('o'))
                ->join('o.rentalCar','c')
                ->where($qb->expr()->in('c.rentalStation',$station_ids))
                ->andWhere($qb->expr()->isNull('o.endTime'))
                ->andWhere($qb->expr()->lte('o.createTime',':dayTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->setParameter('dayTime', (new \DateTime())->modify('-16 hours'))
                ->getQuery()
                ->getSingleScalarResult() ;

        $blacklists = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BlackList')
            ->findAll();


        $member_ids = [];

        foreach($blacklists as $blacklist){

            $member_ids[] = $blacklist->getAuthMember()->getMember()->getId();

        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');


        $no_pay_order_count=

            $qb->select($qb->expr()->count('o'))
                ->where($qb->expr()->in('o.rentalCar',$rental_car_ids));
            if(!empty($member_ids))
                $qb->where($qb->expr()->notIn('o.member',$member_ids));
                $qb->andWhere($qb->expr()->isNotNull('o.endTime'))
                ->andWhere($qb->expr()->isNull('o.payTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->getQuery()
                ->getSingleScalarResult() ;


        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stationCount' => count($operator->getStations()),
            'rentalCarCount' =>count($rental_cars),
            'illegalCount' =>$illegal_count,
            'outTimeOrderCount' =>$out_time_order_count,
            'noPayOrderCount' =>$no_pay_order_count,
            'overloadRentalStationCount'=>$overload_rental_station_count,
            'emptyRentalStationCount'=>$empty_rental_station_count,
            'brokenCars'=>$broken_cars
        ]);

    }

    /**
     * @Route("/illegal/list", methods="POST",name="auto_api_operator_illegal_list")
     */
    public function illegalListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;
        $rental_car_id = $req->request->get('rentalCarID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        if(empty($operator->getStations())){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,

            ]);
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('r')
        ;


        $qb
            ->select('r')
            ->join('r.rentalCar','c')
            ->leftJoin('r.order','o')
            ->orderBy('r.id', 'DESC')
            ->where($qb->expr()->in('c.rentalStation',$station_ids))
            ->andWhere($qb->expr()->isNull('r.handleTime'));

        if($rental_car_id){

            $qb->andWhere($qb->expr()->eq('r.rentalCar',':car'))
                ->setParameter('car', $rental_car_id);

        }
        $illegalRecords =new Paginator(
                    $qb->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'pageCount'     =>ceil($illegalRecords->count() / self::PER_PAGE),
            'page'          =>$page,
            'illegalRecords'=>array_map($this->get('auto_manager.illegal_record_helper')
                ->get_illegal_record_normalizer(),
                $illegalRecords->getIterator()->getArrayCopy()),
        ]);

    }




    /**
     * @Route("/overtime/rentalOrder/list", methods="POST",name="auto_api_operator_overtime_rental_order_list")
     */
    public function overtimeRentalOrderListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        if(empty($operator->getStations())){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,

            ]);
        }



        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');


        $orders =
            new Paginator(
                $qb->select('o')
                    ->join('o.rentalCar','c')
                    ->where($qb->expr()->in('c.rentalStation',$station_ids))
                    ->andWhere($qb->expr()->isNull('o.endTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':dayTime'))
                    ->andWhere($qb->expr()->isNull('o.cancelTime'))
                    ->setParameter('dayTime', (new \DateTime())->modify('-16 hours'))
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'pageCount'  =>ceil($orders->count() / self::PER_PAGE),
            'page'        =>$page,
            'rentalOrders'  =>  array_map($this->get('auto_manager.order_helper')->get_operator_rental_order_normalizer(),
                $orders->getIterator()->getArrayCopy()),

        ]);


    }



    /**
     * @Route("/unpaid/rentalOrder/list", methods="POST",name="auto_api_operator_unpaid_rental_order_list")
     */
    public function unpaidRentalOrderListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        if(empty($operator->getStations())){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'stationCount' => 0,
                'rentalCarCount' =>0,
                'illegalCount' =>0,
                'outTimeOrderCount' =>0,
                'noPayOrderCount' =>0
            ]);
        }

        $blacklists = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BlackList')
            ->findAll();

        $member_ids = [];

        foreach($blacklists as $blacklist){

            $member_ids[] = $blacklist->getAuthMember()->getMember()->getId();

        }

        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }



        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');


        $orders =
            new Paginator(
                $qb->select('o')
                    ->join('o.rentalCar','c')
                    ->where($qb->expr()->in('c.rentalStation',$station_ids))
                    ->where($qb->expr()->notIn('o.member',$member_ids))
                    ->andWhere($qb->expr()->isNotNull('o.endTime'))
                    ->andWhere($qb->expr()->isNull('o.payTime'))
                    ->andWhere($qb->expr()->isNull('o.cancelTime'))
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'pageCount'  =>ceil($orders->count() / self::PER_PAGE),
            'page'        =>$page,
            'rentalOrders'  =>  array_map($this->get('auto_manager.order_helper')->get_operator_rental_order_normalizer(),
                $orders->getIterator()->getArrayCopy()),

        ]);


    }
    /**
     * @Route("/station/list", methods="POST",name="auto_api_operator_station")
     */
    public function stationListAction(Request $req)
    {
        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'      => array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),
                $operator->getStations()->getIterator()->getArrayCopy())
        ]);

    }

    /**
     * @Route("/rentalCar/list", methods="POST",name="auto_api_operator_rental_car_list")
     */
    public function rentalCarListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $station_id = $req->request->get('rentalStationID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }

        if($station_id||in_array($station_id,$station_ids)){
            $station_ids = [$station_id];
        }

        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->createQueryBuilder('c');

        $rental_cars =
            $qb
                ->select('c')
                ->join('c.rentalStation','s')
                ->where($qb->expr()->in('s.id', ':station_ids'))
                ->setParameter('station_ids', $station_ids)
                ->getQuery()
                ->getResult();


        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'rentalCars'   => array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rental_cars)
        ]);

    }

    /**
     * 选项卡
     * @Route("/options", methods="POST",name="auto_api_operator_options")
     */

    public function optionsAction(Request $req){

        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();

        $car_array = array_map(function($c){
            return $c->getName();
        },$cars);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'online'  =>  $this->get('auto_manager.rental_car_helper')->get_online_options(),
            'offline'  =>  $this->get('auto_manager.rental_car_helper')->get_offline_options(),
            'cars'    =>$car_array
        ]);

    }

    /**
     * 人工还车
     * @Route("/endRentalOrder", methods="POST",name="auto_api_operator_return_car")
     */

    public function operateEndRentalOrderAction(Request $req){

        $orderId=$req->request->getInt('orderID');
        $uid = $req->request->get('userID');
        $reason = $req->request->get('reason');
        $remark = $req->request->get('remark');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }


        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($orderId);

        if(empty($rentalOrder)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_ORDER,
                'errorMessage'  =>  self::M_NO_ORDER
            ]);
        }

        if(!empty($rentalOrder->getEndTime())){
            return new JsonResponse([
                'errorCode'  =>  self::E_ORDER_END,
                'errorMessage'  =>  self::M_ORDER_END
            ]);
        }


        $reason_arr = json_decode($reason,true);

        if(empty($reason_arr)||!in_array(17,$reason_arr)){
            $reason_arr[]=17;
        }
        $man = $this->getDoctrine()->getManager();


        $rentalOrder->setEndTime(new \DateTime());

        $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($rentalOrder);

        $rentalOrder->setDueAmount($cost_detail['cost']);


        $redis = $this->container->get('snc_redis.default');

        $redis_cmd= $redis->createCommand('lindex',array($rentalOrder->getRentalCar()->getDeviceCompany()
                ->getEnglishName().'-mileage-'.$rentalOrder->getRentalCar()->getBoxId(),0));
        $mileage_arr = $redis->executeCommand($redis_cmd);
        $mileage_arr = json_decode($mileage_arr,true);
        if(!empty($mileage_arr)){
            $rentalOrder->setEndMileage($mileage_arr['mileage']);
        }
        
        $onlineRecord =new RentalCarOnlineRecord();
        $onlineRecord->setStatus(0);
        $onlineRecord->setRemark($remark);
        $onlineRecord->setReason($reason_arr);
        $onlineRecord->setRentalCar($rentalOrder->getRentalCar());
        $onlineRecord->setMember($operator->getMember());
        $rentalOrder->getRentalCar()->setOnline($onlineRecord);
        $man->persist($onlineRecord);
        $man->flush();
        $man->persist($rentalOrder);
        $man->flush();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder'=>$orderId]);

        if(!empty($dispatch)){

            $dispatch->setStatus(1);

            $man->persist($dispatch);
            $man->flush();
        }


        //断电

        $this->get("auto_manager.rental_car_helper")->operate($rentalOrder->getRentalCar(),'off',$member,'');

        $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_under_write'), ['OrderID'=>$rentalOrder->getId()]);

        // 通知第三方车辆可租用
        $carPartnerData = [
            'carId' => $rentalOrder->getRentalCar()->getId(),
            'orderId' => $rentalOrder->getId(),
            'backRentalStation' => $rentalOrder->getReturnStation()->getId(),
        ];
        $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

        $carPartnerDataOffline = [
            'carId' => $rentalOrder->getRentalCar()->getId(),
            'operator' => $operator->getId(),
            'reason' => $reason,
            'remark' => $remark,
            'stationId' => $rentalOrder->getReturnStation()->getId(),
        ];
        $this->get("auto_manager.partner_helper")->carOffline($carPartnerDataOffline);

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
        ]);

    }
    /**
     * 没有车辆租赁点
     * @Route("/empty/rental/station", methods="POST",name="auto_api_operator_empty_rental_station")
     */

    public function emptyRentalStationAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }
        $redis = $this->container->get('snc_redis.default');

        $redis_cmd= $redis->createCommand('get',array('empty-rental-station'));
        $overload_rental_stations = explode(',',$redis->executeCommand($redis_cmd));
        $stations = array_intersect($overload_rental_stations,$station_ids);
        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->createQueryBuilder('s');

        $rental_stations =
            $qb
                ->select('s')
                ->where($qb->expr()->in('s.id', ':station_ids'))
                ->setParameter('station_ids', $stations)
                ->getQuery()
                ->getResult();

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'      => array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),$rental_stations)
        ]);

    }

    /**
     * 超载车辆租赁点
     * @Route("/overload/rental/station", methods="POST",name="auto_api_operator_overload_rental_station")
     */

    public function overloadRentalStationAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }
        $redis = $this->container->get('snc_redis.default');

        $redis_cmd= $redis->createCommand('get',array('overload-rental-station'));
        $overload_rental_stations = explode(',',$redis->executeCommand($redis_cmd));
        $stations = array_intersect($overload_rental_stations,$station_ids);
        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->createQueryBuilder('s');

        $rental_stations =
            $qb
                ->select('s')
                ->where($qb->expr()->in('s.id', ':station_ids'))
                ->setParameter('station_ids', $stations)
                ->getQuery()
                ->getResult();

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'      => array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),$rental_stations)
        ]);

    }

    /**
     * 车辆下的租赁订单
     * @Route("/rentalCar/orders", methods="POST",name="auto_api_operator_rental_car_orders")
     */

    public function rentalCarOrdersAction(Request $req)
    {
        $rentalCarId=$req->request->getInt('rentalCarID');
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];

        foreach($operator->getStations() as $station){
            $station_ids[] = $station->getId();
        }

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);

        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }

        if(!in_array($rentalCar->getRentalStation()->getId(),$station_ids)){

            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
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
                    ->where($qb->expr()->eq('o.rentalCar', ':rentalCar'))
                    ->setParameter('rentalCar', $rentalCar)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );




        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'pageCount'  =>ceil($orders->count() / self::PER_PAGE),
            'page'        =>$page,
            'rentalOrders'  =>  array_map($this->get('auto_manager.order_helper')->get_operator_rental_order_normalizer(),
                $orders->getIterator()->getArrayCopy()),

        ]);

    }


    /**
     * 车辆下的租赁订单详情
     * @Route("/rentalOrder/show", methods="POST",name="auto_api_operator_rental_order_show")
     */

    public function rentalOrderShowAction(Request $req)
    {
        $rental_order_id=$req->request->getInt('rentalOrderID');
        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }


        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($rental_order_id);

        if(empty($rentalOrder)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        return new JsonResponse([
            'errorCode'   =>  self::E_OK,
            'rentalOrder' =>  call_user_func($this->get('auto_manager.order_helper')->get_operator_rental_order_normalizer(),$rentalOrder),
            'member'     =>call_user_func($this->get('auto_manager.member_helper')->get_member_normalizer(),$rentalOrder->getMember()),
        ]);


    }
    /**
     * 车辆定位
     * @Route("/rentalCar/position", methods="POST",name="auto_api_operator_rental_car_position")
     */

    public function rentalCarPositionAction(Request $req)
    {
        $rental_car_id=$req->request->getInt('rentalCarID');
        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);


        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rental_car_id);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }

        if(!in_array($rentalCar->getRentalStation()->getId(),$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);

        }

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'position'  =>  $this->get('auto_manager.rental_car_helper')->get_rental_car_position($rentalCar)
        ]);

    }

        /**
     * 车辆下的租赁车辆详情
     * @Route("/rentalCar/show", methods="POST",name="auto_api_operator_rental_car_show")
     */

    public function rentalCarShowAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $rental_car_id=$req->request->getInt('rentalCarID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];

        foreach($operator->getStations() as $station){
            $station_ids[] = $station->getId();
        }

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rental_car_id);

        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }

        if(!in_array($rentalCar->getRentalStation()->getId(),$station_ids)){

            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rental_car_id);


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $no_pay_order_count=

            $qb->select($qb->expr()->count('o'))
                ->where($qb->expr()->eq('o.rentalCar',$rentalCar->getId()))
                ->andWhere($qb->expr()->isNotNull('o.endTime'))
                ->andWhere($qb->expr()->isNull('o.payTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->getQuery()
                ->getSingleScalarResult() ;
        $rental_car=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
            $rentalCar);
        $offlineReasons=[];

        if($rental_car["online"]==0){
            $offlinsqb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
                    ->createQueryBuilder('o');
            $carofflin= $offlinsqb
                ->select('o')
                ->orderBy('o.createTime', 'DESC')
                ->where($offlinsqb->expr()->eq('o.rentalCar',':id'))
                ->andWhere($offlinsqb->expr()->eq('o.status',':status'))
                ->setParameter('id', $rental_car["rentalCarID"])
                ->setParameter('status', 0)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $reasons=$this->get('auto_manager.rental_car_helper')->get_offline_options();

            foreach($carofflin->getReason() as $value){
                $offlineReasons['reasons'][]=$reasons[$value];
            }
            if(!empty($carofflin->getRemark())){
                $offlineReasons['remark']=$carofflin->getRemark();
            }
            $offlineReasons["offset"]=(new \DateTime())->getTimestamp()-$carofflin->getCreateTime()->getTimestamp();

        }

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'rentalCar' => $rental_car,
            'offlineReasons'=>$offlineReasons,
            'noPayOrderCount'=>$no_pay_order_count
        ]);


    }


    //2.0首页
    /**
     * @Route("/index2", methods="POST", name="auto_api_operator_index2")
     */
    public function index2Action(Request $req)
    {
        $uid = $req->request->get('userID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);


        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }


        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations' =>array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),
                $operator->getStations()->getIterator()->getArrayCopy())
        ]);

    }


    //2.0设备异常车辆列表
    /**
     * @Route("/brokenCar/list", methods="POST", name="auto_api_operator_brokenCar_list")
     */
    public function brokenCarListAction(Request $req){
        $uid = $req->request->get('userID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        if(empty($operator->getStations())){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'brokenCarStations' => ""
            ]);
        }


        $station_ids = [];
        foreach($operator->getStations() as $station){

            $station_ids[] = $station->getId();

        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('lindex',array('broken-rental-car',0));
        $broken_rental_car = json_decode($redis->executeCommand($redis_cmd),true);
        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->createQueryBuilder('c');

        $rental_cars =
            $qb
                ->select('c')
                ->join('c.rentalStation','s')
                ->where($qb->expr()->in('s.id', ':station_ids'))
                ->setParameter('station_ids', $station_ids)
                ->getQuery()
                ->getResult();

        if(empty($rental_cars)){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'brokenCarStations' => 0
            ]);
        }
        if(!isset($broken_rental_car['ids'])){
            $broken_rental_car['ids'] = [];
        }
        $rental_car_ids = [];
        $broken_stations_ids=[];
        $broken_cars = [];
        $broken_stations=[];
        foreach($rental_cars as $car){
            $rental_car_ids[] = $car->getId();
            if(in_array($car->getId(),$broken_rental_car['ids'])){

                $rental_station_id=$car->getRentalStation()->getId();
                $rentalCarinfo=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(), $car);

                if(!in_array($rental_station_id,$broken_stations_ids)){
                    $broken_stations[$rental_station_id]["stations"]=[
                        "rentalStationID"=>$car->getRentalStation()->getId(),
                        'name'=>$car->getRentalStation()->getName(),
                        "offline"=>0,
                        "prepare"=>0
                    ];
                    $broken_stations[$rental_station_id]["rentalCars"]=[];
                    $broken_stations_ids[]=$rental_station_id;
                }

                if($rentalCarinfo['online']==1){

                    if($rentalCarinfo["status"]==300){
                        array_unshift($broken_stations[$rental_station_id]["rentalCars"],$rentalCarinfo);
                        $broken_stations[$rental_station_id]["stations"]["offline"]++;
                        $broken_stations[$rental_station_id]["stations"]["prepare"]++;
                    }
                    else{
                        for($i=count($broken_stations[$rental_station_id]["rentalCars"]);$i>$broken_stations[$rental_station_id]["stations"]["offline"];$i--){
                            $broken_stations[$rental_station_id]["rentalCars"][$i]= $broken_stations[$rental_station_id]["rentalCars"][$i-1];
                        }
                        $broken_stations[$rental_station_id]["rentalCars"][$i]=$rentalCarinfo;
                        $broken_stations[$rental_station_id]["stations"]["offline"]++;
                        $broken_stations[$rental_station_id]["stations"]["prepare"]++;
                    }
                }
                else{
                    if($rentalCarinfo["status"]==300){

                        for($i=count($broken_stations[$rental_station_id]["rentalCars"]);$i>$broken_stations[$rental_station_id]["stations"]["prepare"];$i--){
                            $broken_stations[$rental_station_id]["rentalCars"][$i]= $broken_stations[$rental_station_id]["rentalCars"][$i-1];
                        }

                        $broken_stations[$rental_station_id]["rentalCars"][$i]=$rentalCarinfo;
                        $broken_stations[$rental_station_id]["stations"]["prepare"]++;
                    }
                    else{
                        $broken_stations[$rental_station_id]["rentalCars"][]=$rentalCarinfo;
                    }
                }

            }
        }


        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'brokenCarStations'=>array_values($broken_stations)
        ]);
    }

    //2.0 车辆搜索
    /**
     * @Route("/rentalCar/search", methods="POST", name="auto_api_operator_rentalCar_search")
     */

    public function  rentalCarSearch(Request $req){
        $uid = $req->request->get('userID');
        $licensePlate = $req->request->get('licensePlate');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[] = $station->getId();
        }

        if(empty($licensePlate)){
            return new JsonResponse([
                'errorCode'  =>  self::E_OK,
                'rentalCars'  =>""
            ]);
        }
        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->createQueryBuilder('c');

        $rental_cars =
            $qb
                ->select('c')
                ->join('c.rentalStation','s')
                ->where($qb->expr()->in('s.id', ':station_ids'))
                ->andWhere($qb->expr()->like('c.licensePlate', ':licensePlate'))
                ->setParameter('station_ids', $station_ids)
                ->setParameter('licensePlate','%'.$licensePlate.'%')
                ->getQuery()
                ->getResult();


        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            "rentalCars"=>array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(), $rental_cars)
        ]);


    }

//2.0 车辆可移库的租赁点
    /**
     * @Route("/rentalCar/dispatch/stations", methods="POST", name="auto_api_operator_rentalCar_dispatch_station")
     */

    public function  rentalCardispatchStationsAction(Request $req){
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $station_ids = [];
        $stations=[];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
            $stations[]=$station;
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        $pickUpRentalStationID=$rentalCar->getRentalStation()->getId();

        if(!in_array($pickUpRentalStationID,$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }
        $index=array_search($pickUpRentalStationID,$station_ids);
        array_splice($stations,$index,1);
        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            "rentalCar"   =>call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
                ,$rentalCar),
            'stations'      => array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),
                $stations)
        ]);
    }

//2.0 车辆移库操作
    /**
     * @Route("/rentalCar/dispatch", methods="POST", name="auto_api_operator_rentalCar_dispatch")
     */

    public function  rentalCardispatchAction(Request $req){
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $dispatchStationID = $req->request->get('dispatchStationID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $station_ids = [];
        $stations=[];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
            $stations[]=$station;
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        $pickUpRentalStationID=$rentalCar->getRentalStation()->getId();

        if(!in_array($pickUpRentalStationID,$station_ids)&&!in_array($dispatchStationID,$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }
        $dispatchStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($dispatchStationID);
        $carqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');
        $totalRentalCarCount=
            $carqb
                ->select($carqb->expr()->count('c'))
                ->where($carqb->expr()->eq('c.rentalStation', ':station'))
                ->setParameter('station', $dispatchStation)
                ->getQuery()
                ->getSingleScalarResult()
        ;

        if($totalRentalCarCount>=$dispatchStation->getUsableParkingSpace()){
            return new JsonResponse([
                'errorCode' => self::E_UNABLE_CHANGE_STATION,
                'errorMessage' => self::M_UNABLE_CHANGE_STATION,
            ]);
        }

        $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
        $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::OPERATOR_DISPATCH_CAR_KIND);
        $dispatch->setRentalCar($rentalCar);
        $dispatch->setRentalStation($dispatchStation);
        $dispatch->setStatus(1);
        $dispatch->setOperateMember($member);
        $man = $this->getDoctrine()->getManager();
        $man->persist($dispatch);
        $man->flush();



        $rentalCar->setRentalStation($dispatchStation);
        $man->persist($rentalCar);
        $man->flush();
        return new JsonResponse([
            'errorCode'    =>  self::E_OK
        ]);
    }


//2.0 车辆续航日志
    /**
     * @Route("/rentalCar/ranges", methods="POST", name="auto_api_operator_rentalCar_ranges")
     */

    public function  rentalCarmileageAction(Request $req){
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        $pickUpRentalStationID=$rentalCar->getRentalStation()->getId();

        if(!in_array($pickUpRentalStationID,$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }


        $redis = $this->container->get('snc_redis.default');

        $redis_cmd_llen = $redis->createCommand('llen',array($rentalCar->getDeviceCompany()->getEnglishName().'-range-'.$rentalCar->getBoxId()));

        $llen = $redis->executeCommand($redis_cmd_llen);


        if($llen==0){
            return new JsonResponse([
                'errorCode'     =>  self::E_OK,
                'pageCount'     =>0,
                'page'          =>0,
                'ranges'=>''
            ]);
        }
        $pageCount=ceil($llen/20);
         if($page==1){
             $star=0;
         }else{
             $star=($page-1)*20;
         }
        if($page>=$pageCount){
            $end=$llen;
        }else{
            $end=$page*20-1;
        }
        $redis_cmd= $redis->createCommand('Lrange',array('carStart-range-'.$rentalCar->getBoxId(),$star,$end));
        $box_json = $redis->executeCommand($redis_cmd);
        $ranges_arr = $this->get('auto_manager.curl_helper')->object_array($box_json);
        $ranges=[];
        foreach($ranges_arr as $value){
            $value= $this->get('auto_manager.curl_helper')->object_array(json_decode($value));

            $ranges[]=[
                "id"=>$value["id"],
                "mileage"=>$value['range'],
                "time"=> date('Y-m-d H:i:s',$value['time'])
            ];
        }

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'pageCount'     =>$pageCount,
            'page'          =>$page,
            'ranges'=>$ranges
        ]);
    }

//2.0 车辆上下线数据
    /**
     * @Route("/rentalCar/onlineReasons", methods="POST", name="auto_api_operator_rentalCar_onlineReasons")
     */
    public function  rentalcarOnlineReasonsAction(Request $req){
        $uid = $req->request->get('userID');
        $orderID = $req->request->get('orderID');
        $rentalCarID = $req->request->get('rentalCarID');
        $onlineStatus= $req->request->get("onlineStatus");
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        if(empty($rentalCarID)){
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->findOneBy(['id'=>$orderID]);
            $rentalCarID=$order->getRentalCar()->getId();
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        $pickUpRentalStationID=$rentalCar->getRentalStation()->getId();

        if(!in_array($pickUpRentalStationID,$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }
        $reason_name1="require";
        $reason_name2="unrequire";

        if($onlineStatus==1){
            $reasons=$this->get('auto_manager.rental_car_helper')->get_online_options();
            $reqkey_arr=[6,7,8,11];
        }
       else{
           $reasons=$this->get('auto_manager.rental_car_helper')->get_offline_options();
           $reqkey_arr=[12,15,18,];
           $reason_name1="operater";
           $reason_name2="car";
       }
        $onlineReasons=[];

        foreach($reasons as $key=>$value){
            if($key!="17"){
                if(in_array($key,$reqkey_arr)){
                    $onlineReasons[$reason_name1][]=[
                        "id"=>$key,
                        "reasons"=>$value
                    ];
                }
                else{
                    $onlineReasons[$reason_name2][]=[
                        "id"=>$key,
                        "reasons"=>$value
                    ];
                }
            }

        }
        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'onlineReasons'     =>$onlineReasons,
            "rentalCar"=>call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
                $rentalCar),
            "orderID"=>$orderID
        ]);

    }

//2.0 车辆充电
    /**
     * @Route("/rentalCar/charging", methods="POST", name="auto_api_operator_rentalCar_charging")
     */
    public function  chargingAction(Request $req){
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $degree = $req->request->getInt('degree'); //充电度数
        $cost = $req->request->getInt('cost');   //充电费用


        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        $pickUpRentalStationID=$rentalCar->getRentalStation()->getId();

        if(!in_array($pickUpRentalStationID,$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }


        $charging = new \Auto\Bundle\ManagerBundle\Entity\ChargingRecords();
        $charging->setRentalCar($rentalCar);
        $charging->setDegree($degree);
        $charging->setCost($cost);
        $charging->setOperator($member);
        $man = $this->getDoctrine()->getManager();
        $man->persist($charging);
        $man->flush();
        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            "rentalCarID"=>$rentalCarID
        ]);

    }


//2.0 车辆详细
    /**
     * @Route("/rentalCar/detail", methods="POST", name="auto_api_operator_rentalCar_detail")
     */
    public function  rentalcarDetailAction(Request $req){
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        if(!in_array($rentalCar->getRentalStation()->getId(),$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }

        $inspect= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->findOneBy(['rentalCar'=>$rentalCar]);


        //operationKind   1营运 2非营运 3租赁

        if($rentalCar->getOperationKind()==1){
            $operationKind="营运";
        }
        elseif ($rentalCar->getOperationKind()==2){
            $operationKind="非营运";
        }
        else{
            $operationKind="租赁";
        }


        $status=$this->get('auto_manager.RentalCar_helper')->get_rental_car_status($rentalCar);
        $carStyle=[
            "id"=>$rentalCarID,
            "status"=>$status,
            "name"=>$rentalCar->getCar()->getName(),
            "color"=>$rentalCar->getColor()->getName(),
            "licensePlace"=>$rentalCar->getLicensePlace()->getName(),
            "licensePlate"=>$rentalCar->getLicensePlate(),
            "chassisNumber"=>$rentalCar->getChassisNumber(),
            "chassisNumber"=>$rentalCar->getChassisNumber(),
            "operationKind"=>$operationKind,
            "nextInspectionTime"=>empty($inspect)?"":$inspect->getNextInspectionTime(),
            "engineNumber"=>$rentalCar->getEngineNumber(),
            "rentalStation"=>$rentalCar->getRentalStation()->getName(),
            "company"=>$rentalCar->getCompany()->getName(),
            "carStart"=>empty($rentalCar->getDeviceCompany())?"":$rentalCar->getDeviceCompany()->getName(),
            "tBox"=>$rentalCar->getBoxId(),
            "images"=>$rentalCar->getImages(),
            "online"=>empty($rentalCar->getOnline())?0:$rentalCar->getOnline()->getStatus()
        ];

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            "rentalCar"=>$carStyle
        ]);

    }



//2.0 车辆保单详细
    /**
     * @Route("/rentalCar/insuranceRecords", methods="POST", name="auto_api_operator_rentalCar_insuranceRecords")
     */
    public function  rentalcarInspectAction(Request $req){
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }
        if(!in_array($rentalCar->getRentalStation()->getId(),$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }

        $inspects= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->findBy(['rentalCar'=>$rentalCar]);

        $InsuranceRecords=[];

        foreach($inspects as $value){

            $InsuranceRecords[]=[
                "id"=> $value->getId(),
                "kind"=>$value->getInsurance()==1?"交强险":"商业险",
                "company"=>$value->getCompany()->getName(),
                "startTime"=>$value->getStartTime()->format('Y-m-d H:i:s'),
                "endTime"=>$value->getEndTime()->format('Y-m-d H:i:s'),
                "amount"=>$value->getInsuranceAmount(),
                "number"=>$value->getInsuranceNumber()
            ];
        }



        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            "InsuranceRecords"=>$InsuranceRecords
        ]);

    }



    //2.0 车辆上下线列表
    /**
     * @Route("/rentalCar/onlineReasons/list", methods="POST", name="auto_api_operator_rentalCar_onlineReasons_list")
     */

    public function  rentalcarOnlineReasonsListAction(Request $req){
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;
        $uid = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $onlineStatus= $req->request->get("onlineStatus");
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }

        $station_ids = [];
        foreach($operator->getStations() as $station){
            $station_ids[]=$station->getId();
        }
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarID);
        if(empty($rentalCar)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }

        $pickUpRentalStationID=$rentalCar->getRentalStation()->getId();

        if(!in_array($pickUpRentalStationID,$station_ids)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RIGHT,
                'errorMessage'  =>  self::M_NO_RIGHT
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
                ->createQueryBuilder('o');

        $onlins=
            new Paginator(
        $qb->select('o')
            ->orderBy('o.createTime', 'DESC')
            ->where($qb->expr()->eq('o.rentalCar',':id'))
            ->andWhere($qb->expr()->eq('o.status',':status'))
            ->setParameter('id', $rentalCarID)
            ->setParameter('status',$onlineStatus )
            ->setMaxResults(self::PER_PAGE)
            ->setFirstResult(self::PER_PAGE * ($page - 1))

        );

        $online_arr=[];
        if($onlineStatus==1){
            foreach($onlins as $onlin){
                $online_arr[]=[
                    "id"=>$onlin->getId(),
                    "time"=>$onlin->getCreateTime()->format('Y/m/d H:i:s'),
                    "operater"=>$onlin->getMember()->getName(),
                    "mobile"=>$onlin->getMember()->getMobile()
                ];
            }
        }
        else {
            $reasons=$this->get('auto_manager.rental_car_helper')->get_offline_options();
            foreach($onlins as $onlin){
                $reasonsIds=$onlin->getReason();
                $reasonstemp=[];
                foreach($reasonsIds as $reasonsId){
                    $reasonstemp[]=$reasons[$reasonsId];
                }
                $online_arr[]=[
                    "id"=>$onlin->getId(),
                    "time"=>$onlin->getCreateTime()->format('Y/m/d H:i:s'),
                    "operater"=>$onlin->getMember()->getName(),
                    "mobile"=>$onlin->getMember()->getMobile(),
                    "reasons"=>$reasonstemp
                ];
            }
        }

        return new JsonResponse([
            'pageCount'  =>ceil($onlins->count() / self::PER_PAGE),
            'page'        =>$page,
            'errorCode'     =>  self::E_OK,
            "onlins"=>$online_arr,
            "rentalCar"=>call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
                $rentalCar)
        ]);

    }

//2.0 车辆上下线列表 workStatus：1上班，：2下班
    /**
     * @Route("/work", methods="POSt", name="auto_api_operator_work")
     */

    public function  workAction(Request $req){
        $uid = $req->request->get('userID');
        $lon = $req->request->get('lon');
        $lat = $req->request->get('lat');
        $onlineStatus= $req->request->get("workStatus");

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR
            ]);
        }
        $stations=$operator->getStations();
        $area= $stations[0]->getArea()->getName();
        if(empty($onlineStatus)){
            return new JsonResponse([
                'errorCode'     =>  self::E_OK,
                "auth"=>call_user_func($this->get('auto_manager.member_helper')->get_member_normalizer(),$member),
                "area"=>$area
            ]);
        }

        $operatorwork = new \Auto\Bundle\ManagerBundle\Entity\OperatorAttendanceRecord();

        $operatorwork->setStatus($onlineStatus);

        $operatorwork->setMember($member);
        $operatorwork->setLongitude($lon);
        $operatorwork->setLatitude($lat);

        $man = $this->getDoctrine()->getManager();

        $man->persist($operatorwork);
        $man->flush();


        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            "auth"=>call_user_func($this->get('auto_manager.member_helper')->get_member_normalizer(),$member),
            "area"=>$area
        ]);

    }


    /**
     * @Route("/getRentalCars", methods="post", name="auto_api_operate_getRentalCars")
     * @Template()
     */
    public function getRentalCarsAction($page = 1 ,Request $req){
        $page = $req->request->get('page')?$req->request->getInt('page'):1;
        $uid = $req->request->get('userID');

        $status=$req->request->getInt('carStaus');
        $type=$req->request->getInt('carType');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);

        if(empty($operator)){
            return new JsonResponse([
                'errorCode'  =>  self::E_NO_OPERATOR,
                'errorMessage'  =>  self::M_NO_OPERATOR,
                "uid"=>$uid
            ]);
        }
        $stations=$operator->getStations();
        $stationsId=array();
        foreach($stations as $value){
            $stationsId[]=$value->getId();
        }
        //car

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('r')
                ->select('r')
                ->join('r.car','c')
                ->leftJoin('r.online','o')
        ;


        $qb ->where($qb->expr()->in('r.rentalStation',$stationsId));

        if((!empty($status) && $status!=-1) || $status===0 ){
            $qb
                ->andWhere($qb->expr()->eq('o.status', ':status'))
                ->setParameter('status',$status);
        }
        if((!empty($type)  && $type!=-1) || $type ===0){
            $qb
                ->andWhere($qb->expr()->eq('c.id', ':carid'))
                ->setParameter('carid',$type);
        }

        $rentalCars =
            new Paginator(
                $qb
                    ->orderBy('r.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($rentalCars) / self::PER_PAGE);
        $carqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Car')
                ->createQueryBuilder('car')
        ;
        $cars =
            $carqb
                ->select('car')
                ->orderBy('car.id', 'DESC')
                ->getQuery()
                ->getResult() ;
        $flag=['status'=>$status,'type'=>$type];

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            "rentalCars"=>array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars->getIterator()->getArrayCopy()),
            'page'=>$page,
            'pageCount'=>$total,
            "cars"=>$cars,
            'flag'=>$flag
        ]);
    }

}