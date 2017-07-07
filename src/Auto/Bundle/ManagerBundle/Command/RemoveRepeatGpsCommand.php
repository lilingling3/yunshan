<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/6/13
 * Time: 下午4:44
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class RemoveRepeatGpsCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:remove:repeat:gps')
            ->setDescription('delete small car repeat gps to redis')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

            $this->delRepeatGps();

    }



    //测试使用
    public function get(){

        $redis = $this->getContainer()->get('snc_redis.default');

        $redis_cmd_llen = $redis->createCommand('llen',array('feeZu-gps-115262100002323'));

        $llen = $redis->executeCommand($redis_cmd_llen);

        $amonutData_cmd = $redis->createCommand('lrange',array('feeZu-gps-115262100002323',0,$llen));

        $data= $redis->executeCommand($amonutData_cmd);

        $data=json_decode($data[0],true);

        var_dump($data);exit;

    }



    public function delRepeatGps(){
        
        $man    = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $rentalCars =
            $qb->select('c')
                ->join('c.deviceCompany','p')
                ->getQuery()
                ->getResult();

        $redis = $this->getContainer()->get('snc_redis.default');

        $data_number='';


        foreach($rentalCars as $rentalCar){

            $redis_cmd_llen = $redis->createCommand('llen',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId()));

            $llen = $redis->executeCommand($redis_cmd_llen);


            $e = 0;

            $gps_data='';


            for ($i = 0; $i < $llen; $i++) {

                $redis_cmd_lindex_data = $redis->createCommand('lindex', array($rentalCar->getDeviceCompany()->getEnglishName() . '-gps-' . $rentalCar->getBoxId(), $e));

                $lindex_data = $redis->executeCommand($redis_cmd_lindex_data);

                $l_data = json_decode($lindex_data, true);

                echo "本次redis操作的的list键值为:" . $rentalCar->getDeviceCompany()->getEnglishName() . '-gps-' . $rentalCar->getBoxId() . PHP_EOL;

                if ($gps_data == $l_data['coordinate']) {

                    $redis_cmd_del = $redis->createCommand('lrem', array($rentalCar->getDeviceCompany()->getEnglishName() . '-gps-' . $rentalCar->getBoxId(), 1, json_encode($l_data)));

                    $data_number += 1;

                    $redis->executeCommand($redis_cmd_del);

                    echo "删除重复数据gps" . json_encode($l_data) . PHP_EOL;

                    unset($redis_cmd_lindex_data);

                    unset($lindex_data);

                    unset($l_data);

                    unset($redis_cmd_del);

                } else {

                    unset($gps_data);

                    $gps_data = $l_data['coordinate'];

                    $e += 1;



                }


            }


                    unset($redis_cmd_llen);

                    unset($llen);



        }


        if($data_number>0){

            echo "本次执行删除redis内重复gps数据条数为：".$data_number.'条。';

        }else{

            echo "无重复数据删除！";

        }

    }







}