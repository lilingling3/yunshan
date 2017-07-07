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



class NewFeeZuCarStatusCommand extends ContainerAwareCommand{


    public function configure()
    {
        $this
            ->setName('auto:fee:zu:carstatusnew')
            ->setDescription('push small car location and status to redis')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

//        swoole_timer_tick(10000, function ($timer_id) {

            $this->saveCarDataToRedis();

//        });

    }




    /**
     * 将车辆的位置信息 状态信息 存入redis
     */

    public function saveCarDataToRedis(){


        // 保存车辆时时状态信息
        $this->setCarLocation();

        // 保存车辆时时状态信息
        $this->setCarData();


    }

    /**
     * 微租车向redis存入车辆位置信息
     */

    public function setCarLocation($licensePlate = null){

        $redis = $this->getContainer()->get('snc_redis.default');

        // 获取全部车辆位置信息
        $car_location_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->findCarsLocation();

//        echo "坐标".$car_location_data['longitude'].','.$car_location_data['latitude'].PHP_EOL;

        if(!empty($car_location_data) && is_array($car_location_data)){

            foreach ($car_location_data as $key => $location) {

                // 坐标系转换 WGS-84 to GCJ-02
                $gps_encrypt = $this->getContainer()->get('auto_manager.gps_helper')->gcj_encrypt(
                    $location['latitude'],
                    $location['longitude']
                );

                // 当前设备号
                $gps_dev_num = $location['deviceNum'];

                $gps_data = [$gps_encrypt['lon'],$gps_encrypt['lat']];

                $respondTime = $location['reportTime'];

                // 保存有效gps定位值
                if ($gps_encrypt['lon'] < 0 && $gps_encrypt['lat'] < 0) {

                    $redis_cmd_gps= $redis->createCommand('hget',array("feeZu-car-curlocation", $gps_dev_num));
                    $historyData = $redis->executeCommand($redis_cmd_gps);

                    if ($historyData) {

                        $history = json_decode($historyData);

                        $gps_encrypt['lon'] = $history[0];
                        $gps_encrypt['lat'] = $history[1];
                        $respondTime        = isset($history[3]) ? $history[3]: $location['reportTime'];

                    } else {
                        $gps_encrypt['lon'] = 0;
                        $gps_encrypt['lat'] = 0;
                        $respondTime        = $location['reportTime'];
                    }
                }

                $redis_cmd= $redis->createCommand('hget',array("feeZu-car-deviceNum-plate",$location['deviceNum']));
                $licensePlate = $redis->executeCommand($redis_cmd);

                //更新当前车的位置
                $redis_cmd_gps= $redis->createCommand('hset',array("feeZu-car-curlocation", $gps_dev_num,
                    json_encode([
                        $gps_encrypt['lon'],
                        $gps_encrypt['lat'],
                        $licensePlate,
                        $respondTime,
                    ])));
                $redis->executeCommand($redis_cmd_gps);

                // 保存车离线状态
                $redis_cmd_gps= $redis->createCommand('hset',array("feeZu-car-online-status", $gps_dev_num, $location['onlineStatus']));
                $redis->executeCommand($redis_cmd_gps);


                // 保存历史轨迹数据
                $arr_gps = [
                        'id' => $gps_dev_num,
                        'coordinate' => $gps_data,
                        'time' => (new \DateTime())->getTimestamp()
                    ];

                $new_data = (new \DateTime($location['reportTime']))->getTimestamp();

                $contrast_time=(time() - $new_data)/1000;

                if($contrast_time<20)
                {
                    $redis_cmd_gps= $redis->createCommand('lpush',array("feeZu-gps-".$gps_dev_num,json_encode($arr_gps)));

                    $redis->executeCommand($redis_cmd_gps);
                }
            }
        }


    }

    /**
     * 微租车车辆状态信息存入redis
     */
    public function setCarData()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $car_status_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->multiCarStatus();


        if(!empty($car_status_data) && is_array($car_status_data)) {


            foreach ($car_status_data as $car_status_datum) {

                $new_data = (new \DateTime($car_status_datum['reportTime']))->getTimestamp();

                $contrast_time = (time() - $new_data) / 1000;

                if ($contrast_time < 20) {

                    // 设备号
                    $deviceNum = $car_status_datum['deviceNum'];

                    if ($car_status_datum['totalMileage'] >= 0) {
                        //总里程
                        $arr_total_mileage = [
                            'id' => $deviceNum,
                            'mileage' => $car_status_datum['totalMileage'],
                            'time' => (new \DateTime())->getTimestamp()];

                        $redis_cmd_mileage = $redis->createCommand('lpush', array("feeZu-mileage-" . $deviceNum, json_encode($arr_total_mileage)));
                        $redis->executeCommand($redis_cmd_mileage);
                    }

                    if ($car_status_datum['power'] >= 0) {
                        //电量
                        $arr_power = [
                            'id' => $deviceNum,
                            'power' => $car_status_datum['power'],
                            'time' => (new \DateTime())->getTimestamp()];

                        $redis_cmd_power = $redis->createCommand('lpush', array("feeZu-power-" . $deviceNum, json_encode($arr_power)));
                        $redis->executeCommand($redis_cmd_power);
                    }

                    if ($car_status_datum['remainMileage'] >= 0) {
                        //续航里程
                        $arr_remain_mileage = [
                            'id' => $deviceNum,
                            'range' => $car_status_datum['remainMileage'],
                            'time' => (new \DateTime())->getTimestamp()];

                        $redis_cmd_range = $redis->createCommand('lpush', array("feeZu-range-" . $deviceNum, json_encode($arr_remain_mileage)));
                        $redis->executeCommand($redis_cmd_range);
                    }

                    echo "总里程".$car_status_datum['totalMileage'].'电量'.$car_status_datum['power'].'续航'.$car_status_datum['remainMileage'].PHP_EOL;
                }
            }
        }
    }

} 

