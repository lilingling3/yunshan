<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/6/15
 * Time: 下午5:20
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SaveCarLocationCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('save:car:location')
            ->setDescription('save car current location');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        swoole_timer_tick(36000000, function ($timer_id) {

            $this->sendInsuranceGps();

        });
    }


    /**
     * 
     */
    public function sendInsuranceGps()
    {

        $redis = $this->getContainer()->get('snc_redis.default');

        
        $man = $this->getContainer()->get('doctrine')->getManager();


        // 车型对应设备号集合
        $carTypes =
            $man
                ->getRepository('AutoManagerBundle:Car')
                ->findAll();

        foreach ($carTypes as $k => $carType) {


            if ($carType) {

                $type = $carType->getId();

                $cars =
                    $man
                        ->getRepository('AutoManagerBundle:RentalCar')
                        ->findBy(['car'=>$type]);

                // 清空redis缓存 
                $redis_cmd = $redis->createCommand('scard',array("location-car-type-".$type));
                $devCount  = $redis->executeCommand($redis_cmd);

                if ($devCount >= 1) {

                    for ($i=0; $i < $devCount; $i++) { 
                        
                        $redis_cmd = $redis->createCommand('spop',array("location-car-type-".$type));

                        $redis->executeCommand($redis_cmd);
                    }
                }
                

                foreach ($cars as $_k => $car) {

                    // 唯一设备号
                    $deviceNum = $car->getBoxid();

                    // 设备号是否有效
                    if (strlen($deviceNum) == 15 && !empty($deviceNum)) {
                        
                        // 保存各车型=>设备号集合
                        $redis_cmd = $redis->createCommand('sadd',array("location-car-type-".$carType->getId(),$deviceNum));

                        $redis->executeCommand($redis_cmd);

                    }
                }
            }
        }

        // 租赁点对应设备号集合
        
        $city =
            $man
                ->getRepository('AutoManagerBundle:Car')
                ->findAll();

        // 获取城市列表
        $cities = $this->getContainer()->get('auto_manager.area_helper')->getCitylist(true);

        if ($cities) {

               
            $list = [];
            foreach ($cities as $k => $v) {


                // 获取租赁点设备
                $stations = $this->getContainer()->get('auto_manager.station_helper')->getStationByArea($v);

                if ($stations) {

                    // 用于保存该市租赁点的索引
                    $curStation = [];

                    foreach ($stations as $station) {
                        
                        if (empty($station)) { continue; }

                        // 清redis缓存
                        $redis_cmd = $redis->createCommand('smembers',array("location-station-car-".$station->getId()));
                        $curList = $redis->executeCommand($redis_cmd);

                        if ($curList) {
                            // dump(array_values($curList));exit;
                            $redis_cmd = $redis->createCommand('srem',array("location-station-car-".$station->getId(),$curList));
                            $redis->executeCommand($redis_cmd);
                        }


                        // 根据租赁点找车
                        $rentalCars = 
                            $man->getRepository('AutoManagerBundle:RentalCar')
                                ->findBy(['rentalStation'=>$station]);

                        if ($rentalCars) {
                            
                            foreach ($rentalCars as $rentalCar) {
                                
                                if (empty($rentalCar) || strlen($rentalCar->getBoxid()) != 15) { continue; }

                                // 保存租赁点车设备
                                $redis_cmd = $redis->createCommand('sadd',array("location-station-car-".$station->getId(),$rentalCar->getBoxid()));
                                $redis->executeCommand($redis_cmd);

                            }

                            // 租赁点对应车设备号
                            array_push($curStation, "location-station-car-".$station->getId());
                        }
                        // dump($curStation);
                    }

                    // 清redis缓存
                    $redis_cmd = $redis->createCommand('smembers',array("location-city-car-".$v->getId()));
                    $curCityList = $redis->executeCommand($redis_cmd);

                    if ($curCityList) {

                        $redis_cmd = $redis->createCommand('srem',array("location-city-car-".$v->getId(), $curCityList));
                        $redis->executeCommand($redis_cmd);
                    }
                    

                    if ($curStation) {
                        // 保存市车设备
                        $redis_cmd = $redis->createCommand('sunionstore',array("location-city-car-".$v->getId(), $curStation));
                        $redis->executeCommand($redis_cmd); 
                    }

                }

            }


        }


    }



}