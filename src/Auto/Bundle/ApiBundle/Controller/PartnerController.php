<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 2017/2/5
 * Time: 下午5:58
 */

namespace Auto\Bundle\ApiBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\Area;
use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord;
use Auto\Bundle\ManagerBundle\Entity\RentalStation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/partner")
 */
class PartnerController extends BaseController
{
    const PER_PAGE = 20;
    const CAR_STATION_DISTANCE = 100;
    const CAR_STATION_DISTANCE_MAX = 300;

    /**
     * @Route("/area/list", methods="POST",name="auto_api_partner_area_list")
     */
    public function areaListAction(Request $req)
    {

        $code = $req->request->get('code');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $paramenters = ['code' => $code, 'timestamp' => $timestamp];

        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }

        $areas = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->findAll();

        return new JsonResponse([
            'error' => 0,
            'areas' => array_map($this->get('auto_manager.area_helper')->get_area_normalizer(),
                $areas)
        ]);

    }

    /**
     * @Route("/station/info/{id}", methods="POST")
     */
    public function stationAction(RentalStation $station, Request $req)
    {
        $code = $req->request->get('code');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $paramenters = ['code' => $code, 'stationId' => $station->getId(), 'timestamp' => $timestamp];
        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }

        return new JsonResponse([
            'error' => 0,
            'station' => call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer(),
                $station)
        ]);

    }


    /**
     * @Route("/area/station", methods="POST")
     */
    public function stationListAction(Request $req)
    {
        $code = $req->request->get('code');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');

        $area_id = $req->request->get('areaId');
        $paramenters = ['code' => $code, 'areaId' => $area_id, 'timestamp' => $timestamp];

        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s');

        $stations = $qb
            ->select('s')
            ->leftJoin('s.area', 'a')
            ->leftJoin('a.parent', 'a1')
            ->leftJoin('a1.parent', 'a2')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('a.id', ':area'),
                $qb->expr()->eq('a1.id', ':area'),
                $qb->expr()->eq('a2.id', ':area')
            ))
            ->setParameter('area', $area_id)
            ->getQuery()
            ->getResult();;


        return new JsonResponse([
            'error' => 0,
            'stations' => array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),
                $stations)
        ]);

    }


    /**
     * @Route("/station/{id}/rentalcar", methods="POST")
     */
    public function stationRentalCarAction(RentalStation $station, Request $req)
    {
        $code = $req->request->get('code');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $paramenters = ['code' => $code, 'stationId' => $station->getId(), 'timestamp' => $timestamp];
        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $rentalCars =

            $qb
                ->select('c')
                ->join('c.online', 'o')
                ->where($qb->expr()->eq('c.rentalStation', ':station'))
                ->andWhere($qb->expr()->eq('o.status', ':status'))
                ->orderBy('c.id', 'DESC')
                ->setParameter('station', $station)
                ->setParameter('status', 1)
                ->getQuery()
                ->getResult();


        $rental_cars = array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
            $rentalCars);

        $rental_able_cars = [];


        foreach ($rental_cars as $car) {

            if ($car['status'] == \Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE) {

                $rental_able_cars[] = $car;

            }

        }

        uasort($rental_able_cars, function ($a, $b) {
            if ($a['mileage'] == $b['mileage']) return 0;
            return ($a['mileage'] < $b['mileage']) ? 1 : -1;
        });

        return new JsonResponse([
            'error' => self::E_OK,
            'rentalCars' => array_values($rental_able_cars),
            'station' => call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer()
                , $station)
        ]);


    }


    /**
     * 还车
     * @Route("/end/rentalorder/{id}", methods="POST")
     */
    public function endRentalOrderAction(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order, Request $req)
    {

        $code = $req->request->get('code');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $mobile = $req->request->get('mobile');
        $paramenters = ['code' => $code, 'mobile' => $mobile, 'rentalOrderId' => $order->getId(), 'timestamp' => $timestamp];
        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }


        if ($order->getEndTime()) {
            return new JsonResponse([
                'error' => self::E_ORDER_END,
                'errorMessage' => self::M_ORDER_END,
                'orderID' => $order->getId()
            ]);
        }

        if (empty($order->getRentalCar()->getBoxId())) {
            return new JsonResponse([
                'error' => self::E_NO_CAR_START_DEVICE,
                'errorMessage' => self::M_NO_CAR_START_DEVICE
            ]);
        }
        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'gps');

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('LINDEX', array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName() . '-gps-' . $order->getRentalCar()->getBoxId(), 0));
        $gps_json = $redis->executeCommand($redis_cmd);
        $gps_arr = json_decode($gps_json, true);
        if (!empty($gps_arr) && ((new \DateTime())->getTimestamp() - $gps_arr['time'] < 15 * 60)) {


            $destination = [$order->getRentalCar()->getRentalStation()->getLongitude(), $order->getRentalCar()->getRentalStation()->getLatitude()];

            $distance = $this->get('auto_manager.amap_helper')->straight_distance($gps_arr['coordinate'], $destination);


            if ((new \DateTime())->getTimestamp() - $gps_arr['time'] <= 5) {

                if ($distance >= self::CAR_STATION_DISTANCE) {

                    return new JsonResponse([
                        'error' => self::E_STATION_CAR_DISTANCE,
                        'errorMessage' => self::M_STATION_CAR_DISTANCE
                    ]);

                }

            } else {


                if ($distance >= self::CAR_STATION_DISTANCE_MAX) {

                    return new JsonResponse([
                        'error' => self::E_STATION_CAR_DISTANCE,
                        'errorMessage' => self::M_STATION_CAR_DISTANCE
                    ]);

                }
            }


        } else {
            return new JsonResponse([
                'error' => self::E_STATION_CAR_DISTANCE,
                'errorMessage' => self::M_STATION_CAR_DISTANCE
            ]);

        }

        //熄火
        if ($order->getRentalCar()->getDeviceCompany()->getEnglishName() == 'carStart') {

            //       $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'status', $member);
            sleep(1);
            $redis = $this->container->get('snc_redis.default');

            $redis_cmd = $redis->createCommand('lindex', array($order->getRentalCar()->getDeviceCompany()
                    ->getEnglishName() . '-status-' . $order->getRentalCar()->getBoxId(), 0));

            $fire_json = $redis->executeCommand($redis_cmd);
            $fire_arr = json_decode($fire_json, true);

            if ((new \DateTime())->getTimestamp() - $fire_arr['time'] > 10) {

                return new JsonResponse([
                    'error' => self::E_ON_FIRE,
                    'errorMessage' => self::M_ON_FIRE
                ]);


            }


            if ($fire_arr['status'] == '6F07') {
                return new JsonResponse([
                    'error' => self::E_ON_FIRE,
                    'errorMessage' => self::M_ON_FIRE
                ]);
            }
        }

        //关门
        $member = null;
        $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'close', $member);

        if (!$result) {
            return new JsonResponse([
                'error' => self::E_CLOSE_DOOR,
                'errorMessage' => self::M_CLOSE_DOOR
            ]);
        } else {
            $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'on', $member);

        }

        //断电

        $offresult = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'off', $member);

        if (!$offresult) {
            return new JsonResponse([
                'error' => self::E_OFF_POWER,
                'errorMessage' => self::M_OFF_POWER
            ]);
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
        if ($range < $order->getRentalCar()->getCar()->getAutoOfflineMileage()) {

            $onlineRecord = new RentalCarOnlineRecord();
            $onlineRecord->setStatus(0)
                ->setReason([16])
                ->setRentalCar($order->getRentalCar())
                ->setBackRange($range)
                ->setMember($member);
            $man->persist($onlineRecord);
            $man->flush();
            $order->getRentalCar()->setOnline($onlineRecord);

        }


        $redis_cmd = $redis->createCommand('lindex', array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName() . '-mileage-' . $order->getRentalCar()->getBoxId(), 0));
        $mileage_arr = $redis->executeCommand($redis_cmd);
        $mileage_arr = json_decode($mileage_arr, true);
        if (!empty($mileage_arr)) {
            $order->setEndMileage($mileage_arr['mileage']);
        }

        $order->setEndTime(new \DateTime());

        $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($order);

        $order->setDueAmount($cost_detail['cost']);
        $man->persist($order);
        $man->flush();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder' => $order]);

        if (!empty($dispatch)) {

            $dispatch->setStatus(1);

            $man->persist($dispatch);
            $man->flush();
        }
        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'off', $member);
        $password = $this->get_random_integer(4);

        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'encode', $member, $password);

        $redis_cmd = $redis->createCommand('del', array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName() . '-password-' . $order->getRentalCar()->getBoxId()));
        $redis->executeCommand($redis_cmd);


        return new JsonResponse([
            'error' => self::E_OK,
            'order' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                $order),

        ]);

    }


    /**
     * @Route("/rentalorder/cancel/{id}", methods="POST")
     */

    public function rentalorderCancelAction(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order, Request $req)
    {

        $code = $req->request->get('code');
        $mobile = $req->request->get('mobile');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $paramenters = ['code' => $code, 'mobile' => $mobile, 'rentalOrderId' => $order->getId(), 'timestamp' => $timestamp];
        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }


        if ($order->getCancelTime()) {
            return new JsonResponse([
                'error' => self::E_OK,
                'order' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                    $order)
            ]);

        }

        $status = $this->get('auto_manager.order_helper')->get_order_status($order);

        if ($mobile == $order->getMobile() && $status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER && !$order->getUseTime()) {

            $order->setCancelTime(new \DateTime());
            if ($order->getPickUpStation()->getBackType()
                == \Auto\Bundle\ManagerBundle\Entity\RentalStation::DIFFERENT_PLACE_BACK
            ) {
                $order->getRentalCar()->setRentalStation($order->getPickUpStation());
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $this->get("auto_manager.sms_helper")->cancelRentalSMS($order->getMember()->getMobile());


            return new JsonResponse([
                'error' => self::E_OK,
                'orderID' => $order->getId()

            ]);

        } else {

            return new JsonResponse([
                'error' => self::E_ORDER_PROGRESS,
                'errorMessage' => self::M_ORDER_PROGRESS,

            ]);

        }

    }


    /**
     * @Route("/order/{id}/operate", methods="POST",name="auto_api_partner_order_operate")
     */

    public function orderOperateAction(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order, Request $req)
    {
        $operate = $req->request->get('operate');
        $code = $req->request->get('code');
        $mobile = $req->request->get('mobile');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $paramenters = ['code' => $code, 'mobile' => $mobile, 'operate' => $operate, 'rentalOrderId' => $order->getId(), 'timestamp' => $timestamp];
        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }

        $result = $this->get('auto_manager.rental_car_helper')->operate($order->getRentalCar(), $operate);

        if ($result) {
            return new JsonResponse([
                'error' => self::E_OK,
            ]);
        } else {
            return new JsonResponse([
                'error' => self::E_FIND_CAR,
                'errorMessage' => self::M_FIND_CAR
            ]);
        }

    }


    /**
     * @Route("/rentalcar/{id}/order", methods="POST")
     */

    public function orderAction(RentalCar $rentalCar, Request $req)
    {
        $code = $req->request->get('code');
        $timestamp = $req->request->get('timestamp');
        $sign = $req->request->get('sign');
        $mobile = $req->request->get('mobile');
        $back_station_id = $req->request->get('returnStationID');
        $back_station_id = $this->get("auto_manager.cache_helper")->getStationIdByPartnerStationIdFromCache($back_station_id, $code);
        $paramenters = ['code' => $code, 'rentalCarId' => $rentalCar->getId(), 'returnStationID' => $back_station_id, 'mobile' => $mobile, 'timestamp' => $timestamp];
        $result = $this->checkPartner($code, $timestamp);

        if ($result['error'] == 1) {
            return new JsonResponse($result);
        }
        $verify_sign = $this->getSign($paramenters, $result['secret']);

        if ($verify_sign != $sign) {
            return new JsonResponse([
                'error' => 1,
                'message' => '验证失败'
            ]);
        }


        if (empty($rentalCar)) {
            return new JsonResponse([
                'error' => self::E_NO_RENTAL_CAR,
                'errorMessage' => self::M_NO_RENTAL_CAR,
            ]);
        }

        if (empty($rentalCar->getOnline()) || $rentalCar->getOnline()->getStatus() == 0) {
            return new JsonResponse([
                'error' => self::E_RENTAL_CAR_OFFLINE,
                'errorMessage' => self::M_RENTAL_CAR_OFFLINE,
            ]);
        }

        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rentalCar);

        if (!empty($car_order)) {
            return new JsonResponse([
                'error' => self::E_HAS_RENTAL_ORDER,
                'errorMessage' => self::M_HAS_RENTAL_ORDER,
                'message' => $car_order->getId()
            ]);
        }


        $partner = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Partner')
            ->findOneBy(['code' => $code]);

        if ($back_station_id && $back_station_id != $rentalCar->getRentalStation()->getId()) {
            $backRentalStation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:rentalStation')
                ->find($back_station_id);

            $count = $this->get("auto_manager.station_helper")->get_parking_space_count($backRentalStation);

            if ($count == 0) {
                return new JsonResponse([
                    'error' => self::E_STATION_NO_PARKING_SPACE,
                    'errorMessage' => self::M_STATION_NO_PARKING_SPACE,
                ]);

            }


        } else {
            $backRentalStation = $rentalCar->getRentalStation();
        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Station')
                ->createQueryBuilder('s');

        $rental_station =
            $qb
                ->select('s')
                ->where($qb->expr()->eq('s.id', ':stationID'))
                ->setParameter('stationID', $rentalCar->getRentalStation()->getId())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

        $order = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
        $order->setRentalCar($rentalCar);
        $order->setMobile($mobile);
        $order->setPartner($partner);
        $order->setPickUpStation($rentalCar->getRentalStation());
        $order->setReturnStation($backRentalStation);
        if ($order->getRentalCar()->getRentalStation()->getBackType()
            == \Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK
        ) {
            $order->setReturnStation($rentalCar->getRentalStation());
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('GET', array('api-rental-car-' . $rentalCar->getId() . "-order"));


        $redis_cmd = $redis->createCommand('SET', array('api-rental-car-' . $rentalCar->getId() . "-order", "0"));
        $redis->executeCommand($redis_cmd);


        //检查车是否已有订单
        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rentalCar);

        if (!empty($car_order)) {
            return new JsonResponse([
                'error' => self::E_HAS_RENTAL_ORDER,
                'errorMessage' => self::M_HAS_RENTAL_ORDER,
            ]);
        }

        $man = $this->getDoctrine()->getManager();

        $man->persist($order);
        $man->flush();

        $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
        $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::USER_RETURN_CAR_KIND);
        $dispatch->setRentalCar($order->getRentalCar());
        $dispatch->setRentalStation($backRentalStation);
        $dispatch->setRentalOrder($order);
        $dispatch->setStatus(0);

        $order->getRentalCar()->setRentalStation($backRentalStation);
        $man->persist($rentalCar);
        $man->flush();

        $man->persist($dispatch);
        $man->flush();

        $this->get("auto_manager.sms_helper")->rentalSMS(

            $order->getMember()->getMobile(),
            $order->getRentalCar()->getLicense(),
            $order->getPickUpStation()->getName()

        );


        return new JsonResponse([
            'error' => self::E_OK,
            'order' => call_user_func($this->get("auto_manager.order_helper")->get_rental_order_normalizer(), $order),
        ]);

    }

    /**
     * @Route("/v1/rentalorder/order", methods="POST")
     *
     *
     */
    public function v1OrderAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $this->logUrl = "/v1/rentalorder/order";
        $mobile = $req->request->get('mobile');
        $car_plate = $req->request->get('car');
        $app_key = $req->request->get('appkey');
        $back_station_id = $req->request->get('returnStationID');
        $back_station_id = $this->get("auto_manager.cache_helper")->getStationIdByPartnerStationIdFromCache($back_station_id, $app_key);
//        $partner_id = $req->request->get('partnerID');
        $car_id = $this->get("auto_manager.cache_helper")->getCarIDsByPlates([$car_plate]);

        if (empty($mobile) || $mobile == '' || empty($car_plate) || $car_plate == '' || empty($back_station_id) || '' == $back_station_id) {
            return $this->returnJson($app_key, self::E_SHORT_OF_INFO, self::M_SHORT_OF_INFO);
        }

        if (1 == count($car_id)) {
            $car_id = $car_id[0];
        } else {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($car_id);

        if (empty($rentalCar)) {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }

        if (empty($rentalCar->getOnline()) || $rentalCar->getOnline()->getStatus() == 0) {
            return $this->returnJson($app_key, self::E_RENTAL_CAR_OFFLINE, self::M_RENTAL_CAR_OFFLINE);
        }

        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rentalCar);

        if (!empty($car_order)) {
            return $this->returnJson($app_key, self::E_HAS_RENTAL_ORDER, self::M_HAS_RENTAL_ORDER);
        }

        $partner = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Partner')
            ->findOneBy(['code' => $app_key]);

        if (empty($partner)) {
            return $this->returnJson($app_key, self::E_NO_PARTNER, self::M_NO_PARTNER);
        }

        if ($back_station_id && $back_station_id != $rentalCar->getRentalStation()->getId()) {
            $backRentalStation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:rentalStation')
                ->find($back_station_id);

            if (empty($backRentalStation)) {
                return $this->returnJson($app_key, self::E_NO_STATION, self::M_NO_STATION);
            }

            $count = $this->get("auto_manager.station_helper")->get_parking_space_count($backRentalStation);

            if ($count == 0) {
                return $this->returnJson($app_key, self::E_STATION_NO_PARKING_SPACE, self::M_STATION_NO_PARKING_SPACE);
            }
        } else {
            $backRentalStation = $rentalCar->getRentalStation();
        }

        $order = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
        $order->setRentalCar($rentalCar);
        $order->setMember($partner->getMember());
        $order->setPartner($partner);
        $order->setMobile($mobile);
        $order->setPickUpStation($rentalCar->getRentalStation());
        $order->setReturnStation($backRentalStation);
        if ($order->getRentalCar()->getRentalStation()->getBackType()
            == \Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK
        ) {
            $order->setReturnStation($rentalCar->getRentalStation());
        }

        if (false) {

//            $order->setSource($source);
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('GET', array('api-rental-car-' . $rentalCar->getId() . "-order"));

        $mark = 1;
        while ($redis->executeCommand($redis_cmd) == 0 && $mark <= 6) {
            sleep(1);
            $mark++;
        }

        if ($mark == 6) {
            return $this->returnJson($app_key, self::E_HAS_RENTAL_ORDER, self::M_HAS_RENTAL_ORDER);
        }

        $redis_cmd = $redis->createCommand('SET', array('api-rental-car-' . $rentalCar->getId() . "-order", "0"));
        $redis->executeCommand($redis_cmd);


        //检查车是否已有订单
        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rentalCar);

        if (!empty($car_order)) {
            return $this->returnJson($app_key, self::E_HAS_RENTAL_ORDER, self::M_HAS_RENTAL_ORDER);
        }

        $man = $this->getDoctrine()->getManager();
        $man->persist($order);
        $man->flush();

        $redis_cmd = $redis->createCommand('SET', array('api-rental-car-' . $rentalCar->getId() . "-order", "1"));
        $redis->executeCommand($redis_cmd);

        $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
        $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::USER_RETURN_CAR_KIND);
        $dispatch->setRentalCar($order->getRentalCar());
        $dispatch->setRentalStation($backRentalStation);
        $dispatch->setRentalOrder($order);
        $dispatch->setStatus(0);

        $order->getRentalCar()->setRentalStation($backRentalStation);
        $man->persist($rentalCar);
        $man->flush();

        $man->persist($dispatch);
        $man->flush();

        $carPartnerData = [
            'notCall' => $app_key,
            'carId' => $car_id,
            'carNo' => $car_plate,
            'orderId' => $order->getId(),
            'rentalStation' => $rentalCar->getRentalStation()->getId(),
            'backRentalStation' => $back_station_id,
        ];

        $this->get("auto_manager.partner_helper")->carUnRental($carPartnerData);

        return $this->returnJsonData($app_key, ['orderID' => $order->getId(), 'createTime' => $order->getCreateTime()->getTimestamp()]);
    }

    /**
     * @Route("/v1/rentalorder/cancel", methods="POST")
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function v1RentalorderCancelAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $this->logUrl = "/v1/rentalorder/cancel";
        $mobile = $req->request->get('mobile');
        $order_id = $req->request->get('orderID');
        $partner_id = $req->request->get('partnerID');
        $app_key = $req->request->get('appkey');

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id' => $order_id, 'partner' => $partner_id, 'mobile' => $mobile]);

        if (empty($order)) {
            return $this->returnJson($app_key, self::E_NO_ORDER, self::M_NO_ORDER);
        }

        if ($order->getCancelTime()) {
            return $this->returnJson($app_key, self::E_OK, '订单已经取消');
        }

        $status = $this->get('auto_manager.order_helper')->get_order_status($order);
        if ($mobile == $order->getMobile() && $status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER && !$order->getUseTime()) {

            $order->setCancelTime(new \DateTime());
            if ($order->getPickUpStation()->getBackType()
                == \Auto\Bundle\ManagerBundle\Entity\RentalStation::DIFFERENT_PLACE_BACK
            ) {
                $order->getRentalCar()->setRentalStation($order->getPickUpStation());
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();


            $carPartnerData = [
                'notCall' => $app_key,
                'carId' => $order->getRentalCar()->getId(),
                'carNo' => $this->get("auto_manager.cache_helper")->getCarPlatesByID($order->getRentalCar()->getId()),
                'orderId' => $order_id,
                'backRentalStation' => $order->getPickUpStation()->getId(),
            ];

            $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

            return $this->returnJson($app_key, self::E_OK, self::M_OK);

        } else {

            return $this->returnJson($app_key, self::E_ORDER_PROGRESS, self::M_ORDER_PROGRESS);

        }

    }

    private function checkPartner($code, $timestamp)
    {
        if (abs((new \DateTime())->getTimestamp() - $timestamp) > 120) {
            return ['error' => 1, 'message' => '请检查请求时间'];
        }

        $partner = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Partner')
            ->findOneBy(['code' => $code, 'status' => 1]);

        if (empty($partner)) {
            return ['error' => 1, 'message' => '没有该合作公司'];
        }

        return ['error' => 0, 'secret' => $partner->getSecret()];
    }

    /**
     * 获得签名
     */
    private function getSign($dataArr, $key)
    {

        ksort($dataArr);
        $queryStr = '';
        foreach ($dataArr as $k => $v) {
            $queryStr .= json_encode($k) . '=' . json_encode($v) . '&';
        }
        $resultStr = $queryStr . $key;
        // 拼接签名串
        $signature = urlencode($resultStr);
        // md5加密
        $signValue = md5($signature);
        return $signValue;
    }


    /**
     * 还车
     * @Route("/v1/rentalorder/end", methods="POST")
     */
    public function v1EndRentalOrderAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $this->logUrl = "/v1/rentalorder/end";
        $order_id = $req->request->get('orderID');
        $mobile = $req->request->get('mobile');
        $operator_id = $req->request->get('operator');
        $reason = $req->request->get('reason');
        $remark = $req->request->get('remark');
        $partner_id = $req->request->get('partnerID');
        $app_key = $req->request->get('appkey');

        if (!empty($operator_id)) {
            return $this->manualEndOrder($order_id, $operator_id, $reason, $remark, $app_key);
        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id' => $order_id, 'partner' => $partner_id, 'mobile' => $mobile]);

        if (empty($order)) {
            return $this->returnJson($app_key, self::E_NO_ORDER, self::M_NO_ORDER);
        }

        if ($order->getEndTime()) {
            return $this->returnJson($app_key, self::E_ORDER_END, self::M_ORDER_END);
        }

        if (empty($order->getRentalCar()->getBoxId())) {
            return $this->returnJson($app_key, self::E_NO_CAR_START_DEVICE, self::M_NO_CAR_START_DEVICE);
        }

        if (!$this->checkStationDistance($order->getRentalCar(), $app_key)) {
            return $this->returnJson($app_key, self::E_STATION_CAR_DISTANCE, self::M_STATION_CAR_DISTANCE);
        }

        //熄火
        if ($order->getRentalCar()->getDeviceCompany()->getEnglishName() == 'carStart') {

            sleep(1);
            $redis = $this->container->get('snc_redis.default');

            $redis_cmd = $redis->createCommand('lindex', array($order->getRentalCar()->getDeviceCompany()
                    ->getEnglishName() . '-status-' . $order->getRentalCar()->getBoxId(), 0));

            $fire_json = $redis->executeCommand($redis_cmd);
            $fire_arr = json_decode($fire_json, true);

            if ((new \DateTime())->getTimestamp() - $fire_arr['time'] > 10) {
                return $this->returnJson($app_key, self::E_ON_FIRE, self::M_ON_FIRE);
            }

            if ($fire_arr['status'] == '6F07') {
                return $this->returnJson($app_key, self::E_ON_FIRE, self::M_ON_FIRE);
            }
        }

        //关门
        $member = $order->getPartner()->getMember();
        $result = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'close', $member);
        if (!$result) {
            return $this->returnJson($app_key, self::E_CLOSE_DOOR, self::M_CLOSE_DOOR);
        } else {
            $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'on', $member);

        }

        //断电
        $offresult = $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'off', $member);

        if (!$offresult) {
            return $this->returnJson($app_key, self::E_OFF_POWER, self::M_OFF_POWER);
        }


        $carPartnerData = [
            'notCall' => $app_key,
            'carId' => $order->getRentalCar()->getId(),
            'carNo' => $this->get("auto_manager.cache_helper")->getCarPlatesByID($order->getRentalCar()->getId()),
            'orderId' => $order_id,
            'backRentalStation' => $order->getReturnStation()->getId(),
        ];

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
        if ($range < $order->getRentalCar()->getCar()->getAutoOfflineMileage()) {
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
                'operator' => $operator_id,
                'reason' => $reason,
                'remark' => '',
                'stationId' => $carPartnerData['backRentalStation'],
            ];
            $this->get("auto_manager.partner_helper")->carOffline($carPartnerData);

        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('lindex', array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName() . '-mileage-' . $order->getRentalCar()->getBoxId(), 0));
        $mileage_arr = $redis->executeCommand($redis_cmd);
        $mileage_arr = json_decode($mileage_arr, true);
        if (!empty($mileage_arr)) {
            $order->setEndMileage($mileage_arr['mileage']);
        }

        $order->setEndTime(new \DateTime());

        $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($order);

        $order->setDueAmount($cost_detail['cost']);
        $man->persist($order);
        $man->flush();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder' => $order]);

        if (!empty($dispatch)) {
            $dispatch->setStatus(1);
            $man->persist($dispatch);
            $man->flush();
        }

        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'off', $member);
        $password = $this->get_random_integer(4);

        $this->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(), 'encode', $member, $password);

        $redis_cmd = $redis->createCommand('del', array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName() . '-password-' . $order->getRentalCar()->getBoxId()));
        $redis->executeCommand($redis_cmd);

        $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);

        return $this->returnJson($app_key, self::E_OK, self::M_OK);
    }

    private function checkStationDistance($rentalCar, $app_key)
    {
        if (empty($rentalCar->getBoxId())) {
            return false;
        }

        $stationId = $this->get("auto_manager.cache_helper")->getPartnerStationIdByStationIdFromCache($rentalCar->getRentalStation()->getId(), $app_key);
        if ($stationId != $rentalCar->getRentalStation()->getId()) {
            return true;
        }

        $this->get("auto_manager.rental_car_helper")->operate($rentalCar, 'gps');

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('LINDEX', array($rentalCar->getDeviceCompany()
                ->getEnglishName() . '-gps-' . $rentalCar->getBoxId(), 0));
        $gps_json = $redis->executeCommand($redis_cmd);
        $gps_arr = json_decode($gps_json, true);
        if (!empty($gps_arr) && ((new \DateTime())->getTimestamp() - $gps_arr['time'] < 15 * 60)) {
            $destination = [$rentalCar->getRentalStation()->getLongitude(), $rentalCar->getRentalStation()->getLatitude()];
            $distance = $this->get('auto_manager.amap_helper')->straight_distance($gps_arr['coordinate'], $destination);

            if ((new \DateTime())->getTimestamp() - $gps_arr['time'] <= 5) {
                if ($distance >= self::CAR_STATION_DISTANCE) {
                    return false;
                }
            } else {
                if ($distance >= self::CAR_STATION_DISTANCE_MAX) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    function get_random_integer($length)
    {
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= rand(1, 5);    //生成php随机数
        }
        return $key;
    }

    private function manualEndOrder($orderID, $operatorID, $reason, $remark, $app_key)
    {
        $this->logUrl = "/v1/rentalorder/end --> manual";
        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($orderID);

        if (empty($rentalOrder)) {
            return $this->returnJson($app_key, self::E_NO_ORDER, self::M_NO_ORDER);
        }

        $check_result = $this->checkPartnerOperator($app_key, $operatorID, $rentalOrder->getRentalCar()->getId());

        if (self::E_OK != $check_result['error']) {
            return $this->returnJson($app_key, $check_result['error'], $check_result['message']);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($check_result['member_id']);

        if (empty($member)) {
            return $this->returnJson($app_key, self::E_NO_OPERATOR, self::M_NO_OPERATOR);
        }

        if (empty($rentalOrder)) {
            return $this->returnJson($app_key, self::E_NO_ORDER, self::M_NO_ORDER);
        }

        if (!empty($rentalOrder->getEndTime())) {
            return $this->returnJson($app_key, self::E_ORDER_END, self::M_ORDER_END);
        }


        $reason_arr = json_decode($reason, true);

        if (empty($reason_arr) || !in_array(17, $reason_arr)) {
            $reason_arr[] = 17;
        }
        $man = $this->getDoctrine()->getManager();


        $rentalOrder->setEndTime(new \DateTime());

        $cost_detail = $this->get("auto_manager.order_helper")->get_rental_order_cost($rentalOrder);

        $rentalOrder->setDueAmount($cost_detail['cost']);


        $redis = $this->container->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('lindex', array($rentalOrder->getRentalCar()->getDeviceCompany()
                ->getEnglishName() . '-mileage-' . $rentalOrder->getRentalCar()->getBoxId(), 0));
        $mileage_arr = $redis->executeCommand($redis_cmd);
        $mileage_arr = json_decode($mileage_arr, true);
        if (!empty($mileage_arr)) {
            $rentalOrder->setEndMileage($mileage_arr['mileage']);
        }

        $onlineRecord = new RentalCarOnlineRecord();
        $onlineRecord->setStatus(0);
        $onlineRecord->setRemark($remark . '-- Operate by partner:' . $app_key . ' operator:' . $operatorID);
        $onlineRecord->setReason($reason_arr);
        $onlineRecord->setRentalCar($rentalOrder->getRentalCar());
        $onlineRecord->setMember($member);
        $rentalOrder->getRentalCar()->setOnline($onlineRecord);
        $man->persist($onlineRecord);
        $man->flush();
        $man->persist($rentalOrder);
        $man->flush();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder' => $orderID]);

        if (!empty($dispatch)) {

            $dispatch->setStatus(1);

            $man->persist($dispatch);
            $man->flush();
        }


        //断电

        $this->get("auto_manager.rental_car_helper")->operate($rentalOrder->getRentalCar(), 'off', $member, '');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
            ('auto_api_under_write'), ['OrderID' => $rentalOrder->getId()]);


        $carPartnerData = [
            'notCall' => $app_key,
            'carId' => $rentalOrder->getRentalCar()->getId(),
            'carNo' => $this->get("auto_manager.cache_helper")->getCarPlatesByID($rentalOrder->getRentalCar()->getId()),
            'orderId' => $rentalOrder->getId(),
            'backRentalStation' => $rentalOrder->getPickUpStation()->getId(),
        ];

        $this->get("auto_manager.partner_helper")->carRentalAble($carPartnerData);


        $carPartnerDataOffline = [
            'notCall' => $app_key,
            'carId' => $rentalOrder->getRentalCar()->getId(),
            'operator' => $operatorID,
            'reason' => $reason,
            'remark' => '[Partner]:' . $app_key . ',[Operator]:' . $operatorID . $remark,
            'stationId' => $rentalOrder->getReturnStation()->getId(),
        ];
        $this->get("auto_manager.partner_helper")->carOffline($carPartnerDataOffline);

        return $this->returnJson($app_key, self::E_OK, self::M_OK);
    }


    /**
     * @Route("/v1/car/info", methods="GET")
     *
     */
    public function carInfoAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $this->logUrl = "/v1/car/info";
        $cars = $req->query->get('car');
        $app_key = $req->query->get('appkey');

        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $this->get("auto_manager.cache_helper")->cacheCarPlate();

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('SMEMBERS', array('partner_visible_cars_' . $app_key));
        $visible_cars = $redis->executeCommand($redis_cmd);

        if (!empty($cars)) {
            $car_ids = $this->get("auto_manager.cache_helper")->getCarIDsByPlates(explode(',', $cars));
            $visible_cars = array_intersect($visible_cars, $car_ids);
        }

        if (empty($visible_cars)) {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }

        $redis_cmd = $redis->createCommand('HMGET', array('car_base_info_id_plate', $visible_cars));
        $car_array = $redis->executeCommand($redis_cmd);

        $re_data = array();
        foreach ($car_array as $car) {
            $info = explode(self::SEPARATOR, $car);
            $re_data[] = [
                'licensePlate' => $info[1],
                'station' => $this->get("auto_manager.cache_helper")->getPartnerStationIdByStationIdFromCache($info[2], $app_key)
            ];
        }

        if (0 == count($re_data)) {
            return $this->returnJsonData($app_key, 'No car');
        }
        return $this->returnJsonData($app_key, $re_data);
    }

    /**
     * 获取车辆实时信息
     *
     * @Route("/v1/car/running", methods="GET")
     *
     * @param Request $req : car 车牌
     * @return JsonResponse
     */
    public function carRunningInfoAction(Request $req)
    {
        $this->logUrl = "/v1/car/running";
        $cars = $req->query->get('car');
        $app_key = $req->query->get('appkey');

        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('SMEMBERS', array('partner_visible_cars_' . $app_key));
        $visible_cars = $redis->executeCommand($redis_cmd);

        if (!empty($cars)) {
            $car_ids = $this->get("auto_manager.cache_helper")->getCarIDsByPlates(explode(',', $cars));
            $visible_cars = array_intersect($visible_cars, $car_ids);
        }

        if (empty($visible_cars)) {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }

        // 获取车辆在线状态
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT rc.id, ro.status
                FROM AutoManagerBundle:RentalCar rc
                LEFT JOIN AutoManagerBundle:RentalCarOnlineRecord ro
                  WITH rc.online=ro.id
                  WHERE rc.id IN ( ' . implode(',', $visible_cars) . ' )
                  '
        );
        $results = $query->getResult();
        $carOnlineStatus = array();
        foreach ($results as $item) {
            $carOnlineStatus[$item['id']] = $item['status'];
        }

        //从缓存获取车辆实时信息
        $redis_cmd = $redis->createCommand('HMGET', array('car_base_info_id_plate', $visible_cars));
        $car_array = $redis->executeCommand($redis_cmd);

        $car_boxIds = array();
        $car_plates = array();
        $car_id_plates = array();
        foreach ($car_array as $car) {
            $info = explode(self::SEPARATOR, $car);
            $car_boxIds[] = $info[3];
            $car_plates[] = $info[1];
            $car_id_plates[$info[0]] = $info[1];

            if (isset($carOnlineStatus[$info[0]])) {
                $carOnlineStatus[$info[1]] = $carOnlineStatus[$info[0]];
            }
        }
        if (0 == count($car_boxIds)) {
            return $this->returnJson($app_key, self::E_OK, 'No car');
        }

        $redis_cmd = $redis->createCommand('HMGET', array('feeZu-car-online-status', $car_boxIds));
        $car_status = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('HMGET', array('feeZu-car-running-info-total-mileage', $car_boxIds));
        $car_total_mileages = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('HMGET', array('feeZu-car-running-info-power', $car_boxIds));
        $car_powers = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('HMGET', array('feeZu-car-running-info-remain-mileage', $car_boxIds));
        $car_remain_mileages = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('HMGET', array('feeZu-car-curlocation', $car_boxIds));
        $car_locations = $redis->executeCommand($redis_cmd);

        //整理返回结果
        $re_data = array();
        foreach ($car_plates as $key => $plate) {
            $car_status[$key] = 3;
            if (empty($car_locations[$key])) {
                $re_data['lon'] = '';
                $re_data['lat'] = '';
            } else {
                $location = json_decode($car_locations[$key]);
                $re_data['lon'] = $location[0] . '';
                $re_data['lat'] = $location[1] . '';
            }

            $re_data['licensePlate'] = $plate;
            $re_data['power'] = empty($car_powers[$key]) ? '' : $car_powers[$key];
            $re_data['remainMileage'] = empty($car_remain_mileages[$key]) ? '' : $car_remain_mileages[$key];
            $re_data['totalMileage'] = empty($car_total_mileages[$key]) ? '' : $car_total_mileages[$key];
            $re_data['online'] = !isset($carOnlineStatus[$plate]) ? '' : $carOnlineStatus[$plate] . '';
            $re_data['rental'] = '0';
            $re_data['status'] = empty($car_status[$key]) ? '' : $car_status[$key] == '2' || $car_status[$key] == '3' ? '1' : 0;
        }

        // 车辆出租状态
        $car_ids_str = implode(',', $visible_cars);
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT rc.id
                FROM AutoManagerBundle:BaseOrder bo
                LEFT JOIN AutoManagerBundle:RentalOrder ro
                  WITH bo.id=ro.id
                LEFT JOIN AutoManagerBundle:RentalCar rc
                  WITH ro.rentalCar=rc.id
                  WHERE bo.endTime is NULL AND bo.cancelTime is NULL AND rc.id IN ( ' . $car_ids_str . ' )
                  '
        );
        $car_have_order = $query->getResult();
        foreach ($car_have_order as $item) {
            if (isset($car_id_plates[$item['id']])) {
                $re_data['rental'] = '1';
            }
        }

        return $this->returnJsonData($req->query->get('appkey'), [$re_data]);
    }

    const SEPARATOR = '|';

    private function checkOperate($operate, $forChangeOrder = false)
    {
        switch ($operate) {
            case 'find'://no break;
                if ($forChangeOrder) {
                    return false;
                } else {
                    return true;
                }
                break;
            case 'open'://no break;
            case 'close'://no break;
            case 'off'://no break;
            case 'on'://no break;
            case 'reset':
                return true;
        }
        return false;
    }

    /**
     * @Route("/v1/car/operate", methods="POST")
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function carOperateAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $car_no = $req->request->get('car');
        $mobile = $req->request->get('mobile');
        $operator = $req->request->get('operator');
        $operate = $req->request->get('operate');
        $app_key = $req->request->get('appkey');
        $this->logUrl = "/v1/car/operate --> " . $operate;

        $car_ids = $this->get("auto_manager.cache_helper")->getCarIDsByPlates([$car_no]);

        if (1 == count($car_ids)) {
            $car_id = $car_ids[0];
        } else {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }

        if (!empty($mobile) && empty($operator)) {
            if ($this->checkPartnerMemberOperate($car_id, $mobile, $operate)) {
                $check['error'] = self::E_OK;
                $operateMessage = 'PartnerID:' . $app_key . '  User mobile:' . $mobile;
            } else {
                $check['error'] = self::E_NO_RENTAL_CAR;
                $check['message'] = self::M_NO_RENTAL_CAR;
            }
        } else {
            $operateMessage = 'PartnerID:' . $app_key . '  OperatorID:' . $operator;
            $check = $this->checkPartnerOperator($app_key, $operator, $car_id, true);
        }

        if (self::E_OK == $check['error']) {

            $car = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->find($car_id);

            if (empty($car)) {
                return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
            }

            if ($this->checkOperate($operate)) {
                $re = $this->get("auto_manager.rental_car_helper")->partnerOperate($car, $operate, $operateMessage);
                if ($re) {
                    return $this->returnJson($app_key, self::E_OK, self::M_OK);
                } else {
                    return $this->returnJson($app_key, self::E_OPERATE_FAIL, self::M_OPERATE_FAIL);
                }
            } else {
                return $this->returnJson($app_key, self::E_OPERATE_FAIL, self::M_OPERATE_FAIL);
            }

        } else {
            return $this->returnJson($app_key, $check['error'], $check['message']);
        }
    }


    /**
     * 判断第三方用户是否正在租用车辆
     *
     * @param $car_id
     * @param $mobile
     * @param $operate
     * @return bool
     */
    private function checkPartnerMemberOperate($car_id, $mobile, $operate)
    {
        $dql = 'SELECT ro.id,ro.useTime
                    FROM AutoManagerBundle:BaseOrder bo
                    LEFT JOIN AutoManagerBundle:RentalOrder ro
                      WITH bo.id=ro.id
                    LEFT JOIN AutoManagerBundle:RentalCar rc
                      WITH ro.rentalCar=rc.id
                      WHERE bo.endTime is NULL AND bo.cancelTime is NULL AND rc.id=' . $car_id . ' AND ro.mobile=' . "'" . $mobile . "'";

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            $dql
        );
        $car_have_order = $query->getResult();
        // 设置取车状态
        foreach ($car_have_order as $order) {
            if (isset($order['id']) && empty($order['useTime'])) {
                $this->setOrderRentalCarUsed($order['id'], $operate);
            }
        }

        return !empty($car_have_order);
    }

    /**
     *
     * 对用户非find操作  将订单置为已取车
     * @param $orderId
     * @param $operate
     */
    private function setOrderRentalCarUsed($orderId, $operate)
    {
        if ($this->checkOperate($operate, true)) {
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->find($orderId);
            $order->setUseTime(new \DateTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
        }
    }


    /**
     * @Route("/v1/car/online", methods="POST")
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function onlineAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $car_no = $req->request->get('car');
        $operator = $req->request->get('operator');
        $partner_id = $req->request->get('partnerID');

        $reason = $req->request->get('reason');
        $remark_base = $req->request->get('remark');
        $station_id = $req->request->get('stationID');
        $app_key = $req->request->get('appkey');
        $status = $req->request->getInt('status');
        $station_id = $this->get("auto_manager.cache_helper")->getStationIdByPartnerStationIdFromCache($station_id, $app_key);

        $this->logUrl = "/v1/car/online --> " . $status;

        // 判断传入状态是否合法
        $operation = '上线';
        if (0 == $status) {
            $operation = '下线';
        } elseif (1 != $status) {
            return $this->returnJson($app_key, self::E_OPERATE_FAIL, self::M_OPERATE_FAIL);
        }

        $remark = $remark_base . '-- Operate by partner:' . $app_key . ' operator:' . $operator;// 记录操作人

        $partner = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Partner')
            ->find($partner_id);

        if (empty($partner)) {
            return $this->returnJson($app_key, self::E_NO_PARTNER, self::M_NO_PARTNER);
        }

        // 获取车辆并判断是否有车辆权限
        $car_ids = $this->get("auto_manager.cache_helper")->getCarIDsByPlates($car_no);
        if (0 == count($car_ids)) {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        } else {
            $car_id = $car_ids[0];
        }

        $check = $this->checkPartnerOperator($app_key, $operator, $car_id);
        if (self::E_OK != $check['error']) {
            return $this->returnJson($app_key, $check['error'], $check['message']);
        }
        if (-1 == $car_id) {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }
        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($car_id);
        if (empty($rental_car)) {
            return $this->returnJson($app_key, self::E_NO_RENTAL_CAR, self::M_NO_RENTAL_CAR);
        }

        //检查车辆状态
        if ($status == empty($rental_car->getOnline()) ? 0 : $rental_car->getOnline()->getStatus()) {
            return $this->returnJson($app_key, self::E_OK, '车辆已是' . $operation . '状态');
        }

        //新建车辆上下线记录
        $onlineRecord = new \Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord();
        $onlineRecord->setRentalCar($rental_car);
        $onlineRecord->setStatus($status);
        $onlineRecord->setMember($partner->getMember());
        if ($reason) $onlineRecord->setReason(json_decode($reason, true));
        if ($remark) $onlineRecord->setRemark($remark);

        $man = $this->getDoctrine()->getManager();
        $man->persist($onlineRecord);
        $man->flush();

        $rental_car->setOnline($onlineRecord);

        //如果车辆上线 对车辆进行调度
        if (1 == $status) {
            $rentalStation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->find($station_id);

            if (empty($rentalStation)) {
                return $this->returnJson($app_key, self::E_NO_STATION, self::M_NO_STATION);
            }

            $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();

            $dispatch->setCreateTime(new \DateTime())
                ->setKind(1)
                ->setRentalCar($rental_car)
                ->setRentalStation($rentalStation)
                ->setStatus(1)
                ->setOperateMember($this->getUser());
            $man = $this->getDoctrine()->getManager();
            $man->persist($dispatch);
            $man->flush();
            $rental_car->setRentalStation($rentalStation);
        }

        $man = $this->getDoctrine()->getManager();
        $man->persist($rental_car);
        $man->flush();

        $carPartnerData = [
            'notCall' => $app_key,
            'carId' => $car_id,
            'carNo' => $car_no,
            'operator' => $operator,
            'reason' => $reason,
            'remark' => '[Partner]:' . $app_key . ',[Operator]:' . $operator . $remark_base,
            'stationId' => $station_id,
        ];

        if (0 == $status) {
            $this->get("auto_manager.partner_helper")->carOffline($carPartnerData);
        } else {
            $this->get("auto_manager.partner_helper")->carOnline($carPartnerData);
        }

        return $this->returnJson($app_key, self::E_OK, self::M_OK);
    }

    /**
     * @Route("/v1/order/changeReturnStation", methods="POST")
     *
     * 更换还车点
     *
     */
    public function changeReturnStationAction(Request $req)
    {
        //更新缓存车辆信息
        $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars();
        $partner_id = $req->request->get('partnerID');
        $mobile = $req->request->get('mobile');
        $order_id = $req->request->get('orderID');
        $rental_station_id = $req->request->get('rentalStationID');
        $this->log($rental_station_id);
        $app_key = $req->request->get('appkey');
        $rental_station_id = $this->get("auto_manager.cache_helper")->getStationIdByPartnerStationIdFromCache($rental_station_id, $app_key);

        $this->logUrl = "/v1/order/changeReturnStation --> " . $rental_station_id;

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id' => $order_id, 'partner' => $partner_id, 'mobile' => $mobile]);
        if (empty($rentalOrder)) {
            return $this->returnJson($app_key, self::E_NO_ORDER, self::M_NO_ORDER);
        }
        $member = $rentalOrder->getPartner()->getMember();

        $this->checkMemberRentalOrder($member, $rentalOrder);

        if ($rentalOrder->getPickUpStation()->getBackType() == \Auto\Bundle\ManagerBundle\Entity\RentalStation::SAME_PLACE_BACK) {
            return $this->returnJson($app_key, self::E_UNABLE_CHANGE_STATION, self::M_UNABLE_CHANGE_STATION);
        }

        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($rental_station_id);
        if (empty($rentalStation)) {
            return $this->returnJson($app_key, self::E_NO_STATION, self::M_NO_STATION);
        }

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
                ->getSingleScalarResult();

        if ($rentalStation->getUsableParkingSpace() <= $rental_car_count) {

            return $this->returnJson($app_key, self::E_UNABLE_CHANGE_STATION, self::M_UNABLE_CHANGE_STATION);
        }


        $man = $this->getDoctrine()->getManager();

        $dispatch = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:DispatchRentalCar')
            ->findOneBy(['rentalOrder' => $rentalOrder->getId()]);

        if (!empty($dispatch)) {
            $dispatch->setRentalStation($rentalStation);
            $man->persist($dispatch);
            $man->flush();
        }

        $rentalOrder->setReturnStation($rentalStation);
        $rentalOrder->getRentalCar()->setRentalStation($rentalStation);

        $man->persist($rentalOrder);
        $man->flush();

        return $this->returnJson($app_key, self::E_OK, self::M_OK);
    }

    /**
     * 传入第三方id和第三方运营人员id，返回对应member id和是否可操作
     * @param $app_key
     * @param $operatorID
     * @param $carID
     * @param $operate
     * @param bool $is_limit
     * @return array
     */
    private function checkPartnerOperator($app_key, $operatorID, $carID, $operate = null, $is_limit = false)
    {
        // 从缓存读取合作方的数据
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('GET', array('partner_member_id_' . $app_key));
        $member_id = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('GET', array('partner_operate_limit_' . $app_key));
        $operate_limit = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('SMEMBERS', array('partner_operate_ids_' . $app_key));
        $operator_ids = $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('SMEMBERS', array('partner_visible_cars_' . $app_key));
        $cars = $redis->executeCommand($redis_cmd);

        // 缓存没数据 从数据库读取，并刷新到缓存里
        if (empty($member_id) || empty($cars) || empty($operate_limit) || empty($operate_ids)) {
            $this->get("auto_manager.cache_helper")->cachePartnerVisibleCars(true);
        }

        // 没有车的控制权
        if (!in_array($carID, $cars)) {
            return ['error' => self::E_NO_RENTAL_CAR,
                'message' => self::M_NO_RENTAL_CAR,
//                'message' => ['carid'=>$carID,'array'=>$cars],
                'member_id' => $member_id];
        }

        // 不存在的运营人员
        if (!in_array($operatorID, $operator_ids)) {
            return ['error' => self::E_NO_OPERATOR, 'message' => self::M_NO_OPERATOR, 'member_id' => $member_id];
        }

        //是否限制运营人员操作次数
        if ($is_limit) {
            $redis_cmd = $redis->createCommand('TTL', array('partner_operate_limit_' . $app_key . '_' . $operatorID));
            $second = $redis->executeCommand($redis_cmd);
            if ($second > 3600) {
                return ['error' => self::E_FREQUENT_OPERATION, 'message' => self::M_FREQUENT_OPERATION, 'member_id' => $member_id];
            }

            // 通过 partner_operate_limit_partnerID_operatorID 的过期时间控制运营人员是否可操作
            if ($second <= 0 && $operate_limit > 0) {
                $redis_cmd = $redis->createCommand('SET', array('partner_operate_limit_' . $app_key . '_' . $operatorID, 1));
                $redis->executeCommand($redis_cmd);
                $redis_cmd = $redis->createCommand('EXPIRE', array('partner_operate_limit_' . $app_key . '_' . $operatorID, 2 * $operate_limit));
                $redis->executeCommand($redis_cmd);
            } else {
                $redis_cmd = $redis->createCommand('EXPIRE', array('partner_operate_limit_' . $app_key . '_' . $operatorID, $second + $operate_limit));
                $redis->executeCommand($redis_cmd);
            }
        }

        return ['error' => self::E_OK, 'member_id' => $member_id];
    }

    /**
     * 返回Json message 主要处理 成功、失败和其他message类型数据
     *
     * @param $appKey
     * @param $errorCode
     * @param $message
     * @return JsonResponse
     */
    private function returnJson($appKey, $errorCode, $message)
    {
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('GET', array('partner_key_' . $appKey));
        $secret = $redis->executeCommand($redis_cmd);

        $re = [
            'content' => [
                'message' => $message
            ],
            'errorCode' => $errorCode,
            'timestamp' => $this->microtime_float(),
        ];

        $re['sign'] = $this->getSign($re, $secret);
        $this->log($re);
        return new JsonResponse($re);
    }

    /**
     * 返回Json数据结果
     *
     * @param $appKey
     * @param $data
     * @return JsonResponse
     */
    private function returnJsonData($appKey, $data)
    {
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('GET', array('partner_key_' . $appKey));
        $secret = $redis->executeCommand($redis_cmd);

        $re = [
            'content' => $data,
            'errorCode' => self::E_OK,
            'timestamp' => $this->microtime_float(),
        ];

        $re['sign'] = $this->getSign($re, $secret);
        $this->log($re);
        return new JsonResponse($re);
    }

    private $logUrl = '';
    const LOG = false;

    /**
     * 记录partner 返回
     * @param $data
     */
    private function log($data)
    {
        if (!self::LOG)
            return;
        $logs = $this->logUrl . '[' . date('y/m/d H:i:s') . ']: ';
        $logs .= 'Return Data: ' . json_encode($data) . "\n";
        file_put_contents('/data/logs/partnerConnect.log', $logs, FILE_APPEND);
    }

    private function microtime_float()
    {

        list($usec, $sec) = explode(" ", microtime());

        return (int)(($usec + $sec) * 1000);
    }
}
