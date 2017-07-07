<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/12/8
 * Time: 下午1:41
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshIllegalRecordCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:refresh:illegal')
            ->setDescription('auto refresh illegal')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {



        $soap = new \SoapClient('http://182.92.4.93:9090/Wservice4Client.asmx?WSDL');

        $u = new \SoapHeader('http://tempuri.org/','MySoapHeadder',array('UserName'=>'13331195120','CustomID'=>'F16BFEC483051F7314ACE144A22752F2'),true);

        $soap->__setSoapHeaders($u);

        $suc = $soap->GetCarWzInfromation();

        $result = json_decode($suc->GetCarWzInfromationResult,true) ;

        $illegals = ($result['mslist']);

        $man = $this->getContainer()->get('doctrine')->getManager();
        $illegal_list =

            $man
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->findAll();

        foreach($illegal_list as $i){

            $man->remove($i);

        }
        $man->flush();


        foreach($illegals as $illegal){
            $car_no = $illegal['carmodel']['carno'];

            echo substr($car_no,0,3).substr($car_no,3).PHP_EOL;
            $man = $this->getContainer()->get('doctrine')->getManager();
            $qb =
                $man
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c');
            $car =
                $qb
                ->select('c')
                ->join('c.licensePlace','p')
                ->where($qb->expr()->eq('c.licensePlate', ':plate'))
                ->andWhere($qb->expr()->eq('p.name', ':name'))
                ->setParameter('plate', substr($car_no,3))
                ->setParameter('name', substr($car_no,0,3))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            ;

            if(empty($car)){
                echo "不中";
                continue;
            }

            $times = [];
            foreach($illegal['peccancymodel'] as $model){


                $time = date('Y-m-d H:i:s',strtotime($model['time']));

                $order = new \Auto\Bundle\ManagerBundle\Entity\BaseOrder();

                if($time<$car->getCreateTime()->format('Y-m-d H:i:s')){

                    continue;

                }else{


                    $qb =
                        $man
                            ->getRepository('AutoManagerBundle:RentalOrder')
                            ->createQueryBuilder('o');
                    $order =
                        $qb
                            ->select('o')
                            ->where($qb->expr()->lte('o.useTime', ':time'))
                            ->andWhere($qb->expr()->gte('o.endTime', ':time'))
                            ->andWhere($qb->expr()->eq('o.rentalCar', ':car'))
                            ->setParameter('time', $time)
                            ->setParameter('car', $car)
                            ->setMaxResults(1)
                            ->getQuery()
                            ->getOneOrNullResult();
                    ;

                }
                $handled_illegals[] = ['time'=>$time,'car'=>$car];

                $times[] = $time;




                    $rental_illegal = new \Auto\Bundle\ManagerBundle\Entity\IllegalRecord();
                    $rental_illegal->setIllegalTime(new \DateTime($model['time']))
                        ->setIllegalPlace($model['location'])
                        ->setRentalCar($car)
                        ->setOrder($order)
                        ->setIllegalAmount($model['fine'])
                        ->setIllegalScore($model['marks'])
                        ->setIllegal($model['deed'])
                        ;

                    $man->persist($rental_illegal);
                    $man->flush();

                    if(!empty($order)){

                        $this->getContainer()->get("auto_manager.sms_helper")->add($order->getMember()->getMobile(),
                            '您在'.$rental_illegal->getIllegalTime()->format('Y年m月d日').'租赁'.$car_no.'期间产生违章，请于'.(new \DateTime())->modify("+4 day")->format('Y年m月d日').'前处理，逾期我们将不能为您提供租赁服务。');


                        $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
                        $message->setContent("您在".$rental_illegal->getIllegalTime()->format('Y年m月d日')."车辆租赁期间产生违章行为，请在3天内处理，逾期将不能为您提供车辆租赁服务。 ")
                            ->setKind(1)
                            ->setMember($order->getMember())
                            ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                        ;
                        $man->persist($message);
                        $man->flush();



                    }



            }



        }




    }
}