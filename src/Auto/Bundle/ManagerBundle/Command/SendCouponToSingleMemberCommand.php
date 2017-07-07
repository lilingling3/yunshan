<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/1/29
 * Time: 上午9:50
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Util\StringUtils;

class SendCouponToSingleMemberCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:to:single')
            ->setDescription('send  coupon')
            ->addOption('member', 'm', InputOption::VALUE_OPTIONAL, '成员id')
            ->addOption('activity', 'a', InputOption::VALUE_OPTIONAL, '活动id')

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


        $member =
            $man
                ->getRepository('AutoManagerBundle:Member')
                ->find($input->getOption('member'));

        if(!empty($member)){
            $amount = $this->getContainer()->get("auto_manager.coupon_helper")->send_activity_coupon($activity,
                $member);
            echo $member->getId()."发放".$amount.PHP_EOL;
        }
            

        // }

    }

}