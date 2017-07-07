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


class FeeZuCheckCarStatusCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('auto:fee:zu:check:car:status')
            ->setDescription('Check the status of the car');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        //获取微租车设备号
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');
        $rentalCars =
            $qb
                ->select('c')
                ->join('c.deviceCompany ', 'p')
                ->where($qb->expr()->eq('p.englishName', ':name'))
                ->setParameter('name', 'feeZu')
                ->getQuery()
                ->getResult();

        //判断每个设备号状态是否正常 不正常的将车辆id存入数组
        foreach ($rentalCars as $rentalCar) {

            $car_status_data = $this->getContainer()->get('auto_manager.fee_zu_helper')->oneCarStatus
            ($rentalCar->getBoxId());

            if($car_status_data == 0){


                $fault_car_arr[]=$rentalCar->getId();

            }

        }

        //判断是否有故障设备
        if(!empty($fault_car_arr)){

            $redis = $this->getContainer()->get('snc_redis.default');

            $redis_cmd_data = $redis->createCommand('lindex',array('broken-rental-car',0));

            $amount_data = $redis->executeCommand($redis_cmd_data);

            $car_id_data = json_decode($amount_data,true);

            $arr_diff=array_diff($car_id_data,$car_id_data['ids']);

            if(!empty($arr_diff)){

                $total_arr=array_merge($arr_diff,$car_id_data['ids']);

                $fault_car_data['time'] = time();

                $fault_car_data['ids'] = $total_arr;

                $car_id_data_cmd = $redis->createCommand('lpush', array("broken-rental-car", json_encode
                ($fault_car_data)));

                $redis->executeCommand($car_id_data_cmd);
            }


        }
    }

}