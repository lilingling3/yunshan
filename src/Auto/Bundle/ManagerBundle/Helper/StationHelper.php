<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/11
 * Time: 下午6:00
 */

namespace Auto\Bundle\ManagerBundle\Helper;

use Auto\Bundle\ManagerBundle\Entity\Area;

class StationHelper extends AbstractHelper
{

    /**
     * @param array $stationIds
     * @return array
     */
    public function getUseAbleCarFromStationIds($stationIds)
    {
        if (empty($stationIds) || !is_array($stationIds))
        {
            return null;
        }

        $query = $this->em->createQuery(
            'SELECT rc.id
                FROM AutoManagerBundle:BaseOrder bo
                LEFT JOIN AutoManagerBundle:RentalOrder ro
                  WITH bo.id=ro.id
                LEFT JOIN AutoManagerBundle:RentalCar rc
                  WITH ro.rentalCar=rc.id
                LEFT JOIN AutoManagerBundle:Station st
                  WITH rc.rentalStation=st.id
                LEFT JOIN AutoManagerBundle:RentalCarOnlineRecord ol
                  WITH rc.online=ol.id
                  WHERE bo.endTime is NULL AND bo.cancelTime is NULL AND ol.status=1 AND st.id IN ( ' . implode(',', $stationIds) . ' )
                  '
        );

        $cars = $query->getResult();

        return $cars;
    }

    /**
     * 根据站点id 从数据库中获取站点信息
     * @param $ids
     * @return array
     */
    public function getStationsFromDByIds($ids = null)
    {
        $dql = 'SELECT st.id,st.name,st.street,st.latitude,st.longitude,st.online,rs.backType,ar.id area
                FROM AutoManagerBundle:RentalStation rs
                LEFT JOIN AutoManagerBundle:Station st
                  WITH st.id=rs.id
                LEFT JOIN AutoManagerBundle:Area ar
                  WITH ar.id=st.area ';

        if (!empty($ids) && is_array($ids)) {
            $query = $this->em->createQuery(
                $dql . 'WHERE st.id IN (' . implode(',', $ids) . ')');
        } else {
            $query = $this->em->createQuery($dql);
        }

        $stations = $query->getResult();

        return $stations;
    }

    /**
     * 缓存车辆站点
     * @param bool $force 是否强制刷新缓存
     * @return bool|mixed
     */
    public function cacheStationBase($force = false)
    {
        if (!$force && $this->exist(self::H_STATION_BASE)) {
            return true;
        }

        $stations = $this->getStationsFromDByIds();
        $re = $this->hSetJson(self::H_STATION_BASE, 'id', $stations, true);

        return $re;
    }

    /**
     * 从缓存读取租赁站点
     * @param array $ids : 站点id 数组
     * @return array
     */
    public function getStationsByIds($ids = null)
    {
        if (($ids != null && !is_array($ids)) || empty($ids)) {
            $ids = null;
        }
        $this->cacheStationBase();

        $jsonStations = $this->hGetJson(self::H_STATION_BASE, $ids);

        $stationIdsNotInCache = $ids;
        $stations = array();
        foreach ($jsonStations as $station) {
            if (empty($station))
                continue;
            $station = json_decode($station, true);
            $stations[$station['id']] = $station;

            if (($key = array_search($station['id'], $stationIdsNotInCache)) !== false) {
                unset($stationIdsNotInCache[$key]);
            }
        }

        // get stations from db witch not in cache, if cache dose not include all data, to refresh cache by force
        $stationsFromDB = $this->getStationsFromDByIds($stationIdsNotInCache);
        if (count($stationsFromDB) > 0) {
            $this->cacheStationBase(true);
            $stations = array_merge($stations, $stationsFromDB);
        }

        return empty($stations) ? null : $stations;
    }


    /**
     * 根据id获取租赁点信息
     **/

    public function getStationByArea(\Auto\Bundle\ManagerBundle\Entity\Area $area)
    {
        $qb = $this->em->createQueryBuilder();

        return
            $qb
                ->select('s')
                ->from('AutoManagerBundle:Station', 's')
                ->join('s.area', 'a')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('s.area', ':area'),
                    $qb->expr()->eq('a.parent', ':area')
                ))
                ->setParameter('area', $area)
                ->getQuery()
                ->getResult();
    }

    public function getUseableStationByArea(\Auto\Bundle\ManagerBundle\Entity\Area $area)
    {
        $qb = $this->em->createQueryBuilder();

        return
            $qb
                ->select('s')
                ->from('AutoManagerBundle:Station', 's')
                ->join('s.area', 'a')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('s.area', ':area'),
                    $qb->expr()->eq('a.parent', ':area')
                ))
                ->andWhere($qb->expr()->eq('s.online', ':online'))
                ->setParameter('online', 1)
                ->setParameter('area', $area)
                ->getQuery()
                ->getResult();
    }

    /**
     * 根据坐标查找最近的区域
     *
     * @param $lat
     * @param $lon
     * @param bool $areaOnly : 是否是最小单位区 不包括省或市
     * @param Area $areas
     * @return Area
     */
    public function getNearestArea($lat, $lon, $areas = null, $areaOnly = true)
    {
        if ($areas == null) {
            $areas = $this->getContainer()
                ->get('doctrine')
                ->getManager()
                ->getRepository('AutoManagerBundle:Area')
                ->findAll();
        }

        $nearestAreaKey = -1;
        $distance = 1000000000000000000;
        foreach ($areas as $key => $area) {

            if ($areaOnly && count($area->getChildren()) > 0) {
                continue;
            }
            $tmpDistance = $this->gpsHelper->distance($area->getLatitude(), $area->getLongitude(), $lat, $lon);
            if ($distance > $tmpDistance) {
                $nearestAreaKey = $key;
                $distance = $tmpDistance;
            }
        }

        if ($nearestAreaKey >= 0) {
            return $areas[$nearestAreaKey];
        }
        return null;
    }

    /**
     * 根据最大最小经纬坐标
     **/

    public function getStationByLatlng($max_lat, $min_lat, $max_lng, $min_lng, $back_type = null)
    {

        $qb = $this->em->createQueryBuilder();

        $qb
            ->select('s')
            ->from('AutoManagerBundle:RentalStation', 's')
            ->where($qb->expr()->gte('s.latitude', ':minlat'))
            ->andWhere($qb->expr()->lte('s.latitude', ':maxlat'))
            ->andWhere($qb->expr()->gte('s.longitude', ':minlng'))
            ->andWhere($qb->expr()->lte('s.longitude', ':maxlng'))
            ->andWhere($qb->expr()->eq('s.online', ':online'))
            ->setParameter('minlat', $min_lat)
            ->setParameter('maxlat', $max_lat)
            ->setParameter('minlng', $min_lng)
            ->setParameter('maxlng', $max_lng)
            ->setParameter('online', 1);

        if ($back_type) {
            $qb->andWhere($qb->expr()->eq('s.backType', ':backType'))
                ->setParameter('backType', $back_type);
        }
        return
            $qb->getQuery()
                ->getResult();

    }

    public function get_station_normalizer()
    {

        return function (\Auto\Bundle\ManagerBundle\Entity\RentalStation $s) {

            $status_count = $this->get_rental_car_status_count($s);

            $depositArea = $this->get_station_deposit_status($s->getArea()->getId());

            $station = [
                'rentalStationID' => $s->getId(),
                'name' => $s->getName(),
                'latitude' => $s->getLatitude(),
                'longitude' => $s->getLongitude(),
                'city' => $s->getArea()->getParent()->getName(),
                'areaID' => $s->getArea()->getId(),
                'street' => $s->getArea()->getParent()->getName() . $s->getArea()->getName() . $s->getStreet(),
                'totalRentalCarCount' => $status_count['total'],
                'onlineRentalCarCount' => $status_count['online'],
                'offlineRentalCarCount' => $status_count['offline'],
                'processRentalCarCount' => $status_count['progress'],
                'usableRentalCarCount' => $status_count['usable'],
                'backType' => $s->getBackType(),
                'isNeedDeposit' => $depositArea ? $depositArea->getIsNeed2Deposit() : 0,
                'needDepositAmount' => $depositArea ? $depositArea->getNeedDepositAmount() : 0,
                'stationDiscount' => $this->rental_station_discount($s),
                'parkingSpaceCount' => $this->get_parking_space_count($s),
                'bigImages' => array_map(function ($img) {
                    return $this->templating->render(
                        '{{ localname|photograph }}',
                        ['localname' => $img]
                    );
                }, $s->getImages()),

                'images' => array_map(function ($img) {
                    return $this->templating->render(
                        '{{ localname|photograph(200,150,"crop") }}',
                        ['localname' => $img]
                    );
                }, $s->getImages()),
            ];

            return $station;
        };
    }


    //2.4.0使用
    public function get_station_data_normalizer()
    {

        return function (\Auto\Bundle\ManagerBundle\Entity\RentalStation $s) {

            $status_count = $this->get_rental_car_status_count($s);

            $depositArea = $this->get_station_deposit_status($s->getArea()->getId());

            $station = [
                'rentalStationID' => $s->getId(),
                'name' => $s->getName(),
                'latitude' => $s->getLatitude(),
                'longitude' => $s->getLongitude(),
                'city' => $s->getArea()->getParent()->getName(),
                'areaID' => $s->getArea()->getId(),
                'street' => $s->getArea()->getParent()->getName() . $s->getArea()->getName() . $s->getStreet(),
                'totalRentalCarCount' => $status_count['total'],
                'onlineRentalCarCount' => $status_count['online'],
                'offlineRentalCarCount' => $status_count['offline'],
                'processRentalCarCount' => $status_count['progress'],
                'usableRentalCarCount' => $status_count['usable'],
                'backType' => $s->getBackType(),
                'isNeedDeposit' => $depositArea ? $depositArea->getIsNeed2Deposit() : 0,
                'needDepositAmount' => $depositArea ? $depositArea->getNeedDepositAmount() : 0,
                'stationDiscount' => $this->get_rental_station_discount($s),
                'parkingSpaceCount' => $this->get_parking_space_count($s),
                'bigImages' => array_map(function ($img) {
                    return $this->curlHelper->base_url() . $this->templating->render(
                            '{{ localname|photograph }}',
                            ['localname' => $img]
                        );
                }, $s->getImages()),

                'images' => array_map(function ($img) {
                    return $this->curlHelper->base_url() . $this->templating->render(
                            '{{ localname|photograph(200,150,"crop") }}',
                            ['localname' => $img]
                        );
                }, $s->getImages()),
            ];

            return $station;
        };
    }


    public function get_parking_space_count(\Auto\Bundle\ManagerBundle\Entity\RentalStation $station)
    {
        $qb = $this->em->createQueryBuilder();
        $using_count =
            $qb
                ->select($qb->expr()->count('c'))
                ->from('AutoManagerBundle:RentalCar', 'c')
                ->where($qb->expr()->eq('c.rentalStation', ':station'))
                ->setParameter('station', $station)
                ->getQuery()
                ->getSingleScalarResult();
        $count = $station->getParkingSpaceTotal() - $using_count;
        return $count > 0 ? $count : 0;

    }

    public function get_charging_station_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\ChargingStation $s) {

            $station = [
                'stationID' => $s->getId(),
                'name' => $s->getName(),
                'latitude' => $s->getLatitude(),
                'longitude' => $s->getLongitude(),
                'address' => $s->getAddress(),
                'currentStatus' => $s->getCurrentStatus(),
                'isActive' => $s->getIsActive(),
                'portType' => $s->getPortType(),
                'fastCount' => $s->getFastCount(),
                'slowCount' => $s->getSlowCount(),
                'nature' => $s->getNature(),
                'type' => $s->getType(),
                'authStatus' => $s->getAuthStatus(),
                'evCount' => $s->getEvCount()
            ];

            return $station;
        };
    }

    public function get_rental_car_status_count(\Auto\Bundle\ManagerBundle\Entity\RentalStation $station)
    {

        $cars_count = ['online' => 0, 'offline' => 0, 'progress' => 0, 'usable' => 0, 'total' => count($station->getRentalCars())];

        foreach ($station->getRentalCars() as $c) {

            $qb = $this->em->createQueryBuilder();
            $order =
                $qb
                    ->select('o')
                    ->from('AutoManagerBundle:RentalOrder', 'o')
                    ->where($qb->expr()->eq('o.rentalCar', ':car'))
                    ->andWhere($qb->expr()->isNull('o.endTime'))
                    ->andWhere($qb->expr()->isNull('o.cancelTime'))
                    ->setParameter('car', $c)
                    ->getQuery()
                    ->getResult();

            if (!empty($c->getOnline()) && $c->getOnline()->getStatus() == 1) {
                $cars_count['online']++;
            }
            if (empty($c->getOnline()) || $c->getOnline()->getStatus() == 0) {
                $cars_count['offline']++;
            }

            if (!empty($order)) {
                $cars_count['progress']++;
            }

            if (empty($order) && !empty($c->getOnline()) && $c->getOnline()->getStatus() == 1) {
                $cars_count['usable']++;
            }

        }

        return $cars_count;


    }


    //获得租赁点折扣信息
    public function get_station_discount_normalizer()
    {

        return function (\Auto\Bundle\ManagerBundle\Entity\RentalStation $s) {

            $station_discount = $this->rental_station_discount($s);

            return $station_discount;
        };
    }


    //2.4.0租赁点优惠信息获取
    public function rental_station_discount(\Auto\Bundle\ManagerBundle\Entity\RentalStation $station)
    {

        $qb = $this->em->createQueryBuilder();

        $rental_station_discount =
            $qb
                ->select('r')
                ->from('AutoManagerBundle:RentalStationDiscount', 'r')
                ->andWhere($qb->expr()->lte('r.startTime', ':time'))
                ->andWhere($qb->expr()->gte('r.endTime', ':time'))
                ->andwhere($qb->expr()->eq('r.rentalStation', ':rentalStation'))
                ->setParameter('time', (new \DateTime()))
                ->setParameter('rentalStation', $station)
                ->getQuery()
                ->getOneOrNullResult();

        if (!empty($rental_station_discount)) {

            $station_discount = [
                'kind' => $rental_station_discount->getKind(),
                'startTime' => $rental_station_discount->getStartTime()->format('Y/m/d H:i'),
                'endTime ' => $rental_station_discount->getEndTime()->format('Y/m/d H:i'),
                'discount' => $rental_station_discount->getDiscount(),
            ];

        } else {

            $station_discount = [
                'kind' => '',
                'startTime' => '',
                'endTime ' => '',
                'discount' => '1',

            ];


        }

        return $station_discount;

    }


    //首页展示车辆存在优惠租赁点显示sall图标
    public function get_rental_station_discount(\Auto\Bundle\ManagerBundle\Entity\RentalStation $station)
    {

        $qb = $this->em->createQueryBuilder();

        $rental_station_discount =
            $qb
                ->select('r')
                ->from('AutoManagerBundle:RentalStationDiscount', 'r')
                ->andWhere($qb->expr()->lte('r.startTime', ':time'))
                ->andWhere($qb->expr()->gte('r.endTime', ':time'))
                ->andwhere($qb->expr()->eq('r.rentalStation', ':rentalStation'))
                ->setParameter('time', (new \DateTime()))
                ->setParameter('rentalStation', $station)
                ->getQuery()
                ->getOneOrNullResult();

        if (!empty($rental_station_discount)) {

            $station_discount = [
                'kind' => $rental_station_discount->getKind(),
                'startTime' => $rental_station_discount->getStartTime()->format('Y/m/d H:i'),
                'endTime ' => $rental_station_discount->getEndTime()->format('Y/m/d H:i'),
                'discount' => $rental_station_discount->getDiscount(),
            ];

        } else {

            $stationDiscount = 1;

            $qb = $this->em->createQueryBuilder();

            $rentalCars =
                $qb
                    ->select('c')
                    ->from('AutoManagerBundle:RentalCar', 'c')
                    ->join('c.online', 'o')
                    ->where($qb->expr()->eq('c.rentalStation', ':station'))
                    ->andWhere($qb->expr()->eq('o.status', ':status'))
                    ->setParameter('station', $station)
                    ->setParameter('status', 1)
                    ->getQuery()
                    ->getResult();


            if (!empty($rentalCars)) {

                foreach ($rentalCars as $re) {

                    $rental_car_status = $this->rentalCarHelper->get_rental_car_status($re);

                    $qb = $this->em->createQueryBuilder();
                    $carDiscount = $qb
                        ->select('c')
                        ->from('AutoManagerBundle:CarDiscount', 'c')
                        ->andWhere($qb->expr()->lte('c.startTime', ':time'))
                        ->andWhere($qb->expr()->gte('c.endTime', ':time'))
                        ->andwhere($qb->expr()->eq('c.car', ':car'))
                        ->setParameter('time', (new \DateTime()))
                        ->setParameter('car', $re->getCar())
                        ->getQuery()
                        ->getOneOrNullResult();

                    if (!empty($carDiscount) && $rental_car_status != 301) {

                        $stationDiscount = 0.9;

                        break;

                    }

                }

            }

            $station_discount = [
                'kind' => '',
                'startTime' => '',
                'endTime ' => '',
                'discount' => $stationDiscount,
            ];

        }

        return $station_discount;


    }


    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

    public function setGPSHelper($gpsHelper)
    {
        $this->gpsHelper = $gpsHelper;
    }


    public function setRentalCarHelper($rentalCarHelper)
    {
        $this->rentalCarHelper = $rentalCarHelper;
    }

    /**
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalStationDiscount $o
     * @return bool
     * 添加折扣时需判断同一个租赁点在设置的时间段内是否存在已有折扣，有则返回true，否则返回false
     */
    public function check_rental_station_discount_overlaps(\Auto\Bundle\ManagerBundle\Entity\RentalStationDiscount $o)
    {

        $qb = $this->em->createQueryBuilder();

        $qb = $qb->select('r')
            ->from('AutoManagerBundle:RentalStationDiscount', 'r')
            ->andWhere($qb->expr()->lte('r.startTime', ':time'))
            ->andWhere($qb->expr()->gte('r.endTime', ':time'))
            ->andwhere($qb->expr()->eq('r.rentalStation', ':rentalStation'))
            ->setParameter('time', $o->getStartTime())
            ->setParameter('rentalStation', $o->getRentalStation());

        if ($o->getId()) {
            $qb = $qb->andWhere($qb->expr()->neq('r.id', ':id'))
                ->setParameter('id', $o->getId());
        }
        $discount_start_time =
            $qb
                ->getQuery()
                ->getResult();

        if (!empty($discount_start_time)) {
            return true;
        }
        $qb2 = $this->em->createQueryBuilder();
        $qb2 = $qb2->select('r')
            ->from('AutoManagerBundle:RentalStationDiscount', 'r')
            ->andWhere($qb2->expr()->lte('r.startTime', ':time'))
            ->andWhere($qb2->expr()->gte('r.endTime', ':time'))
            ->andwhere($qb2->expr()->eq('r.rentalStation', ':rentalStation'))
            ->setParameter('time', $o->getEndTime())
            ->setParameter('rentalStation', $o->getRentalStation());

        if ($o->getId()) {
            $qb2 = $qb2->andWhere($qb2->expr()->neq('r.id', ':id'))
                ->setParameter('id', $o->getId());
        }
        $discount_end_time =
            $qb2
                ->getQuery()
                ->getResult();

        if (!empty($discount_end_time)) {
            return true;
        }

        $qb3 = $this->em->createQueryBuilder();
        $qb3 = $qb3->select('r')
            ->from('AutoManagerBundle:RentalStationDiscount', 'r')
            ->andWhere($qb3->expr()->gte('r.startTime', ':startTime'))
            ->andWhere($qb3->expr()->lte('r.endTime', ':endTime'))
            ->andwhere($qb3->expr()->eq('r.rentalStation', ':rentalStation'))
            ->setParameter('startTime', $o->getStartTime())
            ->setParameter('endTime', $o->getEndTime())
            ->setParameter('rentalStation', $o->getRentalStation());

        if ($o->getId()) {
            $qb3 = $qb3->andWhere($qb3->expr()->neq('r.id', ':id'))
                ->setParameter('id', $o->getId());
        }
        $discount_time =
            $qb3
                ->getQuery()
                ->getResult();

        if (!empty($discount_time)) {
            return true;
        }

        return false;

    }

    private function get_station_deposit_status($id)
    {

        $qb = $this->em->createQueryBuilder();

        $area_status = $qb
            ->select('a')
            ->from('AutoManagerBundle:DepositArea', 'a')
            ->andwhere($qb->expr()->eq('a.area', ':area'))
            ->setParameter('area', $id)
            ->getQuery()
            ->getOneOrNullResult();


        return $area_status;
    }

}


