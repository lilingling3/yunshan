<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/2/17
 * Time: 下午2:45
 */

namespace Auto\Bundle\ManagerBundle\Command;

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendCouponToRegisterMemberCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:coupon:to:register:member')
            ->setDescription('send coupon to register member')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:Member')
                ->createQueryBuilder('m');

        $members = $qb
            ->select('m')
            ->andWhere($qb->expr()->gt('m.createTime',':time'))
            ->setParameter('time', (new \DateTime())->modify("-10 minutes"))
            ->getQuery()
            ->getResult()
        ;

        $activity = $man
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->find(1);

        foreach($members as $member){

            $coupon = $man
                ->getRepository('AutoManagerBundle:Coupon')
                ->findBy(['member' => $member,'activity'=>1]);


            if(empty($coupon)){
                $amount = $this->getContainer()->get("auto_manager.coupon_helper")->send_activity_coupon($activity,
                    $member);
                echo $member->getId()."发放".$amount.PHP_EOL;
            }



        }

    }

}