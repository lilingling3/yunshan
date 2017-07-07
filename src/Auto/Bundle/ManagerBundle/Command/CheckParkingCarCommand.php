<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/8/18
 * Time: 下午2:57
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckParkingCarCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:check:parking:car')
            ->setDescription('check parking car')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s');

        $stations = $qb
            ->select('s')
            ->andWhere($qb->expr()->eq('s.online', ':online'))
            ->setParameter('online', 1)
            ->getQuery()
            ->getResult();

        $empty_stations = [];
        $overload_stations = [];


        foreach($stations as $station){

            $cars_count = call_user_func( $this->getContainer()->get('auto_manager.StationHelper')->get_station_normalizer(),
                $station);

            if($cars_count['usableRentalCarCount'] == 0){

                $empty_stations[]=$station->getId();

            }else{

                if(count($station->getRentalCars())>$station->getParkingSpaceTotal()){

                    $overload_stations[]=$station->getId();
                }

            }

        }

        $redis = $this->getContainer()->get('snc_redis.default');
        if(!empty($empty_stations)){

            $redis_cmd= $redis->createCommand('set',array('empty-rental-station',implode(',',$empty_stations)));
            $status_json = $redis->executeCommand($redis_cmd);
        }
        if(!empty($overload_stations)){

            $redis_cmd= $redis->createCommand('set',array('overload-rental-station',implode(',',$overload_stations)));
            $status_json = $redis->executeCommand($redis_cmd);
        }
        var_dump($empty_stations);
        var_dump($overload_stations);

    }
}