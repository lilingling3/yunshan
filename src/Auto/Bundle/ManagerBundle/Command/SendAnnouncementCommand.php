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
use Auto\Bundle\ManagerBundle\Entity\Message;

class SendAnnouncementCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('send:announce:message')
            ->setDescription('send message');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	   $this->sendMessage();
    }


    public function sendMessage() {


	    $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:MobileDevice')
                ->createQueryBuilder('m');

        $devices =
            $qb
                ->select('m')
                ->where($qb->expr()->isNotNull('m.member'))
                // ->andWhere($qb->expr()->eq('m.member', ':mem'))
                // ->setParameter('mem', $test)
                ->getQuery()
                ->getResult();
        

        $messageStr = "由于近期广州市政进行道路维修，广州大学城北C出口网点暂时停运，给您造成的不便我们深表歉意！预计停运时间15天，网点恢复运营我们将第一时间通知大家，感谢大家谅解！";

        $custom = ['subject'=>'8'];

        if (!empty($devices)) {

            foreach ($devices as $key => $device) {
                

                if($device->getPlatform()==1){

		            $this->getContainer()->get('auto_manager.push_helper')->pushTokenIos($messageStr,$device->getDevicetoken(),$custom);
                }else{

                    $this->getContainer()->get('auto_manager.push_helper')->pushTokenAndroid($messageStr,$device->getDevicetoken(),$custom);
                }

                $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
                $message->setMember($device->getMember());
                $message->setContent($messageStr);
                $message->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD);
                $message->setKind(1);

                $man->persist($message);
                $man->flush();

                echo "用户".$device->getMember()->getMobile()."已发送信息".PHP_EOL;
            }
        }

    }

}
