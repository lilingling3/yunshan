<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/5/24
 * Time: 上午11:51
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tinify\Exception;


class RentalCarStatusCommand extends ContainerAwareCommand
{

    const CAR_ENGLISH_NAME_ZHI_XIN_TONG = 'zhiXinTong';
    const CAR_ENGLISH_NAME_BAO_JIA = 'baoJia';
    const CAR_ENGLISH_NAME_FEE_ZU = 'feeZu';
    const CAR_ENGLISH_NAME_YUN_SHAN_ZHI_HUI = 'yunshanZhihui';

    public function configure()
    {
        $this
            ->setName('auto:rental:car:rental:carstatus')
            ->setDescription('push car on or off line staus and rental status')
            ->addOption('param', 'p', InputOption::VALUE_REQUIRED, 'param');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $param = $input->getOption('param');

        if ($param > 1) {
            $this->testConnect();
            return;
        }

        swoole_timer_tick(5000, function ($timer_id) {
            $timeStart = time();
            echo '[' . date('Y/m/d H:i:s', $timeStart) . ']Begin to run...' . PHP_EOL;
            try {
                $this->saveCarDataToRedis();
            } catch (Exception $exception) {
                file_put_contents('/data/logs/debug.log'
                    , '[' . date('Y/m/d H:i:s', $timeStart) . '] auto:rental:car:rental:carstatus have error : ' . $exception->getMessage() . PHP_EOL
                    , FILE_APPEND);
                //@todo send message ?
            }
            $timeEnd = time();
            echo '[' . date('Y/m/d H:i:s', $timeEnd) . ']Save cache end, used time :' . ($timeEnd - $timeStart) . 's' . PHP_EOL;

            $redis = $this->getContainer()->get('snc_redis.default');
            $redis_cmd = $redis->createCommand('LPUSH', array("cache_car_time_log", date('Y/m/d H:i:s', $timeStart) . '--' . date('Y/m/d H:i:s', $timeEnd)));
            $redis->executeCommand($redis_cmd);
        });
    }

// example sn : 116502100005073

//feeZu-power-sn                            {"id":"sn","power":70,"time":1497949708}
//feeZu-range-sn                            {"id":"sn","range":70,"time":1497949708}
//feeZu-mileage-sn                          {"id":"sn","mileage":70,"time":1497949708}
//feeZu-gps-sn                              {"id":"sn","coordinate":[117.3300190207,31.736574228205],"time":1497949517}

//feeZu-car-running-info-power              sn => 89
//feeZu-car-running-info-remain-mileage     sn => 111
//feeZu-car-running-info-total-mileage      sn => 188
//feeZu-car-curlocation                     sn => [113.36870534177,23.036337880262,"\\u7ca4AE57W9","20170621084007"]

//feeZu-car-speed-status                    sn => "72.3"
//feeZu-car-deviceNum-plate                 sn => \xe7\x9a\x96A4B414
//feeZu-car-online-status                   sn => 1// 离线， sn => 2// 在线静止, sn => 3//在线运行
    private function testConnect()
    {
//        $car_status_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->multiCarStatus();
//        $car_status_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->oneCarStatus('116232100003758');
//        echo json_encode($car_status_data) . PHP_EOL;
//        $car_location_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->findCarsLocation();
        $boxIds = ['116502100006489',
            '250006064|093990',
            '028000003883',
            '116502100006778',
            '116232100004647',
            '116502100005225',];
        $count = -1;
        foreach ($boxIds as $boxId) {
            $data = $this->getContainer()->get('auto_manager.cache_helper')->getCarGpsByBoxId($boxId, $count);
            echo 'Count:' . count($data) . '   ';
            echo json_encode($data) . PHP_EOL;
//            $data = $this->getContainer()->get('auto_manager.cache_helper')->getCarMileageByBoxId($boxId, $count);
//            echo 'Count:' . count($data) . '   ';
//            echo json_encode($data) . PHP_EOL;
//            $data = $this->getContainer()->get('auto_manager.cache_helper')->getCarPowerByBoxId($boxId, $count);
//            echo 'Count:' . count($data) . '   ';
//            echo json_encode($data) . PHP_EOL;
//            $data = $this->getContainer()->get('auto_manager.cache_helper')->getCarRangeByBoxId($boxId, $count);
//            echo 'Count:' . count($data) . '   ';
//            echo json_encode($data) . PHP_EOL;
//            break;
        }

    }

    /**
     * 将车辆的位置信息 状态信息 存入redis
     */
    private function saveCarDataToRedis()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $query = $em->createQuery(
            'SELECT rc.id, CONCAT(lp.name,rc.licensePlate) plate,rc.boxId,cp.englishName
                    FROM AutoManagerBundle:RentalCar rc
                    LEFT JOIN AutoManagerBundle:Company cp
                      WITH cp.id=rc.deviceCompany
                    LEFT JOIN AutoManagerBundle:LicensePlace lp
                      WITH lp.id=rc.licensePlace'
        );
        $cars = $query->getResult();
        $this->saveCache($cars);

        // 缓存车辆基本信息
        $this->getContainer()->get('auto_manager.cache_helper')->cacheCarPlate(true, $cars);
    }

    /**
     * @param $cars
     */
    private function saveCache($cars)
    {
        $carsZhiXinTong = array();
        $carsBaoJia = array();
        $carsFeeZu = array();
        $carsYunShan = array();
        foreach ($cars as $car) {
            switch ($car['englishName']) {
                case self::CAR_ENGLISH_NAME_ZHI_XIN_TONG:
                    $carsZhiXinTong[] = $car;
                    break;
                case self::CAR_ENGLISH_NAME_BAO_JIA:
                    $carsBaoJia[] = $car;
                    break;
                case self::CAR_ENGLISH_NAME_FEE_ZU:
                    $carsFeeZu[] = $car;
                    break;
                case self::CAR_ENGLISH_NAME_YUN_SHAN_ZHI_HUI:
                    $carsYunShan[] = $car;
                    break;
                default:
                    echo '车牌：' . $car['plate'] . '，设备ID：' . $car['boxId'] . '，设备公司：' . $car['englishName'] . '，车辆公司未统计' . PHP_EOL;
            }
        }

        $cacheTime = 0;
        if (count($carsZhiXinTong) > 0) {
            $starTime = $this->microTime();
            $cacheTime += $this->saveCacheZhiXinTong($carsZhiXinTong);
            echo 'ZhiXinTong save cache over, time used :' . ($this->microTime() - $starTime) . 'ms' . PHP_EOL;
        }
        if (count($carsBaoJia) > 0) {
            $starTime = $this->microTime();
            $cacheTime += $this->saveCacheBaoJia($carsBaoJia);
            echo 'BaoJia save cache over, time used :' . ($this->microTime() - $starTime) . 'ms' . PHP_EOL;
        }
        if (count($carsYunShan) > 0) {
            $starTime = $this->microTime();
            $cacheTime += $this->saveCacheYunShan($carsBaoJia);
            echo 'YunShan save cache over, time used :' . ($this->microTime() - $starTime) . 'ms' . PHP_EOL;
        }
        $starTime = $this->microTime();
        $cacheTime += $this->saveCacheFeeZu();
        echo 'FeeZu save cache over, time used :' . ($this->microTime() - $starTime) . 'ms' . PHP_EOL;
        echo 'Cache data used time:' . $cacheTime . 'ms' . PHP_EOL;
    }

    private function saveCacheYunShan($cars)
    {
//        $car_location_data = $this->getContainer()->get('auto_manager.yunshan_zhihui_helper')->findCarLocation($licensePlate, $deviceNum);
//        $car_information_data = $this->getContainer()->get('auto_manager.yunshan_zhihui_helper')->findCarInformation($licensePlate, $deviceNum);
    }

    private function saveCacheZhiXinTong($cars)
    {
        $boxIds = [];
        $boxIdsCode = [];
        foreach ($cars as $car) {
            echo '车牌：' . $car['plate'] . '，设备ID：' . $car['boxId'] . '，设备公司：' . $car['englishName'] . PHP_EOL;
            $boxIds[] = $car['boxId'];
            $snCode = explode('|', $car['boxId']);
            if (isset($snCode[0])) {
                $boxIdsCode[$snCode[0]] = $car['boxId'];
            }
        }
        $re = $this->getContainer()->get('auto_manager.zhixin_tong_helper')->findCarLocation($boxIds);
        if (empty($re))
            return 0;
        $reArray = json_decode($re, true);
        if (!isset($reArray['cars'])) {
            return 0;
        }
        $carsData = $reArray['cars'];
        $carInfo = [];
        foreach ($carsData as $data) {
            $gps = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt($data['latitude'], $data['longitude']);
            $electricity = explode('/', $data['electricity']);
            $power = $electricity[0];
            $boxId = $data['sn'];
            if (isset($boxIdsCode[$boxId])) {
                $boxId = $boxIdsCode[$boxId];
            }
            $carInfo[] = [
                'boxId' => $boxId,
                'time' => $data['receivedTime'],
                'power' => $power,
                'range' => $data['mileage'],
                'mileage' => $data['totalMileage'],
                'status' => $data['on'],
                'speed' => $data['speed'],
            ];
            $carInfo[] = [
                'boxId' => $boxId,
                'time' => $data['gpsTime'],
                'lng' => $gps['lon'],
                'lat' => $gps['lat'],
            ];
        }

        $cacheStart = $this->microTime();
        $this->getContainer()->get('auto_manager.cache_helper')->cacheCarRunningInfo($carInfo);
        return $this->microTime() - $cacheStart;
    }

    private function saveCacheBaoJia($cars)
    {
        $carInfo = [];
        foreach ($cars as $car) {
            echo '车牌：' . $car['plate'] . '，设备ID：' . $car['boxId'] . '，设备公司：' . $car['englishName'] . PHP_EOL;
            $car_location_data = $this->getContainer()->get('auto_manager.bao_jia_helper')->findCarLocation($car['boxId']);
            if ($car_location_data) {
                $lat = isset($car_location_data['latitude']) ? substr($car_location_data['latitude'], 0, strlen($car_location_data['latitude']) - 6)
                    . '.' . substr($car_location_data['latitude'], -6) : '';
                $lng = isset($car_location_data['latitude']) ? substr($car_location_data['longitude'], 0, strlen($car_location_data['longitude']) - 6)
                    . '.' . substr($car_location_data['longitude'], -6) : '';
                $power = $car_location_data['surplusPercent'] * 100;
                $range = $car_location_data['surplusDistance'];
                $mileage = $car_location_data['distance'];
                $speed = $car_location_data['speed'] / 10;
                $status = 2;
                if (!isset($car_location_data['rtCode']) || $car_location_data['rtCode'] == '1' || $car_location_data['rtCode'] == '3') {
                    $status = 1;
                }
                $respondTime = $car_location_data['time'];
                $carInfo[] = [
                    'boxId' => $car['boxId'],
                    'time' => $respondTime,
                    'power' => $power,
                    'range' => $range,
                    'mileage' => $mileage,
                    'status' => $status,
                    'lng' => $lng,
                    'lat' => $lat,
                    'speed' => $speed,
                ];
            }
        }
        $cacheStart = $this->microTime();
        $this->getContainer()->get('auto_manager.cache_helper')->cacheCarRunningInfo($carInfo);
        return $this->microTime() - $cacheStart;
    }

    private function saveCacheFeeZu()
    {
        $car_status_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->multiCarStatus();
        $car_location_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->findCarsLocation();
        $carInfo = [];
        $outLog = 'FeeZu car : get ' . count($car_location_data) . ' car location data and ' . count($car_status_data) . ' car running data' . PHP_EOL;
        echo $outLog;
        if (!empty($car_location_data) && is_array($car_location_data)) {
            foreach ($car_location_data as $key => $location) {
                // gps 转换为高德坐标
                $gps = $this->getContainer()->get('auto_manager.gps_helper')
                    ->gcj_encrypt($location['latitude'], $location['longitude']);
                $carInfo[] = [
                    'boxId' => $location['deviceNum'],
                    'time' => strtotime($location['reportTime']),
                    'status' => $location['onlineStatus'],
                    'lng' => $gps['lon'],
                    'lat' => $gps['lat'],
                    'speed' => $location['speed'],
                ];
            }
        }

        if (!empty($car_status_data) && is_array($car_status_data)) {
            foreach ($car_status_data as $car_status_datum) {
                $carInfo[] = [
                    'boxId' => $car_status_datum['deviceNum'],
                    'time' => strtotime($car_status_datum['reportTime']),
                    'power' => $car_status_datum['power'],
                    'range' => $car_status_datum['remainMileage'],
                    'mileage' => $car_status_datum['totalMileage'],
                ];
            }
        }
        $cacheStart = $this->microTime();
        $this->getContainer()->get('auto_manager.cache_helper')->cacheCarRunningInfo($carInfo);
        return $this->microTime() - $cacheStart;
    }

    private function microTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (int)(($usec + $sec) * 1000);
    }
}