<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/7/28
 * Time: 上午10:38
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;


class OffRentalCarCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:off:rental:car')
            ->setDescription('off rental car by rental order')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man    = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $orders =
            $qb->select('o')
                ->andWhere($qb->expr()->gte('o.endTime',':time'))
                ->andWhere($qb->expr()->isNotNull('o.endTime'))
                ->setParameter('time', (new \DateTime())->modify("-2 minutes"))
                ->getQuery()
                ->getResult();

        //断电


        foreach($orders as $order){
            echo "off rental car".$order->getRentalCar()->getLicense().PHP_EOL;
            $this->getContainer()->get("auto_manager.rental_car_helper")->operate($order->getRentalCar(),'off',
                $order->getMember());
        }

    }

}