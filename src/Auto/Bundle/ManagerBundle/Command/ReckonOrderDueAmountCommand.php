<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/31
 * Time: 下午5:36
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ReckonOrderDueAmountCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:reckon:order')
            ->setDescription('car reckon order')
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
            ->andWhere($qb->expr()->isNull('o.dueAmount'))
            ->andWhere($qb->expr()->isNull('o.cancelTime'))
            ->andWhere($qb->expr()->isNotNull('o.endTime'))
            ->orderBy('o.id')
            ->getQuery()
            ->getResult()
        ;

        foreach($orders as $o){


            $cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($o);

            $o->setDueAmount($cost['cost']);

            $man->persist($o);
            $man->flush();

            echo $o->getId().PHP_EOL;
        }


    }

}