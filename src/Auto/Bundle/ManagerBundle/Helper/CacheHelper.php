<?php
/**
 * 缓存 helper
 *
 * Created by PhpStorm.
 * User: Ma
 * Date: 17/4/25
 * Time: 下午5:05
 */

namespace Auto\Bundle\ManagerBundle\Helper;

use Monolog\Handler\PHPConsoleHandler;

class CacheHelper extends AbstractHelper
{
    private $redisHelper;

    public function setRedisHelper($redisHelper)
    {
        $this->redisHelper = $redisHelper;
    }

    /**
     * @param $carsRunningInfo array like {[boxId=>11,time=>1,power=>11,range=>11,mileage=>1,lng=>1,lat=>1,status=>1,speed=>1],[]}
     * @param $abandonSecond integer 当前时间与车辆信息时间超过这个值时  不存入
     */
    public function cacheCarRunningInfo($carsRunningInfo, $abandonSecond = 20)
    {
        foreach ($carsRunningInfo as $item) {
//            echo json_encode($item) . PHP_EOL;
//
            if (!isset($item['boxId'])) {
                continue;
            }

            if (isset($item['status'])) {
                $this->cacheOnline($item['boxId'], $item['status']);
            }
            if (isset($item['speed'])) {
                $this->cacheSpeed($item['boxId'], $item['speed']);
            }

            if (!isset($item['time'])) {
                continue;
            }

            if (strlen($item['time']) > 10) {
                $item['time'] = substr($item['time'], 0, 10);
//                echo 'time stamp : ' . $item['time'] . PHP_EOL;
            }

            $timeDiff = time() - $item['time'] > $abandonSecond;
            if ($timeDiff > $abandonSecond) {
//                echo ''
                continue;
            }
            if ($item['time'] - time() > $abandonSecond) {
                // --
                continue;
            }

            if (isset($item['power'])) {
                $this->cachePower($item['boxId'], $item['power'], $item['time']);
            }
            if (isset($item['range'])) {
                $this->cacheRange($item['boxId'], $item['range'], $item['time']);
            }
            if (isset($item['mileage'])) {
                $this->cacheMileage($item['boxId'], $item['mileage'], $item['time']);
            }
            if (isset($item['lng']) && isset($item['lat'])) {
                $this->cacheGPS($item['boxId'], $item['lng'], $item['lat'], $item['time']);
            }
        }
    }

    /**
     * 缓存车辆电量
     * @param $boxId
     * @param $power
     * @param $time
     */
    private function cachePower($boxId, $power, $time)
    {
        $this->saveCarDataToList($boxId, $power, $time, self::P_CAR_POWER__BOXID_);
    }

    /**
     * 缓存车辆续航
     * @param $boxId
     * @param $range
     * @param $time
     */
    private function cacheRange($boxId, $range, $time)
    {
        $this->saveCarDataToList($boxId, $range, $time, self::P_CAR_RANGE__BOXID_);
    }

    /**
     * 缓存车辆行驶里程
     * @param $boxId
     * @param $mileage
     * @param $time
     */
    private function cacheMileage($boxId, $mileage, $time)
    {
        $this->saveCarDataToList($boxId, $mileage, $time, self::P_CAR_MILEAGE__BOXID_);
    }

    /**
     * 缓存车辆 GPS
     * @param $boxId
     * @param $lat
     * @param $lng
     * @param $time
     */
    private function cacheGPS($boxId, $lat, $lng, $time)
    {
        // 粗略判断gps是否正确
        if ($lng < 0 || $lat < 0) {
            return;
        }
        $oldGpsArray = $this->getCarDataArrayFromList(self::P_CAR_GPS__BOXID_ . $boxId);

        if (isset($oldGpsArray[self::I_CAR_GPS__BOXID_LAT]) && $oldGpsArray[self::I_CAR_GPS__BOXID_LAT] == $lat
            && isset($oldGpsArray[self::I_CAR_GPS__BOXID_LNG]) && $oldGpsArray[self::I_CAR_GPS__BOXID_LNG] == $lng
        ) {
            $cmd = $this->redis->createCommand(self::CMD_LPOP, [self::P_CAR_GPS__BOXID_ . $boxId]);
            $this->redis->executeCommand($cmd);
        }

        $cmd = $this->redis->createCommand(self::CMD_LPUSH, [self::P_CAR_GPS__BOXID_ . $boxId, $lat . self::SEPARATOR . $lng . self::SEPARATOR . $time]);
        $this->redis->executeCommand($cmd);
    }

    private function saveCarDataToList($boxId, $data, $time, $halfKey)
    {
        if ($data < 0)
            return;
        if ($data == $this->getCarDataArrayFromList($halfKey . $boxId, self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA)) {
            $cmd = $this->redis->createCommand(self::CMD_LPOP, [$halfKey . $boxId]);
            $this->redis->executeCommand($cmd);
        }
        $cmd = $this->redis->createCommand(self::CMD_LPUSH, [$halfKey . $boxId, $data . self::SEPARATOR . $time]);
        $this->redis->executeCommand($cmd);
    }

    private function cacheSpeed($boxId, $speed)
    {
        $cmd = $this->redis->createCommand(self::CMD_HSET, [self::H_CAR_SPEED__BOXID, $boxId, $speed]);
        $this->redis->executeCommand($cmd);
    }

    private function cacheOnline($boxId, $status)
    {
        $cmd = $this->redis->createCommand(self::CMD_HSET, [self::H_CAR_STATUS__BOXID, $boxId, $status]);
        $this->redis->executeCommand($cmd);
    }


    public function getCarRangeById($carId)
    {
        $boxId = $this->getCarBoxIdByID($carId);
        return $this->getCarDataArrayFromList(self::P_CAR_RANGE__BOXID_ . $boxId, 0);
    }

    public function getCarPowerById($carId)
    {
        $boxId = $this->getCarBoxIdByID($carId);
        return $this->getCarDataArrayFromList(self::P_CAR_POWER__BOXID_ . $boxId, 0);
    }

    public function getCarMileageById($carId)
    {
        $boxId = $this->getCarBoxIdByID($carId);
        return $this->getCarDataArrayFromList(self::P_CAR_MILEAGE__BOXID_ . $boxId, 0);
    }

    /**
     * @param $boxId
     * @param int $count
     * @return array|bool|string :
     *      count<1 return string 11 (range)
     *      count=1 return array{id=>boxid,range=>11,time=11}
     *      count>1 return array[{id=>boxid,range=>11,time=11},{id=>boxid,range=>11,time=10},...]
     */
    public function getCarRangeByBoxId($boxId, $count = 1)
    {
        if ($count > 0) {
            $data = $this->getCarDataArrayFromList(self::P_CAR_RANGE__BOXID_ . $boxId, $count);
            $re = false;
            if ($count == 1) {
                if (isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                    $re = [
                        'id' => $boxId,
                        'range' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                        'time' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                    ];
                }
            } elseif ($count > 1 && is_array($data)) {
                if (count($data) == count($data, 1)) {
                    if (isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                        $re[] = [
                            'id' => $boxId,
                            'range' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                            'time' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                        ];
                    }
                } else {
                    foreach ($data as $item) {
                        if (isset($item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                            $re[] = [
                                'id' => $boxId,
                                'range' => $item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                                'time' => $item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                            ];
                        }
                    }
                }
            }
            return $re;
        } else {
            return $this->getCarDataStrFromList(self::P_CAR_RANGE__BOXID_ . $boxId);
        }
    }

    /**
     * @param $boxId
     * @param int $count
     * @return array|bool|string :
     *      count<1 return string 11 (power)
     *      count=1 return array{id=>boxid,power=>11,time=11}
     *      count>1 return array[{id=>boxid,power=>11,time=11},{id=>boxid,power=>11,time=10},...]
     */
    public function getCarPowerByBoxId($boxId, $count = 1)
    {
        if ($count > 0) {
            $data = $this->getCarDataArrayFromList(self::P_CAR_POWER__BOXID_ . $boxId, $count);
            $re = false;
            if ($count == 1) {
                if (isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                    $re = [
                        'id' => $boxId,
                        'power' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                        'time' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                    ];
                }
            } elseif ($count > 1 && is_array($data)) {
                if (count($data) == count($data, 1)) {
                    if (isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                        $re[] = [
                            'id' => $boxId,
                            'power' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                            'time' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                        ];
                    }
                } else {
                    foreach ($data as $item) {
                        if (isset($item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                            $re[] = [
                                'id' => $boxId,
                                'power' => $item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                                'time' => $item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                            ];
                        }
                    }
                }
            }
            return $re;
        } else {
            return $this->getCarDataStrFromList(self::P_CAR_POWER__BOXID_ . $boxId);
        }

    }

    /**
     * @param $boxId
     * @param int $count
     * @return array|bool|string :
     *      count<1 return string 11 (mileage)
     *      count=1 return array{id=>boxid,mileage=>11,time=11}
     *      count>1 return array[{id=>boxid,mileage=>11,time=11},{id=>boxid,mileage=>11,time=10},...]
     */
    public function getCarMileageByBoxId($boxId, $count = 1)
    {
        if ($count > 0) {
            $data = $this->getCarDataArrayFromList(self::P_CAR_MILEAGE__BOXID_ . $boxId, $count);
            $re = false;
            if ($count == 1) {
                if (isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                    $re = [
                        'id' => $boxId,
                        'mileage' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                        'time' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                    ];
                }
            } elseif ($count > 1 && is_array($data)) {
                if (count($data) == count($data, 1)) {
                    if (isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                        $re[] = [
                            'id' => $boxId,
                            'mileage' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                            'time' => $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                        ];
                    }
                } else {
                    foreach ($data as $item) {
                        if (isset($item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME])) {
                            $re[] = [
                                'id' => $boxId,
                                'mileage' => $item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA],
                                'time' => $item[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME]
                            ];
                        }
                    }
                }

            }
            return $re;
        } else {
            return $this->getCarDataStrFromList(self::P_CAR_MILEAGE__BOXID_ . $boxId);
        }
    }

    public function getCarGpsById($carId, $count = 1)
    {
        $boxId = $this->getCarBoxIdByID($carId);
        return $this->getCarGpsByBoxId($boxId, $count);
    }

    /**
     * @param $boxId
     * @param int $count
     * @return array|bool :
     *      count<1 return array [lat,lng,time]
     *      count=1 return array{id=>boxid,lat=>11,lng=>11,time=11}
     *      count>1 return array[{id=>boxid,lat=>11,lng=>11,time=11},{id=>boxid,lat=>11,lng=>11,time=10},...]
     */
    public function getCarGpsByBoxId($boxId, $count = 1)
    {
        if ($count > 0) {
            $data = $this->getCarDataArrayFromList(self::P_CAR_GPS__BOXID_ . $boxId, $count);
            $re = false;
            if ($count == 1) {
                if (isset($data[self::I_CAR_GPS__BOXID_TIME])) {
                    $re = [
                        'id' => $boxId,
                        'lat' => $data[self::I_CAR_GPS__BOXID_LAT],
                        'lng' => $data[self::I_CAR_GPS__BOXID_LNG],
                        'time' => $data[self::I_CAR_GPS__BOXID_TIME]
                    ];
                }
            } elseif ($count > 1 && is_array($data)) {
                if (count($data) == count($data, 1)) {
                    if (isset($data[self::I_CAR_GPS__BOXID_TIME])) {
                        $re[] = [
                            'id' => $boxId,
                            'lat' => $data[self::I_CAR_GPS__BOXID_LAT],
                            'lng' => $data[self::I_CAR_GPS__BOXID_LNG],
                            'time' => $data[self::I_CAR_GPS__BOXID_TIME]
                        ];
                    }
                } else {
                    foreach ($data as $item) {
                        if (isset($item[self::I_CAR_GPS__BOXID_TIME])) {
                            $re[] = [
                                'id' => $boxId,
                                'lat' => $item[self::I_CAR_GPS__BOXID_LAT],
                                'lng' => $item[self::I_CAR_GPS__BOXID_LNG],
                                'time' => $item[self::I_CAR_GPS__BOXID_TIME]
                            ];
                        }
                    }
                }
            }
            return $re;
        } else {
            return $this->getCarDataArrayFromList(self::P_CAR_GPS__BOXID_ . $boxId);
        }
    }


    /**
     * @param $key string redis list key
     * @param $count integer
     * @return bool|array : count=1 {id=>sn,power|range|mileage=>11,time=>timestamp}
     * , count>1 [{id=>sn,power|range|mileage=>11,time=>timestamp},{id=>sn,power|range|mileage=>11,time=>timestamp},...]
     */
    private function getCarDataArrayFromList($key, $count = 1)
    {
        if (!is_string($key))
            return false;
        $cmd = $this->redis->createCommand(self::CMD_LRANGE, [$key, 0, $count - 1]);
        $re = $this->redis->executeCommand($cmd);
        if ($re) {
            if (count($re) == 1) {
                $data = explode(self::SEPARATOR, $re[0]);
                return $data;
            } elseif (count($re) > 1) {
                $data = [];
                foreach ($re as $item) {
                    $data[] = explode(self::SEPARATOR, $item);
                }
                return $data;
            }

        }
        return false;
    }

    /**
     * @param $key string redis list key
     * @return bool|string|array
     */
    private function getCarDataStrFromList($key)
    {
        if (!is_string($key))
            return false;
        $cmd = $this->redis->createCommand(self::CMD_LINDEX, [$key, 0]);
        $re = $this->redis->executeCommand($cmd);
        if ($re) {
            $data = explode(self::SEPARATOR, $re);
            return isset($data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA]) ? $data[self::I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA] : false;
        }
        return false;
    }


    /**
     * 根据车辆id获取车牌
     *
     * @param $carId
     * @return null
     */
    public function getCarPlatesByID($carId)
    {
        $this->cachePartnerVisibleCars();

        $redis_cmd = $this->redis->createCommand(self::CMD_HGET, array(self::H_CAR_BASE_ID_PLATE, $carId));
        $carInfo = $this->redis->executeCommand($redis_cmd);

        $carInfos = explode(self::SEPARATOR, $carInfo);

        if (isset($carInfos[self::I_CAR_BASE_ID_PLATE_PLATE])) {
            return $carInfos[self::I_CAR_BASE_ID_PLATE_PLATE];
        }

        return null;
    }

    /**
     * 根据车辆id获取车牌
     *
     * @param $carId
     * @return null
     */
    public function getCarBoxIdByID($carId)
    {
        $this->cachePartnerVisibleCars();

        $redis_cmd = $this->redis->createCommand(self::CMD_HGET, array(self::H_CAR_BASE_ID_PLATE, $carId));
        $carInfo = $this->redis->executeCommand($redis_cmd);

        $carInfos = explode(self::SEPARATOR, $carInfo);

        if (isset($carInfos[self::I_CAR_BASE_ID_PLATE_BOXID])) {
            return $carInfos[self::I_CAR_BASE_ID_PLATE_BOXID];
        }

        return null;
    }

    /**
     * @param array $ids
     * @return null
     */
    public
    function getCarPlatesByIDs($ids)
    {
        $this->cachePartnerVisibleCars();

        $redis_cmd = $this->redis->createCommand(self::CMD_HMGET, array(self::H_CAR_BASE_ID_PLATE, $ids));
        $carInfos = $this->redis->executeCommand($redis_cmd);

        $carIDPlate = [];
        foreach ($carInfos as $key => $carInfo) {
            $carInfo = explode(self::SEPARATOR, $carInfo);
            $carIDPlate[$carInfo[0]] = isset($carInfo[1]) ? $carInfo[1] : '';
        }

        return $carIDPlate;
    }

    /**
     * 根据车牌获取车辆id
     *
     * @param $car_nos
     * @return mixed
     */
    public
    function getCarIDsByPlates($car_nos)
    {
        $this->cachePartnerVisibleCars();

        $redis_cmd = $this->redis->createCommand(self::CMD_HMGET, array(self::H_CAR_BASE_PLATE_ID, $car_nos));
        $car_ids = $this->redis->executeCommand($redis_cmd);

        return $car_ids;
    }

    /**
     * 缓存第三方租赁点
     * @param $partnerCode : partner code
     * @param bool $force : 是否强制刷新
     */
    public
    function cachePartnerStations($partnerCode, $force = false)
    {
        if (!$force && $this->exist(self::H_STATION_ID_TO_PARTNER_STATION_ID)) {
            return;
        }

        $query = $this->em->createQuery(
            'SELECT s.id station_id, ps.partnerStation partner_station_id,p.id partner_id, p.code partner_code
                FROM AutoManagerBundle:PartnerStation ps
                LEFT JOIN AutoManagerBundle:Partner p
                  WITH ps.partner=p.id
                LEFT JOIN AutoManagerBundle:Station s
                  WITH s.id=ps.station
                WHERE p.code = :code'
        )->setParameter('code', $partnerCode);
        $stations = $query->getResult();

        $redis_cmd = $this->redis->createCommand(self::CMD_DEL, [self::H_STATION_ID_TO_PARTNER_STATION_ID]);
        $this->redis->executeCommand($redis_cmd);

        foreach ($stations as $station) {

            $fields = $station['partner_station_id'] . self::SEPARATOR . $station['partner_id']
                . self::SEPARATOR . $station['partner_code'] . self::SEPARATOR . $station['station_id'];

            $redis_cmd = $this->redis->createCommand(self::CMD_HSET, array(self::H_STATION_ID_TO_PARTNER_STATION_ID,
                $station['station_id'], $fields));
            $this->redis->executeCommand($redis_cmd);

            $redis_cmd = $this->redis->createCommand(self::CMD_HSET, array(self::H_PARTNER_STATION_ID_TO_STATION_ID_ . $partnerCode,
                $station['partner_station_id'], $station['station_id']));
            $this->redis->executeCommand($redis_cmd);
        }
    }

    /**
     * 根据station id 获取 partner station id
     *
     * @param $stationId
     * @param $partnerCode
     * @return integer
     */
    public
    function getPartnerStationIdByStationIdFromCache($stationId, $partnerCode)
    {
        $redis_cmd = $this->redis->createCommand(self::CMD_HSET, array('station_id_to_partner_station_id', $stationId));
        $stationInfo = $this->redis->executeCommand($redis_cmd);
        if ($stationInfo) {
            $info = explode(self::SEPARATOR, $stationInfo);
            if (isset($info[2]) && $info[2] == $partnerCode) {
                return $info[0];
            }
        }
        return $stationId;
    }

    /**
     * 根据partner station id 和 partner code 获取 station id
     *
     * @param $partnerCode
     * @param $partnerStationId
     * @return integer
     */
    public
    function getStationIdByPartnerStationIdFromCache($partnerStationId, $partnerCode)
    {
        $redis_cmd = $this->redis->createCommand(self::CMD_HGET, array('partner_station_id_to_station_id_' . $partnerCode, $partnerStationId));
        $stationInfo = $this->redis->executeCommand($redis_cmd);
        return empty($stationInfo) ? $partnerStationId : $stationInfo;
    }

    /**
     * 缓存车牌与车辆id对应关系
     *
     * @param bool $force
     * @param array $cars
     * @return bool
     */
    public
    function cacheCarPlate($force = false, $cars = null)
    {
        if (!$force && $this->exist(self::H_CAR_BASE_ID_PLATE)) {
            return true;
        }

        if ($cars == null) {
            $cars = $this->getCarBaseInfoFromDB();
        }

        $bingo = false;
        foreach ($cars as $car) {
            $redis_cmd = $this->redis->createCommand(self::CMD_HSET, [
                self::H_CAR_BASE_ID_PLATE, $car['id'],
                $car['id'] . self::SEPARATOR . $car['plate'] . self::SEPARATOR . $car['boxId']
            ]);
            $this->redis->executeCommand($redis_cmd);
            $redis_cmd = $this->redis->createCommand(self::CMD_HSET, array(self::H_CAR_BASE_PLATE_ID, $car['plate'], $car['id']));
            $this->redis->executeCommand($redis_cmd);
            if (!$bingo)
                $bingo = true;
        }
        if ($bingo) {
            $redis_cmd = $this->redis->createCommand(self::CMD_EXPIRE, array(self::H_CAR_BASE_ID_PLATE, self::EXPIRE_LEVEL_WEEK));
            $this->redis->executeCommand($redis_cmd);
        }
    }

    private
    function getCarBaseInfoFromDB()
    {
        $query = $this->em->createQuery(
            'SELECT car.id, CONCAT(lp.name,car.licensePlate) plate, car.boxId
                FROM AutoManagerBundle:RentalCar car
                LEFT JOIN AutoManagerBundle:LicensePlace lp
                  WITH car.licensePlace=lp.id'
        );
        $cars = $query->getResult();
        return $cars;
    }

    /**
     * 缓存第三方可用车辆
     *
     * @param bool $force
     * @return array|bool
     */
    public
    function cachePartnerVisibleCars($force = false)
    {
        $this->cacheCarPlate();

        $redis = $this->redis;

        if (!$force) {
            $redis_cmd = $redis->createCommand('keys', array(self::M_PARTNER_VISIBLE_CARS_ . '*'));
            $re = $redis->executeCommand($redis_cmd);
            if (!empty($re)) {
                return true;
            }
        }

        $query = $this->em->createQuery(
            'SELECT p.operateLimit,p.code, p.visibleCars, p.operatorIds, m.id member_id
                FROM AutoManagerBundle:Partner p
                LEFT JOIN AutoManagerBundle:Member m
                  WITH p.member=m.id
                WHERE p.visibleCars IS NOT NULL '
        );
        $partners = $query->getResult();

        foreach ($partners as $partner) {
            $member_id = $partner['member_id'];
            $app_key = $partner['code'];
            $cars = explode(',', $partner['visibleCars']);
            $operate_limit = $partner['operateLimit'] > 0 ? 3600 / $partner['operateLimit'] : 0;
            $operator_ids = explode(',', $partner['operatorIds']);

            $redis_cmd = $redis->createCommand(self::CMD_SET, array(self::S_PARTNER_CODE_MEMBER_ID_ . $app_key, $member_id));
            $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand(self::CMD_SET, array(self::S_PARTNER_OPERATOR_LIMIT_ . $app_key, $operate_limit));
            $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand(self::CMD_SADD, array(self::M_PARTNER_OPERATORS_ . $app_key, $operator_ids));
            $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand(self::CMD_SADD, array(self::M_PARTNER_VISIBLE_CARS_ . $app_key, $cars));
            $redis->executeCommand($redis_cmd);

            $redis_cmd = $redis->createCommand(self::CMD_EXPIRE, array(self::S_PARTNER_CODE_MEMBER_ID_ . $app_key, self::EXPIRE_LEVEL_WEEK));
            $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand(self::CMD_EXPIRE, array(self::S_PARTNER_OPERATOR_LIMIT_ . $app_key, self::EXPIRE_LEVEL_WEEK));
            $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand(self::CMD_EXPIRE, array(self::M_PARTNER_OPERATORS_ . $app_key, self::EXPIRE_LEVEL_WEEK));
            $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand(self::CMD_EXPIRE, array(self::M_PARTNER_VISIBLE_CARS_ . $app_key, self::EXPIRE_LEVEL_WEEK));
            $redis->executeCommand($redis_cmd);
            $this->cachePartnerStations($app_key);
        }

    }
}