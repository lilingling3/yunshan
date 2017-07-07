<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/25
 * Time: 下午3:04
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CleanCarStartDataCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:clean:carstart:data')
            ->setDescription('car start clean data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $man = $this->getContainer()->get('doctrine')->getManager();

        $boxes =
            $man
                ->getRepository('AutoManagerBundle:CarStartTbox')
                ->findAll()
        ;

        $redis = $this->getContainer()->get('snc_redis.default');
        foreach($boxes as $box){

            //del find data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-find-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);

            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-find-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." find ".($count-43000).PHP_EOL;
            }

            //del door data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-door-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);

            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-door-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." door ".($count-43000).PHP_EOL;

            }


            //del range data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-range-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);

            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-range-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." range ".($count-43000).PHP_EOL;

            }

            //del gps data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-gps-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);

            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-gps-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." gps ".($count-43000).PHP_EOL;

            }

            //del status data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-status-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);

            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-status-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." status ".($count-43000).PHP_EOL;

            }

            //del mileage data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-mileage-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);


            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-mileage-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." mileage ".($count-43000).PHP_EOL;

            }
            //del power data
            $redis_cmd= $redis->createCommand('LLEN',array('carStart-power-'.$box->getCarStartId()));
            $count = $redis->executeCommand($redis_cmd);

            if($count>=43000){
                $redis_cmd= $redis->createCommand('LTRIM',array('carStart-power-'.$box->getCarStartId(),0,43000));
                $redis->executeCommand($redis_cmd);
                echo "del ".$box->getCarStartId()." power ".($count-43000).PHP_EOL;

            }


        }






    }

}