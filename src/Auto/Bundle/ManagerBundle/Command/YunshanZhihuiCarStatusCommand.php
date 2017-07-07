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


class YunshanZhihuiCarStatusCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('auto:yunshan:zhihui:carstatus')
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
                ->setParameter('name', 'yunshanZhihui')
                ->getQuery()
                ->getResult();

        foreach ($rentalCars as $rentalCar) {
            if (!$rentalCar->getBoxId()) continue;
            echo $rentalCar->getLicense() . PHP_EOL;
            $this->setCarLocation($rentalCar->getBoxId(), $rentalCar->getLicensePlace()->getName() . $rentalCar->getLicensePlate());
            $this->setCarData($rentalCar->getBoxId(),$rentalCar->getLicensePlace()->getName() . $rentalCar->getLicensePlate());
        }
    }

    /**
     * 云杉向redis存入车辆位置信息
     */
    public function setCarLocation($deviceNum, $licensePlate)
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('hset', array("yunshanZhihui-car-deviceNum-plate", $deviceNum, $licensePlate));
        $redis->executeCommand($redis_cmd);

        $car_location_data = $this->getContainer()->get('auto_manager.yunshan_zhihui_helper')->findCarLocation($licensePlate, $deviceNum);
        //转化坐标
        $gps_encrypt = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt($car_location_data['lat'],$car_location_data['lgt']);
        $gps_data = [$gps_encrypt['lon'],$gps_encrypt['lat']];
        echo "坐标" . $gps_encrypt['lon'] . ',' . $gps_encrypt['lat'] . PHP_EOL;

        if ($car_location_data) {

            $respondTime = $car_location_data['time'];

            // 保存车离线状态
            $online = 1;
            if(1 == $car_location_data['curr_ostt']){
                $online = 2;
            }
            $redis_cmd_gps = $redis->createCommand('hset', array("yunshanZhihui-car-online-status", $deviceNum, $online));
            $redis->executeCommand($redis_cmd_gps);

            // 保存有效gps定位值
            if ($gps_encrypt['lon'] < 0 && $gps_encrypt['lat'] < 0) {
                $redis_cmd_gps = $redis->createCommand('hget', array("yunshanZhihui-car-curlocation", $deviceNum));
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
            $redis_cmd_gps = $redis->createCommand('hset', array("yunshanZhihui-car-curlocation", $deviceNum,
                json_encode([
                    $gps_encrypt['lon'],
                    $gps_encrypt['lat'],
                    $licensePlate,
                    $respondTime,
                ])));
            $redis->executeCommand($redis_cmd_gps);


//            $new_data = (new \DateTime($car_location_data['time']))->getTimestamp();
            $contrast_time = (time() - strtotime($car_location_data['time']));

            // 保存车速度状态
            $redis_cmd_gps = $redis->createCommand('hset', array("yunshanZhihui-car-speed-status", $deviceNum, $car_location_data['speed']));
            $redis->executeCommand($redis_cmd_gps);

            // 保存历史轨迹数据
            $arr_gps = ['id' => $deviceNum, 'coordinate' => $gps_data, 'time' => (new \DateTime())->getTimestamp()];
//            $new_data = (new \DateTime($car_location_data['time']))->getTimestamp();
            $contrast_time = (time() - strtotime($car_location_data['report_time']));
            //if ($contrast_time < 30) {
                $redis_cmd_gps = $redis->createCommand('lpush', array("yunshanZhihui-gps-" . $deviceNum, json_encode($arr_gps)));
                $redis->executeCommand($redis_cmd_gps);
            //}
        }
    }

    /**
     * 云杉车辆状态信息存入redis
     */
    public function setCarData($deviceNum,$licensePlate)
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $car_information_data = $this->getContainer()->get('auto_manager.yunshan_zhihui_helper')->findCarInformation($licensePlate, $deviceNum);
        echo "总里程" . $car_information_data['total_mil'] . '电量' . $car_information_data['soc'] . '续航' . $car_information_data['rmn_mil'] . PHP_EOL;

        if ($car_information_data) {

            if ($car_information_data['total_mil'] >= 0) {
                //总里程
                $arr_total_mileage = ['id' => $deviceNum, 'mileage' => $car_information_data['total_mil'], 'time' => (new \DateTime())->getTimestamp()];
                $redis_cmd_mileage = $redis->createCommand('lpush', array("yunshanZhihui-mileage-" . $deviceNum, json_encode($arr_total_mileage)));
                $redis->executeCommand($redis_cmd_mileage);

                $redis_cmd= $redis->createCommand('hset',array("yunshanZhihui-car-running-info-total-mileage",$deviceNum, $car_information_data['total_mil']));
                $redis->executeCommand($redis_cmd);
            }

            if ($car_information_data['soc'] >= 0) {
                //电量
                $arr_power = ['id' => $deviceNum, 'power' => $car_information_data['soc'], 'time' => (new \DateTime())->getTimestamp()];
                $redis_cmd_power = $redis->createCommand('lpush', array("yunshanZhihui-power-" . $deviceNum, json_encode($arr_power)));
                $redis->executeCommand($redis_cmd_power);

                $redis_cmd= $redis->createCommand('hset',array("yunshanZhihu-car-running-info-power",$deviceNum, $car_information_data['soc']));
                $redis->executeCommand($redis_cmd);
            }

            if ($car_information_data['rmn_mil'] >= 0) {
                //续航里程
                $arr_remain_mileage = ['id' => $deviceNum, 'range' => $car_information_data['rmn_mil'], 'time' => (new \DateTime())->getTimestamp()];
                $redis_cmd_range = $redis->createCommand('lpush', array("yunshanZhihui-range-" . $deviceNum, json_encode($arr_remain_mileage)));
                $redis->executeCommand($redis_cmd_range);

                $redis_cmd= $redis->createCommand('hset',array("yunshanZhihui-car-running-info-remain-mileage",$deviceNum, $car_information_data['rmn_mil']));
                $redis->executeCommand($redis_cmd);
            }
        }
    }
}