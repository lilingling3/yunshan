<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/12
 * Time: 下午4:46
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;


class RefreshCarStartPasswordCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:refresh:carstart:password')
            ->setDescription('auto refresh carstart password')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man = $this->getContainer()->get('doctrine')->getManager();
        $boxes =
            $man
                ->getRepository('AutoManagerBundle:CarStartTbox')
                ->findBy(['password'=>null])
        ;

        foreach($boxes as $box){

            if(!$box->getPassword()){

                $password = $this->get_random_integer(4);
                $result = $this->getContainer()->get("auto_manager.rental_car_helper")->car_start_operate
                ($box->getCarStartId(),'encode',$password);

                if($result){

                    $box->setPassword($password);
                    $man->persist($box);
                    $man->flush();

                    echo "修改密码".$box->getRentalCar()->getLicensePlate()." ".$password.PHP_EOL;
                }


            }

        }


    }


    function get_random_integer($length)
    {
        $key='';
        for($i=0;$i<$length;$i++)
        {
            $key .= rand(1,6);    //生成php随机数
        }
        return $key;
    }
}