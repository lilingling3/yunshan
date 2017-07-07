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


class IllegalRecordCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('illegal:record:get')
            ->setDescription('type');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        // 对应城市的车 
        $man = $this->getContainer()->get('doctrine')->getManager();

        $qb =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $stations = [12,13,14];

        $rentalCars =
            $qb
                ->select('c')
                ->join('c.rentalStation', 'p')
                ->where($qb->expr()->in('p.area', $stations))
                ->setParameter('name', 'feeZu')
                ->getQuery()
                ->getResult();
        ;


        foreach($rentalCars as $rentalCar){

            echo $rentalCar->getLicense().PHP_EOL;

            // $res = $this->getContainer()->get('auto_manager.illegal_record_helper')->IllegalRecordQuery(
            //         '13188173381',
            //         '1',
            //         'guangzhou',
            //         $rentalCar->getLicensePlace()->getName().$rentalCar->getLicensePlate(),
            //         substr($rentalCar->getEngineNumber(), -6),
            //         $rentalCar->getChassisNumber());

            // // var_dump($res);


            // if ($res) {


            //     file_put_contents( '/data/www/data/car_ill.log', $res, FILE_APPEND);
            //     # code...
            // } 

        }


        
    }
}