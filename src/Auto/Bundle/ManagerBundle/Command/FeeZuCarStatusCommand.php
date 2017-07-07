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


class FeeZuCarStatusCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:fee:zu:carstatus')
            ->setDescription('push small car location and status to redis')
        ;
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
    public function saveCarDataToRedis(){




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
                ->setParameter('name', 'feeZu')
                ->getQuery()
                ->getResult();
        ;


        foreach($rentalCars as $rentalCar){


            if(!$rentalCar->getBoxId()) continue;
            echo $rentalCar->getLicense().PHP_EOL;
            $this->setCarLocation($rentalCar->getBoxId(),$rentalCar->getLicensePlace()->getName().$rentalCar->getLicensePlate());

            $this->setCarData($rentalCar->getBoxId());

        }


    }

    /**
     * 微租车向redis存入车辆位置信息
     */
    public function setCarLocation($deviceNum,$licensePlate){

        $redis = $this->getContainer()->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('hset', array("feeZu-car-deviceNum-plate", $deviceNum, $licensePlate));
        $redis->executeCommand($redis_cmd);

        $car_location_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->findCarLocation($deviceNum);

        echo "坐标".$car_location_data['longitude'].','.$car_location_data['latitude'].PHP_EOL;

        if($car_location_data){
           // $gps_data = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt($car_location_data['longitude'],$car_location_data['latitude']);
            //$gps_data=[$car_location_data['longitude']+0.005386,$car_location_data['latitude']-0.002567];

            $gps_encrypt = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt($car_location_data['latitude'],$car_location_data['longitude']);

            $gps_data = [$gps_encrypt['lon'],$gps_encrypt['lat']];

            $respondTime = $car_location_data['reportTime'];

            // 保存有效gps定位值
            if ($gps_encrypt['lon'] < 0 && $gps_encrypt['lat'] < 0) {

                $redis_cmd_gps= $redis->createCommand('hget',array("feeZu-car-curlocation",$deviceNum));
                $historyData = $redis->executeCommand($redis_cmd_gps);

                if ($historyData) {

                    $history = json_decode($historyData);

                    $gps_encrypt['lon'] = $history[0];
                    $gps_encrypt['lat'] = $history[1];
                    $respondTime        = isset($history[3]) ? $history[3]: $car_location_data['reportTime'];

                } else {
                    $gps_encrypt['lon'] = 0;
                    $gps_encrypt['lat'] = 0;
                    $respondTime        = $car_location_data['reportTime'];
                }
            }



            //更新当前车的位置 
            $redis_cmd_gps= $redis->createCommand('hset',array("feeZu-car-curlocation",$deviceNum,
                    json_encode([
                        $gps_encrypt['lon'],
                        $gps_encrypt['lat'],
                        $licensePlate,
                        $respondTime,
                    ])));
            $redis->executeCommand($redis_cmd_gps);

            // 保存车离线状态
            $redis_cmd_gps= $redis->createCommand('hset',array("feeZu-car-online-status",$deviceNum,$car_location_data['onlineStatus']));
            $redis->executeCommand($redis_cmd_gps);

            // 保存车速度状态
            $redis_cmd_gps= $redis->createCommand('hset',array("feeZu-car-speed-status",$deviceNum,$car_location_data['speed']));
            $redis->executeCommand($redis_cmd_gps);



            // 保存历史轨迹数据
            $arr_gps = ['id'=>$deviceNum,'coordinate'=>$gps_data,'time'=>(new \DateTime())->getTimestamp()];

            $new_data = (new \DateTime($car_location_data['reportTime']))->getTimestamp();


            $contrast_time=(time()-$new_data)/1000;

            if($contrast_time<20)
            {
                $redis_cmd_gps= $redis->createCommand('lpush',array("feeZu-gps-".$deviceNum,json_encode($arr_gps)));

                $redis->executeCommand($redis_cmd_gps);


            }

        }


    }

    /**
     * 微租车车辆状态信息存入redis
     */
    public function setCarData($deviceNum)
    {


        $redis = $this->getContainer()->get('snc_redis.default');

        $car_status_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->oneCarStatus($deviceNum);

        echo "总里程".$car_status_data['totalMileage'].'电量'.$car_status_data['power'].'续航'.$car_status_data['remainMileage'].PHP_EOL;

        if($car_status_data) {

            $new_data = (new \DateTime($car_status_data['reportTime']))->getTimestamp();


            $contrast_time = (time() - $new_data) / 1000;


            if ($contrast_time < 20) {


                if ($car_status_data['totalMileage'] >= 0) {
                    //总里程
                    $arr_total_mileage = ['id' => $deviceNum, 'mileage' => $car_status_data['totalMileage'], 'time' => (new \DateTime())->getTimestamp()];
                    $redis_cmd_mileage = $redis->createCommand('lpush', array("feeZu-mileage-" . $deviceNum, json_encode($arr_total_mileage)));
                    $redis->executeCommand($redis_cmd_mileage);

                    $redis_cmd= $redis->createCommand('hset',array("feeZu-car-running-info-total-mileage",$deviceNum, $car_status_data['totalMileage']));
                    $redis->executeCommand($redis_cmd);
                }

                if ($car_status_data['power'] >= 0) {
                    //电量
                    $arr_power = ['id' => $deviceNum, 'power' => $car_status_data['power'], 'time' => (new \DateTime())->getTimestamp()];
                    $redis_cmd_power = $redis->createCommand('lpush', array("feeZu-power-" . $deviceNum, json_encode($arr_power)));
                    $redis->executeCommand($redis_cmd_power);

                    $redis_cmd= $redis->createCommand('hset',array("feeZu-car-running-info-power",$deviceNum, $car_status_data['power']));
                    $redis->executeCommand($redis_cmd);
                }

                if ($car_status_data['remainMileage'] >= 0) {
                    //续航里程
                    $arr_remain_mileage = ['id' => $deviceNum, 'range' => $car_status_data['remainMileage'], 'time' => (new \DateTime())->getTimestamp()];
                    $redis_cmd_range = $redis->createCommand('lpush', array("feeZu-range-" . $deviceNum, json_encode($arr_remain_mileage)));
                    $redis->executeCommand($redis_cmd_range);

                    $redis_cmd= $redis->createCommand('hset',array("feeZu-car-running-info-remain-mileage",$deviceNum, $car_status_data['remainMileage']));
                    $redis->executeCommand($redis_cmd);
                }
            }

        }



    }


}