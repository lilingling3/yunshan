<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/7
 * Time: 下午5:05
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;


class MemberSendOrderMessageCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:member:order:message')
            ->setDescription('member order push message')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $create_orders = $qb
            ->select('o')
            ->andWhere($qb->expr()->gt('o.createTime',':time'))
            ->andWhere($qb->expr()->isNull('o.cancelTime'))
            ->andWhere($qb->expr()->isNull('o.endTime'))
            ->setParameter('time', (new \DateTime())->modify("-1 minutes"))
            ->getQuery()
            ->getResult()
        ;

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $cancel_orders = $qb
            ->select('o')
            ->andWhere($qb->expr()->gt('o.cancelTime',':time'))
            ->andWhere($qb->expr()->isNotNull('o.cancelTime'))
            ->andWhere($qb->expr()->isNull('o.endTime'))
            ->setParameter('time', (new \DateTime())->modify("-1 minutes"))
            ->getQuery()
            ->getResult()
        ;


        $orders = array_merge($cancel_orders,$create_orders);


        foreach($orders as $order){

            $message = new \Auto\Bundle\ManagerBundle\Entity\Message();

            if(!empty($order->getCancelTime())){

                //取消
                $m = '您所租赁的'.$order->getRentalCar()->getLicense().'行程已取消，感谢您的使用！';

            }else{

                //下单成功
                $m='您租赁的'.$order->getRentalCar()->getLicense().'已下单成功，请尽快到'.$order->getPickUpStation()->getName()
                    .'取车，此行程已开始计费！';
            }

            $message->setKind(1)
                    ->setMember($order->getMember())
                    ->setContent($m)
                    ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                    ;
            $man->persist($message);
            $man->flush();

            echo "send order message to ".$order->getMember()->getMobile().PHP_EOL;

        }




    }
}