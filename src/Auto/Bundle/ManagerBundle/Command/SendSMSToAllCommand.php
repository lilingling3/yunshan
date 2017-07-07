<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/9/8
 * Time: 上午10:46
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Util\StringUtils;


class SendSMSToAllCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:sms:to:all')
            ->setDescription('send sms to all')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $members =
            $man
                ->getRepository('AutoManagerBundle:Member')
                ->findAll()
        ;

        $message = "史上最狠充返，最多可得20000元。首充还能多得100元，还等什么快快来。如有疑问，请联系客服400-111-8220";
        foreach($members as $member){

            $this->getContainer()->get("auto_manager.sms_helper")->send($member->getMobile(),$message);
            echo $member->getMobile().PHP_EOL;
        }

    }

}