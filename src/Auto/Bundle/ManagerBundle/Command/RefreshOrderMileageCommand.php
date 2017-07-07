<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/12/14
 * Time: 下午3:41
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;


class RefreshOrderMileageCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:refresh:order:mileage')
            ->setDescription('refresh order mileage')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $orders = $qb
            ->select('o')
            ->andWhere($qb->expr()->isNull('o.cancelTime'))
            ->andWhere($qb->expr()->isNotNull('o.useTime'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->andX($qb->expr()->isNotNull('o.endTime'),$qb->expr()->isNull('o.mileage')),
                $qb->expr()->isNull('o.endTime')
            )
            )
            ->getQuery()
            ->getResult()
        ;

        $redis = $this->getContainer()->get('snc_redis.default');

        foreach($orders as $order){


            if(empty($order->getRentalCar()->getBoxId())){
                continue;
            }

            echo $order->getId().PHP_EOL;

            $redis_cmd= $redis->createCommand('LLEN',array($order->getRentalCar()->getDeviceCompany()
                ->getEnglishName().'-mileage-'.$order->getRentalCar()->getBoxId()));
            $length = $redis->executeCommand($redis_cmd);

            $redis_cmd= $redis->createCommand('lrange',array($order->getRentalCar()->getDeviceCompany()
                    ->getEnglishName().'-mileage-'.$order->getRentalCar()->getBoxId(),0,$length));
            $mileage_arr = $redis->executeCommand($redis_cmd);

            $mileage_list = [];


            foreach($mileage_arr as $mileage){

                $m = json_decode($mileage,true);

                $end = $order->getEndTime()?$order->getEndTime():(new \DateTime());

                if($m['time']>$order->getUseTime()->getTimestamp()&&($m['time']<$end->getTimestamp())){
                    $mileage_list[] = $m['mileage'];
                }
            }

            $distance = 0;

            if(!empty($mileage_list)){
                $distance = $mileage_list[0]-end($mileage_list);
            }

            if($distance>0){
                echo $order->getId().'距离'.$distance.PHP_EOL;
                $order->setMileage($distance*1000);
                $man->persist($order);
                $man->flush();
            }
        }
    }
}