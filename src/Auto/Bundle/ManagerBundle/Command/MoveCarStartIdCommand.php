<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/31
 * Time: 上午10:35
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class MoveCarStartIdCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:move:carstart:id')
            ->setDescription('move carstart id')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man = $this->getContainer()->get('doctrine')->getManager();

        $carstarts =
            $man
                ->getRepository('AutoManagerBundle:CarStartTbox')
                ->findAll()
        ;



        foreach($carstarts as $c){

            $c->getRentalCar()->setBoxId($c->getCarStartId());
            $man->persist($c->getRentalCar());
            $man->flush();
            echo $c->getRentalCar()->getLicense().PHP_EOL;




        }

    }
}