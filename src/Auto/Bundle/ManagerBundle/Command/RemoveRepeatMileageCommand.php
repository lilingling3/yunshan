<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/7/14
 * Time: 下午2:15
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class RemoveRepeatMileageCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:remove:repeat:mileage')
            ->setDescription('delete small car repeat mileage to redis')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->del();

    }



    public function del(){

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

        $data_number = 0;

        foreach($rentalCars as $rentalCar){

            $data_number+=$this->delRepeatMileage($rentalCar);


            gc_collect_cycles();



        }


        if($data_number>0){

            echo "本次执行删除redis内重复mileage数据条数为：".$data_number.'条。';

        }else{

            echo "无重复数据删除！";

        }




    }



    public function delRepeatMileage($rentalCar){


            $redis = $this->getContainer()->get('snc_redis.default');

            $data_number='';



            $redis_cmd_llen = $redis->createCommand('llen',array($rentalCar->getDeviceCompany()->getEnglishName().'-mileage-'.$rentalCar->getBoxId()));

            $llen = $redis->executeCommand($redis_cmd_llen);

            $mileage_data='';


            $e = 0;


            for ($i = 0; $i < $llen; $i++) {

                $redis_cmd_lindex_data = $redis->createCommand('lindex', array($rentalCar->getDeviceCompany()->getEnglishName() . '-mileage-' . $rentalCar->getBoxId(), $e));

                $lindex_data = $redis->executeCommand($redis_cmd_lindex_data);

                $l_data = json_decode($lindex_data, true);

                echo "本次redis操作的的list键值为:" . $rentalCar->getDeviceCompany()->getEnglishName() . '-mileage-' . $rentalCar->getBoxId() . PHP_EOL;

                if ($mileage_data == $l_data['mileage']) {

                    $redis_cmd_del = $redis->createCommand('lrem', array($rentalCar->getDeviceCompany()->getEnglishName() . '-mileage-' . $rentalCar->getBoxId(), 1, json_encode($l_data)));

                    $data_number += 1;

                    $redis->executeCommand($redis_cmd_del);

                    echo "删除重复数据mileage" . json_encode($l_data) . PHP_EOL;

                    unset($l_data);

                    unset($lindex_data);

                    unset($redis_cmd_lindex_data);

                    unset($lindex_data);

                    unset($l_data);

                    unset($redis_cmd_del);


                } else {

                    unset($mileage_data);

                    $mileage_data = $l_data['mileage'];

                    $e += 1;




                }

                gc_collect_cycles();


            }

            unset($mileage_data);

            unset($redis_cmd_llen);

            unset($llen);


        return $data_number;





    }











//
//    public function delRepeatMileage(){
//
//        $man    = $this->getContainer()->get('doctrine')->getManager();
//
//        $qb =
//            $man
//                ->getRepository('AutoManagerBundle:RentalCar')
//                ->createQueryBuilder('c');
//
//        $rentalCars =
//            $qb->select('c')
//                ->join('c.deviceCompany','p')
//                ->getQuery()
//                ->getResult();
//
//        $redis = $this->getContainer()->get('snc_redis.default');
//
//        $data_number='';
//
//        foreach($rentalCars as $rentalCar){
//
//            $redis_cmd_llen = $redis->createCommand('llen',array($rentalCar->getDeviceCompany()->getEnglishName().'-mileage-'.$rentalCar->getBoxId()));
//
//            $llen = $redis->executeCommand($redis_cmd_llen);
//
//            $mileage_data='';
//
//
//            $e = 0;
//
//
//            for ($i = 0; $i < $llen; $i++) {
//
//                $redis_cmd_lindex_data = $redis->createCommand('lindex', array($rentalCar->getDeviceCompany()->getEnglishName() . '-mileage-' . $rentalCar->getBoxId(), $e));
//
//                $lindex_data = $redis->executeCommand($redis_cmd_lindex_data);
//
//                $l_data = json_decode($lindex_data, true);
//
//                echo "本次redis操作的的list键值为:" . $rentalCar->getDeviceCompany()->getEnglishName() . '-mileage-' . $rentalCar->getBoxId() . PHP_EOL;
//
//                if ($mileage_data == $l_data['mileage']) {
//
//                    $redis_cmd_del = $redis->createCommand('lrem', array($rentalCar->getDeviceCompany()->getEnglishName() . '-mileage-' . $rentalCar->getBoxId(), 1, json_encode($l_data)));
//
//                    $data_number += 1;
//
//                    $redis->executeCommand($redis_cmd_del);
//
//                    echo "删除重复数据mileage" . json_encode($l_data) . PHP_EOL;
//
//                    unset($l_data);
//
//                    unset($lindex_data);
//
//                    unset($redis_cmd_lindex_data);
//
//                    unset($lindex_data);
//
//                    unset($l_data);
//
//                    unset($redis_cmd_del);
//
//
//                } else {
//
//                    unset($mileage_data);
//
//                    $mileage_data = $l_data['mileage'];
//
//                    $e += 1;
//
//
//
//                }
//
//
//            }
//
//            unset($mileage_data);
//
//            unset($redis_cmd_llen);
//
//            unset($llen);
//
//        }
//
//
//        if($data_number>0){
//
//            echo "本次执行删除redis内重复mileage数据条数为：".$data_number.'条。';
//
//        }else{
//
//            echo "无重复数据删除！";
//
//        }
//
//    }







}