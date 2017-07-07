<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/1/7
 * Time: 下午3:36
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SendAuthMemberSMSCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:auth:member')
            ->setDescription('send auth member sms')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exit;

        $man = $this->getContainer()->get('doctrine')->getManager();

        $authMembers =
            $man
                ->getRepository('AutoManagerBundle:AuthMember')
                ->findBy(['licenseAuthError' => 0]);

        foreach($authMembers as $auth){

            $this->getContainer()->get("auto_manager.sms_helper")->add(
                $auth->getMember()->getMobile(),
                "亲爱的用户，温馨提示您:即日起云杉智行夜间租赁时间段更改为23时至次日5时。感谢您一直以来的支持!"
            );

            echo "send to ".$auth->getMember()->getMobile().PHP_EOL;

        }

    }

}