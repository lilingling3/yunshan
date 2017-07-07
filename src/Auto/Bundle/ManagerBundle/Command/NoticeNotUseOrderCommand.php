<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/9/9
 * Time: 下午3:56
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;


class NoticeNotUseOrderCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:notice:not:use:order')
            ->setDescription('notice not use order')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $rentalOrders =
            $qb
                ->select('o')
                ->where($qb->expr()->isNull('o.useTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->andWhere($qb->expr()->isNull('o.endTime'))
                ->andWhere($qb->expr()->lt('o.createTime',':time'))
                ->setParameter('time', (new \DateTime('-30 minutes')))
                ->getQuery()
                ->getResult();
        ;

        foreach($rentalOrders as $order){

            if($order->getCreateTime()>(new \DateTime('-30 minutes'))&&$order->getCreateTime()<(new \DateTime('-20
            minutes'))){

                $message = "您所租赁的车辆".$order->getRentalCar()->getLicense()
                    ."已经30分钟没有取车，此次行程已开始计费不要忘喽。如有疑问，请联系客服400-111-8220";
                $device = $man
                    ->getRepository('AutoManagerBundle:MobileDevice')
                    ->findOneBy(['member'=>$order->getMember()],['id'=>'desc']);

                $custom = ['subject'=>'1'];
                if(empty($device)){
                    continue;
                }

                if($device->getPlatform()==1){
                    $this->getContainer()->get('auto_manager.push_helper')->pushTokenIos($message,$device->getDevicetoken(),$custom);
                }else{
                    $this->getContainer()->get('auto_manager.push_helper')->pushTokenAndroid($message,$device->getDevicetoken(),$custom);
                }

                echo $message.PHP_EOL;

            }

            if($order->getCreateTime()>(new \DateTime('-60 minutes'))&&$order->getCreateTime()<(new \DateTime('-50
            minutes'))){
                $message = "您所租赁的车辆".$order->getRentalCar()->getLicense()
                    ."还未取车，同时已产生一小时租赁费用，请及时关注您的租赁状态以免产生不必要的费用。如有疑问，请联系客服400-111-8220";

                $this->getContainer()->get("auto_manager.sms_helper")->send($order->getMember()->getMobile(),$message);
                echo $message.PHP_EOL;

            }
        }


    }
}