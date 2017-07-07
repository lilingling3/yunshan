<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/11
 * Time: 下午4:44
 */

namespace Auto\Bundle\ManagerBundle\Helper;


use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Predis\Client as Redis;


abstract class AbstractHelper
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    protected $templating;
    protected $container;
    protected $router;

    protected $redis;

    public function __construct(Registry $doctrine, EngineInterface $templating, Container $container,
                                UrlGeneratorInterface $router, Redis $redis)
    {
        $this->setEntityManager($doctrine->getManager());
        $this->templating = $templating;
        $this->container = $container;
        $this->router = $router;
        $this->redis = $redis;

    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }

    const DEVICE_COMPANY_ZHI_XIN_TONG = 'zhiXinTong';

    const CMD_DEL = 'DEL';
    const CMD_SET = 'SET';
    const CMD_GET = 'GET';
    const CMD_HSET = 'HSET';
    const CMD_HGET = 'HGET';
    const CMD_HMGET = 'HMGET';
    const CMD_HGETALL = 'HGETALL';
    const CMD_SADD = 'SADD';
    const CMD_LPUSH = 'LPUSH';
    const CMD_LRANGE = 'LRANGE';
    const CMD_LPOP = 'LPOP';
    const CMD_LPUSHX = 'LPUSHX';
    const CMD_LINDEX = 'LINDEX';
    const CMD_SMEMBERS = 'SMEMBERS';
    const CMD_EXPIRE = 'EXPIRE';

    const SEPARATOR = '|';
    const EXPIRE_LEVEL_NEVER = 1000000000;
    const EXPIRE_LEVEL_DAY = 86400;
    const EXPIRE_LEVEL_WEEK = 604800;
    const EXPIRE_LEVEL_MONTH = 2592000;


    const S_PARTNER_CODE_MEMBER_ID_ = 'partner_member_id_';
    const S_PARTNER_OPERATOR_LIMIT_ = 'partner_operate_limit_';

    const H_STATION_BASE = 'h_station_base';
    const H_CAR_BASE = 'h_car_base';

    const H_CAR_BASE_ID_PLATE = 'car_base_info_id_plate';//id => id|plate|boxId
    const I_CAR_BASE_ID_PLATE_ID = 0;
    const I_CAR_BASE_ID_PLATE_PLATE = 1;
    const I_CAR_BASE_ID_PLATE_BOXID = 2;

    const H_CAR_BASE_PLATE_ID = 'car_base_info_plate_id';//plate => id
    const H_STATION_ID_TO_PARTNER_STATION_ID = 'station_id_to_partner_station_id';//
    const H_PARTNER_STATION_ID_TO_STATION_ID_ = 'partner_station_id_to_station_id_';//


    const M_PARTNER_VISIBLE_CARS_ = 'partner_visible_cars_';//'m_partner_visible_cars_';
    const M_PARTNER_OPERATORS_ = 'partner_operate_ids_';//'m_partner_operate_ids_';
    const H = 1;

    const P_CAR_POWER__BOXID_ = 'p_car_power_';// sn => power|time
    const P_CAR_RANGE__BOXID_ = 'p_car_range_';// sn => range|time
    const P_CAR_MILEAGE__BOXID_ = 'p_car_mileage_';// sn => mileage|time
    const I_CAR_POWER_RANGE_MILEAGE__BOXID_DATA = 0;// sn => power|time
    const I_CAR_POWER_RANGE_MILEAGE__BOXID_TIME = 1;// sn => power|time

    const P_CAR_GPS__BOXID_ = 'p_car_gps_';// sn => lat|lng|time
    const I_CAR_GPS__BOXID_LAT = 0;// sn => lat|lng|time
    const I_CAR_GPS__BOXID_LNG = 1;// sn => lat|lng|time
    const I_CAR_GPS__BOXID_TIME = 2;// sn => lat|lng|time

    const H_CAR_SPEED__BOXID = 'h_car_speed';
    const H_CAR_STATUS__BOXID = 'h_car_status';

    /**
     * @param string $key
     * @return mixed
     */
    public function exist($key)
    {
        if (gettype($key) != 'string') {
            return false;
        }
        $redis_cmd = $this->redis->createCommand('EXISTS', [$key]);
        $re = $this->redis->executeCommand($redis_cmd);
        return $re;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function delKey($key)
    {
        if (gettype($key) != 'string') {
            return false;
        }
        $redis_cmd = $this->redis->createCommand('DEL', [$key]);
        $re = $this->redis->executeCommand($redis_cmd);
        return $re;
    }

    /**
     * @param $key
     * @param $field
     * @param $array
     * @param $deleteOld
     * @param int $expire
     * @return bool|mixed
     */
    public function hSetJson($key, $field, $array, $deleteOld = false, $expire = self::EXPIRE_LEVEL_NEVER)
    {
        if (!is_array($array))
            return false;

        $hSetArray = [$key];

        foreach ($array as $value) {
            if (!is_array($value) || !isset($value[$field]))
                return false;
            $hSetArray[] = $value[$field];
            $hSetArray[] = json_encode($value);
        }

        if ($deleteOld)
            $this->delKey($key);

        $redis_cmd = $this->redis->createCommand('HMSET', $hSetArray);
        $re = $this->redis->executeCommand($redis_cmd);

        if ($expire > 0) {
            $redis_cmd = $this->redis->createCommand('EXPIRE', [$key, $expire]);
            $this->redis->executeCommand($redis_cmd);
        }

        return $re;
    }

    /**
     * @param $key
     * @param $fields
     * @return bool|mixed
     */
    public function hGetJson($key, $fields)
    {
        if (empty($key))
            return false;

        if (is_array($fields)) {
            $cmdArray = array_merge([$key], $fields);
            $redis_cmd = $this->redis->createCommand('HMGET', $cmdArray);
            $re = $this->redis->executeCommand($redis_cmd);
        } else {
            $redis_cmd = $this->redis->createCommand('HGETALL', [$key]);
            $re = $this->redis->executeCommand($redis_cmd);
        }

        return $re;
    }
}