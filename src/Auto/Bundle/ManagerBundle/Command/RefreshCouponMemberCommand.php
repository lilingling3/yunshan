<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/1/28
 * Time: 下午3:16
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RefreshCouponMemberCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('auto:refresh:coupon:member')
            ->setDescription('auto refresh illegal')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        $coupons = $qb
            ->select('c')
            ->andWhere($qb->expr()->isNull('c.member'))
            ->andWhere($qb->expr()->isNotNull('c.mobile'))
            ->andWhere($qb->expr()->gt('c.endTime',':endTime'))
            ->setParameter('endTime', (new \DateTime()))
            ->getQuery()
            ->getResult()
        ;

       foreach($coupons as $coupon){

           $member  =
               $man
                   ->getRepository('AutoManagerBundle:Member')
                   ->findOneBy(['mobile'=>$coupon->getMobile()])
           ;

           if(!empty($member)){
                echo $member->getMobile().$coupon->getId().PHP_EOL;
                $coupon->setMember($member);
                $man->persist($coupon);
                $man->flush();

           }

       }

    }
}