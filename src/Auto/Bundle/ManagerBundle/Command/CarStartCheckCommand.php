<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/11/19
 * Time: 下午8:54
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CarStartCheckCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('auto:carstart:check')
            ->setDescription('car start check')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man    = $this->getContainer()->get('doctrine')->getManager();

        $rentalCars =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findAll()
        ;

        $rental_car_ids = [];

        foreach($rentalCars as $car){

            if($car->getDeviceCompany()->getEnglishName()!='carStart') continue;

            $redis = $this->getContainer()->get('snc_redis.default');
            $redis_cmd= $redis->createCommand('lindex',array('carStart-status-'.$car->getBoxId(),0));
            $status_json = $redis->executeCommand($redis_cmd);

            $status_arr = json_decode($status_json,true);

            if(((new \DateTime())->getTimestamp()-$status_arr['time'])>5*60){

                echo $car->getLicense().PHP_EOL;
                $rental_car_ids[] = $car->getId();

            }
        }

        if(!empty($rental_car_ids)){

            $redis = $this->getContainer()->get('snc_redis.default');
            $redis_cmd= $redis->createCommand('lpush',array('broken-rental-car',json_encode
            (['ids'=>$rental_car_ids,'time'=>(new \DateTime())->getTimestamp()])));
            $redis->executeCommand($redis_cmd);

        }
    }
}

