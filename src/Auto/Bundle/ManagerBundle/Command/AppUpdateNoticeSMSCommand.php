<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/3/30
 * Time: 上午10:04
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;


class AppUpdateNoticeSMSCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:app:update:message')
            ->setDescription('app update notice message')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exit;

        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:MobileDevice')
                ->createQueryBuilder('m');

        $members = $qb
            ->select('m')
            ->andWhere($qb->expr()->isNotNull('m.member'))
            ->getQuery()
            ->getResult()
        ;

        $arr = [];
        foreach($members as $m){

            if(!in_array($m->getMember()->getId(),$arr)){

                $arr[] = $m->getMember()->getId();
                $this->getContainer()->get("auto_manager.sms_helper")->add($m->getMember()->getMobile(),"我们的客户端版本已更新至1.5.0，增加云随还(异地还车)、有车提醒功能，请更新后再使用。如有疑问，请联系客服400-111-8220");

                echo $m->getMember()->getMobile()."发短信".PHP_EOL;

            }

        }



    }
}