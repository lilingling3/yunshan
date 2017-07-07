<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/10/24
 * Time: 上午10:04
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Util\StringUtils;

class SendNoticeMessageCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:message')
            ->setDescription('send notice message')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();

        $messages =
            $man
                ->getRepository('AutoManagerBundle:SMS')
                ->findBy(['status'=>0])
        ;

        foreach($messages as $message){
            $message->setStatus(1);
            $man->persist($message);
            $man->flush();
            $this->getContainer()->get("auto_manager.sms_helper")->send($message->getMobile(),$message->getMessage());
        }

    }

}