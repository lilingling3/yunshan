<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/3/11
 * Time: 上午10:54
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PushRemindMessageCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:push:remind:message')
            ->setDescription('auto push remind message')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:Remind')
                ->createQueryBuilder('r');

        $reminds = $qb
            ->select('r')
            ->where($qb->expr()->isNull('r.remindTime'))
            ->andWhere($qb->expr()->gt('r.endTime',':time'))
            ->andWhere($qb->expr()->lt('r.createTime',':time'))
            ->setParameter('time',new \DateTime())
            ->getQuery()
            ->getResult()
        ;


        foreach($reminds as $remind){
            $qb =
                $man
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c');

            $rentalCars =

                $qb
                    ->select('c')
                    ->join('c.online','o')
                    ->where($qb->expr()->eq('c.rentalStation', ':station'))
                    ->andWhere($qb->expr()->eq('o.status', ':status'))
                    ->orderBy('c.id', 'DESC')
                    ->setParameter('station', $remind->getRentalStation())
                    ->setParameter('status', 1)
                    ->getQuery()
                    ->getResult();

            $rental_cars = array_map($this->getContainer()->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
                $rentalCars);


            foreach($rental_cars as $car){
                if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE){


                    $device = $man
                        ->getRepository('AutoManagerBundle:MobileDevice')
                        ->findOneBy(['member'=>$remind->getMember()],['id'=>'desc']);

                    if(empty($device)) break;
                    if(empty($remind->getRentalStation())) break;

                    echo "remind"." ".$remind->getId().PHP_EOL;

                    $custom = ['subject'=>'7', 'rentalStationID'=>$remind->getRentalStation()->getId(),
                        'latitude'=>$remind->getRentalStation()->getLatitude(),
                        'longitude'=>$remind->getRentalStation()->getLongitude()];
                    $message = $remind->getRentalStation()->getName()."有车可以租，快去下单吧！";

                    if($device->getPlatform()==1){
                        $res = $this->getContainer()->get('auto_manager.push_helper')->pushTokenIos($message,$device->getDevicetoken(),
                            $custom);
                        // echo json_encode($res).PHP_EOL;
                    }else{
                        $res = $this->getContainer()->get('auto_manager.push_helper')->pushTokenAndroid($message,$device->getDevicetoken(),$custom);
                    }

                    $remind->setRemindTime(new \DateTime());
                    $man->persist($remind);
                    $man->flush();

                    break;

                }

            }








        }
    }
}