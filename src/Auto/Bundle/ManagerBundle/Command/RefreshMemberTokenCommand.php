<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/23
 * Time: 上午9:53
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Util\SecureRandom;

class RefreshMemberTokenCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:refresh:member:token')
            ->setDescription('auto refresh member token')
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
        foreach($members as $member){

            if(!$member->getToken()){

                $member->setToken(md5((new SecureRandom())->nextBytes(18)));
                $man->persist($member);
                $man->flush();

                echo $member->getMobile().PHP_EOL;

            }

        }




    }
}