<?php
/**
 * Created by PhpStorm.
 * User: Ma
 * Date: 17/4/26
 * Time: 上午11:51
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class BaoJiaCarStatusCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('auto:bao:jia:carstatus')
            ->setDescription('push small car location and status to redis');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        swoole_timer_tick(10000, function ($timer_id) {
            $this->saveCarDataToRedis();
        });
    }

    /**
     * 将车辆的位置信息 状态信息 存入redis
     */
    public function saveCarDataToRedis()
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $rentalCars =
            $qb
                ->select('c')
                ->join('c.deviceCompany ', 'p')
                ->where($qb->expr()->eq('p.englishName', ':name'))
                ->setParameter('name', 'baoJia')
                ->getQuery()
                ->getResult();

        foreach ($rentalCars as $rentalCar) {
            if (!$rentalCar->getBoxId()) continue;
            echo $rentalCar->getLicense() . PHP_EOL;
            $this->setCarLocation($rentalCar->getBoxId(), $rentalCar->getLicensePlace()->getName() . $rentalCar->getLicensePlate());
        }
    }

    /**
     * 微租车向redis存入车辆位置信息
     */
    public function setCarLocation($deviceNum, $licensePlate)
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('hset', array("baoJia-car-deviceNum-plate", $deviceNum, $licensePlate));
        $redis->executeCommand($redis_cmd);

        $car_location_data = $this->getContainer()->get('auto_manager.bao_jia_helper')->findCarLocation($deviceNum);

        if (!$this->checkData($car_location_data)) {
            return;
        }

        echo "坐标" . $car_location_data['longitude'] . ',' . $car_location_data['latitude'] . PHP_EOL;
        echo "总里程" . $car_location_data['distance'] . '电量' . $car_location_data['surplusPercent'] . '续航' . $car_location_data['surplusDistance'] . PHP_EOL;

        if ($car_location_data) {
            $car_location_data['longitude'] /= 1000000.0;
            $car_location_data['latitude'] /= 1000000.0;
            // $gps_data = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt($car_location_data['longitude'],$car_location_data['latitude']);
            //$gps_data=[$car_location_data['longitude']+0.005386,$car_location_data['latitude']-0.002567];

            $gps_encrypt = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt($car_location_data['latitude'], $car_location_data['longitude']);

            $gps_data = [$gps_encrypt['lon'], $gps_encrypt['lat']];

            $respondTime = $car_location_data['time'];

            // 保存有效gps定位值
            if ($gps_encrypt['lon'] < 0 && $gps_encrypt['lat'] < 0) {
                $redis_cmd_gps = $redis->createCommand('hget', array("baoJia-car-curlocation", $deviceNum));
                $historyData = $redis->executeCommand($redis_cmd_gps);

                if ($historyData) {

                    $history = json_decode($historyData);

                    $gps_encrypt['lon'] = $history[0];
                    $gps_encrypt['lat'] = $history[1];
                    $respondTime = isset($history[3]) ? $history[3] : $car_location_data['time'];

                } else {
                    $gps_encrypt['lon'] = 0;
                    $gps_encrypt['lat'] = 0;
                    $respondTime = $car_location_data['time'];
                }
            }

            //更新当前车的位置
            $redis_cmd_gps = $redis->createCommand('hset', array("baoJia-car-curlocation", $deviceNum,
                json_encode([
                    $gps_encrypt['lon'],
                    $gps_encrypt['lat'],
                    $licensePlate,
                    $respondTime,
                ])));
            $redis->executeCommand($redis_cmd_gps);


//            $new_data = (new \DateTime($car_location_data['time']))->getTimestamp();
            $contrast_time = (time() - $car_location_data['time'] / 100) / 1000;
            if ($contrast_time < 20) {
                if ($car_location_data['distance'] >= 0) {
                    //总里程
                    $arr_total_mileage = ['id' => $deviceNum, 'mileage' => $car_location_data['distance'], 'time' => (new \DateTime())->getTimestamp()];
                    $redis_cmd_mileage = $redis->createCommand('lpush', array("baoJia-mileage-" . $deviceNum, json_encode($arr_total_mileage)));
                    $redis->executeCommand($redis_cmd_mileage);

                    $redis_cmd = $redis->createCommand('hset', array("baoJia-car-running-info-total-mileage", $deviceNum, $car_location_data['distance']));
                    $redis->executeCommand($redis_cmd);
                }

                if ($car_location_data['surplusPercent'] >= 0) {
                    //电量
                    $arr_power = ['id' => $deviceNum, 'power' => $car_location_data['surplusPercent'], 'time' => (new \DateTime())->getTimestamp()];
                    $redis_cmd_power = $redis->createCommand('lpush', array("baoJia-power-" . $deviceNum, json_encode($arr_power)));
                    $redis->executeCommand($redis_cmd_power);

                    $redis_cmd = $redis->createCommand('hset', array("baoJia-car-running-info-power", $deviceNum, $car_location_data['surplusPercent']));
                    $redis->executeCommand($redis_cmd);
                }

                if ($car_location_data['surplusDistance'] >= 0) {
                    //续航里程
                    $arr_remain_mileage = ['id' => $deviceNum, 'range' => $car_location_data['surplusDistance'], 'time' => (new \DateTime())->getTimestamp()];
                    $redis_cmd_range = $redis->createCommand('lpush', array("baoJia-range-" . $deviceNum, json_encode($arr_remain_mileage)));
                    $redis->executeCommand($redis_cmd_range);

                    $redis_cmd = $redis->createCommand('hset', array("baoJia-car-running-info-remain-mileage", $deviceNum, $car_location_data['surplusDistance']));
                    $redis->executeCommand($redis_cmd);
                }
            }

            $status = 2;
            if (!isset($car_location_data['rtCode']) || $car_location_data['rtCode'] == '1' || $car_location_data['rtCode'] == '3') {
                $status = 1;
            }

            $redis_cmd_gps = $redis->createCommand('hset', array("baoJia-car-online-status", $deviceNum, $status));
            $redis->executeCommand($redis_cmd_gps);


            // 保存车速度状态
            $redis_cmd_gps = $redis->createCommand('hset', array("baoJia-car-speed-status", $deviceNum, $car_location_data['speed']));
            $redis->executeCommand($redis_cmd_gps);

            // 保存历史轨迹数据
            $arr_gps = ['id' => $deviceNum, 'coordinate' => $gps_data, 'time' => (new \DateTime())->getTimestamp()];
//            $new_data = (new \DateTime($car_location_data['time']))->getTimestamp();

            $contrast_time = (time() - $car_location_data['time'] / 100) / 1000;
            if ($contrast_time < 20) {
                $redis_cmd_gps = $redis->createCommand('lpush', array("baoJia-gps-" . $deviceNum, json_encode($arr_gps)));
                $redis->executeCommand($redis_cmd_gps);
            }
        }
    }

    private function checkData($car_location_data)
    {
        $bingo = true;

        $errorMessage = '未获取到 ：';
        if (isset($car_location_data['longitude'])) {
            $errorMessage .= 'longitude ';
        }
        if (isset($car_location_data['latitude'])) {
            $errorMessage .= 'latitude ';
        }
        if (isset($car_location_data['distance'])) {
            $errorMessage .= '总里程 ';
        }
        if (isset($car_location_data['surplusPercent'])) {
            $errorMessage .= '剩余电量 ';
        }
        if (isset($car_location_data['surplusDistance'])) {
            $errorMessage .= '续航里程 ';
        }
        if (isset($car_location_data['time'])) {
            $errorMessage .= '时间 ';
        }
        if (isset($car_location_data['speed'])) {
            $errorMessage .= '速度 ';
        }
        if (isset($car_location_data['rtCode'])) {
            $errorMessage .= '车辆状态 ';
        }

        if (!$bingo) {
            echo $errorMessage . PHP_EOL;
        }

        return $bingo;
    }
} 