<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/6/15
 * Time: 下午5:20
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class InsertInviteTestDataCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('insert:invite:data')
            ->setDescription('test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        // swoole_timer_tick(10000, function ($timer_id) {

            $this->test();

        // });
    }


    public function test()
    {

        $man = $this->getContainer()->get('doctrine')->getManager();

        $member =
            $man
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['id' => '22']);

        $inviteeMember = 
            $man
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(["mobile"=>'18809890960']);



        $auth = 
            $man
                ->getRepository('AutoManagerBundle:AuthMember')
                ->findOneBy(["member"=>$inviteeMember]);

        $operate = new \Auto\Bundle\ManagerBundle\Entity\RechargeOperate(6);
        
        $man = $this->getContainer()->getDoctrine()->getManager();

        $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
        $record->setCreateTime(new \DateTime());
        $record->setMember($member);
        $record->setWalletAmount($member->getWallet() + 5);
        $record->setAmount(5);
        $record->setOperate($operate);
        $record->setRemark($operate->getName());
        $man->persist($record);



        $invite = new \Auto\Bundle\ManagerBundle\Entity\Invite();
        $invite->setCreateTime(new \DateTime());
        $invite->setInviter($member);
        $invite->setInviteeMobile('18809890960');
        $invite->setChannel('1003');
        $man->persist($invite); 


        $inviteReward = new \Auto\Bundle\ManagerBundle\Entity\InviteReward();
        $inviteReward->setRelative($invite);
        $inviteReward->setInvitee($auth);
        $inviteReward->setRechargeRecord($record);

        $man->persist($inviteReward); 

    }


}