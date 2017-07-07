<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/23
 * Time: 下午4:55
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;

class CancelRentalOrderCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:cancel:order')
            ->setDescription('cancel rental order')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exit;
        $man = $this->getContainer()->get('doctrine')->getManager();

        $orders =
            $man
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->findBy(['useTime'=>null,'cancelTime'=>null,'endTime'=>null])
        ;


        foreach($orders as $order){

           $create_time = strtotime($order->getCreateTime()->format('Y-m-d H:i:s'));
           $now_time = strtotime(date('Y-m-d H:i:s'));

            if($now_time-$create_time>15*60){

                $order->setCancelTime(new \DateTime());
                $man->persist($order);
                $man->flush();
            }

        }

    }
}