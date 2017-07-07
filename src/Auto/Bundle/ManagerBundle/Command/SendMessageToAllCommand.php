<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/22
 * Time: 下午2:12
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;


class SendMessageToAllCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:message:to:all')
            ->setDescription('send message to all')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exit;
        $man = $this->getContainer()->get('doctrine')->getManager();
        $members = $man
            ->getRepository('AutoManagerBundle:Member')
            ->findAll();

        foreach($members as $member){

            $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
            $message->setMember($member)
                    ->setContent("亲爱的用户，温馨提示您:即日起云杉智行夜间租赁时间段更改为23时至次日5时。感谢您一直以来的支持!")
                ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                ->setKind(1);

            $man->persist($message);
            $man->flush();

            echo "send message to ".$member->getMobile().PHP_EOL;


        }



    }

}