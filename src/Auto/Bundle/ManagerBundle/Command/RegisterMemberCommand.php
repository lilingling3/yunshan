<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/16
 * Time: 下午1:49
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Util\StringUtils;

class RegisterMemberCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:member:register')
            ->setDescription('Register member')
            ->addOption('mobile', null, InputOption::VALUE_REQUIRED)
            ->addOption('password', null, InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man    = $this->getContainer()->get('doctrine')->getManager();
        $member = null;
        if ($input->getOption('mobile'))
            $member =
                $man
                    ->getRepository('AutoManagerBundle:Member')
                    ->findOneBy(['mobile' => $input->getOption('mobile')])
            ;
        if (!$member) {
            $member = new \Auto\Bundle\ManagerBundle\Entity\Member();
            $member
                ->setMobile($input->getOption('mobile'))
            ;

            $encoded = $this->getContainer()->get('security.password_encoder')
                ->encodePassword($member, $input->getOption('password'));

            $member->setPassword($encoded);
            $member->setRoles(['ROLE_USER']);

            $man->persist($member);
        }
        $man->flush();

        $output->writeln(sprintf(
            'Register member <%d:%s>.',
            $member->getId(),
            $member->getMobile()
        ));
    }


}
