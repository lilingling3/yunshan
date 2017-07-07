<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/15
 * Time: 下午6:23
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class CreateActivityCouponCodeCommand  extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:create:activity:coupon:code')
            ->setDescription('car create activity coupon code')
            ->addOption('activity', 'activity', InputOption::VALUE_REQUIRED, 'activity id')
            ->addOption('count', 'o', InputOption::VALUE_REQUIRED, 'count)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $activity =
            $man
                ->getRepository('AutoManagerBundle:CouponActivity')
                ->find($input->getOption('activity'))
        ;

        if(empty($activity)||empty($activity->getKinds())){

            echo "活动无效";
        }

        $kind = $activity->getKinds()[0];




        for($i=0;$i<$input->getOption('count');$i++){

            $c = new \Auto\Bundle\ManagerBundle\Entity\Coupon();

            do {
                $code = strtoupper($this->getContainer()->get('auto_manager.wechat_helper')->createNonceStr(6));
                $coupon =
                    $man
                        ->getRepository('AutoManagerBundle:Coupon')
                        ->findOneBy(['code'=>$code])
                ;

            } while (!empty($coupon));

            echo $code.PHP_EOL;

            $date = (new \DateTime((new \DateTime('+'.$kind->getValidDay().' days'))->format('Y-m-d')));

            $c
                ->setEndTime($date)
                ->setKind($kind)
                ->setActivity($activity)
                ->setCode($code)
            ;
            $man->persist($c);
            $man->flush();


        }

    }



}