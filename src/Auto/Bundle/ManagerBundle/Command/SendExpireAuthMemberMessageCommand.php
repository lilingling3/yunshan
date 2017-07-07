<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/8
 * Time: 上午9:54
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SendExpireAuthMemberMessageCommand  extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:send:expire:auth:member:message')
            ->setDescription('send expire auth member message')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:AuthMember')
                ->createQueryBuilder('m');

        $expireAuthMembers = $qb
            ->select('m')
            ->andWhere($qb->expr()->lte('m.licenseEndDate',':time'))
            ->andWhere($qb->expr()->gt('m.licenseEndDate',':min'))
            ->andWhere($qb->expr()->eq('m.licenseAuthError', ':error'))
            ->setParameter('error', 0)
            ->setParameter('time', (new \DateTime())->modify("+30 days"))
            ->setParameter('min', (new \DateTime())->modify("+28 days"))
            ->getQuery()
            ->getResult()
        ;


        foreach($expireAuthMembers as $auth){

            $message = new \Auto\Bundle\ManagerBundle\Entity\Message();

            $m = "您的驾驶证将在".$auth->getLicenseEndDate()->format('Y年m月d日')."过期，为了不影响您正常使用租车服务，请及时更换驾驶证并重新上传认证！ ";

            $message->setKind(1)
                ->setMember($auth->getMember())
                ->setContent($m)
                ->setRead(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
            ;
            $man->persist($message);
            $man->flush();

            echo "send order message to ".$auth->getMember()->getMobile().PHP_EOL;


        }
    }
}