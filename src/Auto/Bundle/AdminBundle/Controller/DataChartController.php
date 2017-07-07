<?php

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Process\Process;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Auto\Bundle\ManagerBundle\Form\RentalCarType;
use Doctrine\ORM\EntityManager;

/**
 * @Route("/dataChart")
 */

class DataChartController extends Controller
{
    /**
     * @Route("/operate", methods="GET", name="auto_admin_chart_operate")
     * @Template()
     */
    public function operateAction(Request $req)
    {
        date_default_timezone_set("PRC");
        $province=$req->query->get('province');
        $city=$req->query->get('city');
        $area=$req->query->get('area');
        $rental_station=$req->query->get('rental_station');
        $day=$req->query->get('day');
        $startTime = $req->query->get('start_time');
        $endTime = $req->query->get('end_time');
        $end_time = $endTime ? $endTime : date("Y-m-d H:i:s");
        $end_timestamp = (new \DateTime($end_time))->getTimestamp();
        if($day){
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *$day));
        }else{
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *1));
        }
        $cantons=null;
        $citys=null;
        $provinces=null;
        if($rental_station){
            //获取车
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.returnStation', 'op');
            $returnCars =
                $qb->select('o')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qb->expr()->eq('op.id', ':rentalStation'))
                    ->setParameter('rentalStation', $rental_station)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();
            $return_cars=[];$rental_car_number=0;
            if($returnCars){
                foreach($returnCars as $return){
                    $return_cars[$rental_car_number]=$return->getRentalCar();
                    $rental_car_number+=1;
                }
            }else{
                $return_cars=null;
            }
            $qbUp =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation', 'op');
            $PickUpCars =
                $qbUp->select('o')
                    ->where($qbUp->expr()->isNull('o.cancelTime'))
                    ->andWhere($qbUp->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qbUp->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qbUp->expr()->orX(
                        $qbUp->expr()->gte('o.endTime', ':startTime'),
                        $qbUp->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qbUp->expr()->eq('op.id', ':rentalStation'))
                    ->setParameter('rentalStation', $rental_station)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();

            $pick_cars=[];$pick_car_number=0;
            if($PickUpCars){
                foreach($PickUpCars as $pick){
                    $pick_cars[$pick_car_number]=$pick->getRentalCar();
                    $pick_car_number+=1;
                }
            }else{
                $pick_cars=null;
            }

            $qbcar =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c');

            $stationCars =
                $qbcar->select('c')
                    ->andWhere($qbcar->expr()->eq('c.rentalStation', ':rentalStation'))
                    ->setParameter('rentalStation', $rental_station)
                    ->getQuery()
                    ->getResult();
            if(count($return_cars)!=0||count($pick_cars)!=0||count($stationCars)!=0){
                if(count($return_cars)!=0){
                    $rentalCars=$return_cars;
                }
                if(count($pick_cars)!=0){
                    $rentalCars=$pick_cars;
                }
                if(count($stationCars)!=0){
                    $rentalCars=$stationCars;
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars);
                }
                if(count($return_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
                if(count($pick_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($pick_cars, $stationCars);
                   // $rentalCars=array_unique($rentalCarsOne);
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0&&count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars,$stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
            }else{
                $rentalCars=null;
            }
            //获取车结束

            $rentalStation= $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findOneBy(['id'=>$rental_station]);
            $Area= null;
            $City=null;
            $Province=  null;

            //全部省
            $provinces = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC']);
            $rentalStations = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findAll();

            //订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o');


            $rentalOrders =
                $qb
                    ->andWhere($qb->expr()->eq('o.pickUpStation',':pickUpStation'))
                    ->andwhere( $qb->expr()->isNull('o.cancelTime') )
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->setParameter('pickUpStation', $rental_station)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->orderBy('o.createTime','ASC')
                    ->getQuery()
                    ->getResult() ;

            //在库时长
            $car_count=[];$dispatch_count=[];$arr_index=[];$countTime=[];$count=[];
            if($rentalCars){
                $stay_time = [];
                $stay_time_dispatch=[];

                foreach($rentalCars as $car){
                    if(!empty($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($dispatch_count[$car->getId()])){
                        $dispatch_count[$car->getId()]=0;
                    }
                    if(!isset($car_count[$car->getId()])){
                        $car_count[$car->getId()]=0;
                    }
                    $car_count[$car->getId()]+=1;
                    $qb =
                        $this
                            ->getDoctrine()
                            ->getRepository('AutoManagerBundle:DispatchRentalCar')
                            ->createQueryBuilder('dr');
                    $dispatchs =
                        $qb
                            ->select('dr')
                            ->andWhere($qb->expr()->eq('dr.rentalCar', ':car'))
                            ->andWhere($qb->expr()->gte('dr.createTime', ':startTime'))
                            ->andWhere($qb->expr()->lte('dr.createTime', ':endTime'))
                            ->andWhere($qb->expr()->eq('dr.status', ':status'))
                            ->setParameter('status', 1)
                            ->setParameter('car', $car->getId())
                            ->setParameter('startTime', $start_time)
                            ->setParameter('endTime', $end_time)
                            ->orderBy('dr.createTime', 'ASC')
                            ->getQuery()
                            ->getResult();

                    $cars=array();$n=0;$time=[];$tid=null;$t=[];
                    if($dispatchs){
                        $count_dispatch=[];
                        $count[$car->getId()]+=1;

                        if(count($dispatchs)==1){
                            foreach($dispatchs as $dispatch){
                                if($dispatch->getRentalStation()->getId()==$rental_station){
                                    if($dispatch->getKind()==1){
                                        $t['startTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        $t['startTime']=$dispatch->getRentalOrder()->getEndTime();
                                    }
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                    $t['endTime']=$dispatchTime;
                                }
                                if($dispatch->getRentalStation()->getId()!=$rental_station){
                                    if($dispatch->getKind()==1){
                                        $t['endTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        if($dispatch->getRentalOrder()->getEndTime()){
                                            $t['endTime']=$dispatch->getRentalOrder()->getEndTime();
                                        }else{
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime;
                                        }

                                    }

                                    $dispatchTime=(new \DateTime($start_time));
                                    $t['startTime']=$dispatchTime;
                                }
                                $time[]=$t;
                                // var_dump($time);
                            }
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if($dispatch->getRentalStation()->getId()!=$rental_station){
                                    if (!isset($stay_time[$rental_station][$car->getId()])) {
                                        $stay_time[$rental_station][$car->getId()] = 0;
                                    }
                                    if(!empty($stay_time[$rental_station][$car->getId()])){
                                        $stay_time[$rental_station][$car->getId()]=0;
                                    }
                                    foreach($time as $tim) {
                                        if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                            $stay_time[$rental_station][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                    ->getTimestamp());
                                        }
                                    }
                                }else{
                                    if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    foreach($time as $tim){
                                        if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                    ->getTimestamp());
                                        }
                                        // echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                        $stay_time=$stay_time_dispatch;
                                    }
                                }


                            }
                        }else{
                            if(!isset($count_dispatch[$rental_station][$car->getId()])){
                                $count_dispatch[$rental_station][$car->getId()]=0;
                            }
                            if(!empty($count_dispatch[$rental_station][$car->getId()])){
                                $count_dispatch[$rental_station][$car->getId()]=0;
                            }
                            foreach($dispatchs as $dispatch){
                                $cars[$n]=$dispatch;
                                $n+=1;
                                if(!isset($count_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $count_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                $count_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                            }
//                        echo '$count_dispatch'.var_dump($count_dispatch);
                            if($count_dispatch[$rental_station][$car->getId()]==1){
                                foreach($dispatchs as $dispatch){
                                    $cars[$n]=$dispatch;
                                    $n+=1;
                                }
//                            echo 'count($dispatchs)'.count($dispatchs);
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(($rental_station==$cars[$i]->getRentalStation()->getId())&&($car->getId()==$cars[$i]->getRentalCar()->getId())){
//                                    echo 'test序号'.$i.'RentalStation'.$cars[$i]->getRentalStation()->getId();
                                        if($i==0){
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }
                                            }
//                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
//                                        $t['endTime']=$dispatchTime;

//                                        echo 'one';

                                        }else if($i==(count($dispatchs)-1)){
                                            if($cars[$i]->getKind()==1){
                                                if($cars[$i+1]){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==2){
                                                if($cars[$i]->getRentalOrder()->getEndTime()){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==1){
                                                // $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                $t['startTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                //  $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
//                                        $dispatchTimeTwo=(new \DateTime($start_time));
//                                        $t['startTime']=$dispatchTimeTwo;
//                                        echo 'two';

                                        }else{
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }

                                            }

                                        }

                                        $time[]=$t;
                                    }
//echo '$time'.var_dump($time);
                                }

                                foreach($dispatchs as $dispatch){
                                    $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                    if($dispatch->getRentalStation()->getId()!=$rental_station){
                                        if (!isset($stay_time[$rental_station][$car->getId()])) {
                                            $stay_time[$rental_station][$car->getId()] = 0;
                                        }
                                        if(!empty($stay_time[$rental_station][$car->getId()])){
                                            $stay_time[$rental_station][$car->getId()]=0;
                                        }
                                        foreach($time as $tim) {
                                            if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                                $stay_time[$rental_station][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                        ->getTimestamp());
                                            }
                                        }
                                    }else{
                                        if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        foreach($time as $tim){
                                            if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                            }
                                            if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                        ->getTimestamp());
                                            }


                                            $stay_time=$stay_time_dispatch;
                                        }
                                    }


                                }

                            }else
                            {
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(isset($t['endTime'])){
                                        $t=[];
                                    }
                                    if(!empty($arr_index)){
                                        foreach($arr_index as $index){
                                            if($i!=$index){
                                                if($cars[$i]->getRentalStation()->getId()==$rental_station){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }

                                                }
                                            }else if($i==$index&&$cars[$i-1]->getRentalStation()->getId()!=$rental_station){
                                                if($cars[$i]->getRentalStation()->getId()==$rental_station){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }
                                                    if(($i+1)==count($dispatchs)){
                                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                        $t['endTime']=$dispatchTime;
                                                        $time[]=$t;
                                                        break;
                                                    }

                                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                                        if($cars[$j]->getRentalStation()->getId()==$rental_station){
                                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                            $t['endTime']=$dispatchTime;
                                                            $time[]=$t;
                                                            //    echo 'endTime'.var_dump($t['endTime']);

                                                        }else{
                                                            if($cars[$j]->getKind()==1){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }else if($cars[$j]->getKind()==2){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                if($cars[$j]->getRentalOrder()->getEndTime()){
                                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                                }else{
                                                                    $t['endTime']=$dispatchTime;

                                                                }
                                                                $time[]=$t;
                                                                break;
                                                            }else{
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }
                                                        }}
                                                }
                                                //   echo '$tstartTime'.var_dump($t);

                                            }else if(($i==$index&&$cars[$i]->getRentalStation()->getId()==$rental_station)){
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                $t['endTime']=$dispatchTime;
                                                $time[]=$t;
                                                break;

                                            }
                                        }
                                    }else{
                                        if($cars[$i]->getRentalStation()->getId()==$rental_station){
                                            if($i==0){
                                                if($cars[$i]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?new \DateTime():(new \DateTime($start_time));
                                                    $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getCreateTime()->getTimestamp()?$cars[$i]->getCreateTime():$dispatchTime;
                                                    //  echo '$t["startTime"]'.var_dump($t['startTime']);
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                                    if($cars[$i]->getRentalOrder()->getEndTime()) {
                                                        $t['startTime'] = $dispatchTime->getTimestamp() > $cars[$i]->getRentalOrder()->getEndTime()->getTimestamp() ?$cars[$i]->getRentalOrder()->getEndTime()  :$dispatchTime ;
                                                    }else{
                                                        $t['startTime']=$dispatchTime;
                                                    }
                                                }
                                            }else{
                                                if($cars[$i]->getKind()==1){
                                                    $t['startTime']=$cars[$i]->getCreateTime();
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                }
                                            }
                                        }
                                    }
                                    if(($i+1)==count($dispatchs)){
                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                        $t['endTime']=$dispatchTime;
                                        $time[]=$t;
                                        break;
                                    }
                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                        if($cars[$j]->getRentalStation()->getId()==$rental_station){
                                            //array_push($arr_index,$j);
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }

                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                        if($cars[$j]->getRentalStation()->getId()!=$rental_station){
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                    }

                                }}
//                       echo 'Time'.var_dump($time);
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if(!isset($countTime[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $countTime[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;

                                }
                                foreach($time as $tim){

                                    if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                ->getTimestamp());
                                    }
                                    // echo '$stay_time_dispatch'.var_dump($stay_time_dispatch);
                                    if($dispatch->getRentalStation()->getId()==$rental_station&&($countTime[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]+1)==count($time)){
                                        $stay_time=$stay_time_dispatch;
                                    }

                                    $countTime[$dispatch->getRentalStation()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                                }

                            }
                        }
                    }else{
                        $stay_start = $car->getCreateTime()->format('Y-m-d H:i:s')>$start_time?$car->getCreateTime()->format
                        ('Y-m-d H:i:s'):$start_time;
                        $stay_end = (new \DateTime())->format('Y-m-d H:i:s')>$end_time?
                            $end_time:(new \DateTime())->format('Y-m-d  H:i:s');
                        if(!isset($stay_time[$car->getRentalStation()->getId()][$car->getId()])){
                            $stay_time[$car->getRentalStation()->getId()][$car->getId()]=0;
                        }

                        $stay_time[$car->getRentalStation()->getId()][$car->getId()] = (new \DateTime($stay_end))
                                ->getTimestamp() -(new \DateTime($stay_start))
                                ->getTimestamp();
                        /* echo 'test'.((new \DateTime('2016-04-13  13:48:52'))->getTimestamp() -(new \DateTime('2016-04-06 09:04:39'))->getTimestamp());
                         echo '$stay_start'.$stay_start;
                         echo '$stay_end'.$stay_end;
                         echo '$stay_time'.var_dump($stay_time);*/
                    }


                }
                $car_count_data=null;
            }else{
                $stay_time=null;
            }
        }elseif($area){
            //获取车
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.returnStation', 'op');
            $returnCars =
                $qb->select('o')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qb->expr()->eq('op.area',':area') )
                    ->setParameter('area', $area)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();
            $return_cars=[];$rental_car_number=0;
            if($returnCars){
                foreach($returnCars as $return){
                    $return_cars[$rental_car_number]=$return->getRentalCar();
                    $rental_car_number+=1;
                }
            }else{
                $return_cars=null;
            }
            $qbUp =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation', 'op');
            $PickUpCars =
                $qbUp
                    ->where($qbUp->expr()->isNull('o.cancelTime'))
                    ->andWhere($qbUp->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qbUp->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qbUp->expr()->orX(
                        $qbUp->expr()->gte('o.endTime', ':startTime'),
                        $qbUp->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qb->expr()->eq('op.area',':area') )
                    ->setParameter('area', $area)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();

            $pick_cars=[];$pick_car_number=0;
            if($PickUpCars){
                foreach($PickUpCars as $pick){
                    $pick_cars[$pick_car_number]=$pick->getRentalCar();
                    $pick_car_number+=1;
                }
            }else{
                $pick_cars=null;
            }
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.rentalStation','rs');
            $stationCars=
                $qb
                    ->where($qb->expr()->eq('rs.area',':area') )
                    ->setParameter('area', $area)
                    ->getQuery()
                    ->getResult() ;
            if(count($return_cars)!=0||count($pick_cars)!=0||count($stationCars)!=0){
                if(count($return_cars)!=0){
                    $rentalCars=$return_cars;
                }
                if(count($pick_cars)!=0){
                    $rentalCars=$pick_cars;
                }
                if(count($stationCars)!=0){
                    $rentalCars=$stationCars;
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars);
                }
                if(count($return_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
                if(count($pick_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($pick_cars, $stationCars);
                    // $rentalCars=array_unique($rentalCarsOne);
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0&&count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars,$stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
            }else{
                $rentalCars=null;
            }
            //获取车结束


            $rentalStation=null;
            $City=null;
            $Province=  null;
            $Area= $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:area')
                ->findOneBy(['id'=>$area]);

            //全部省
            $provinces = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC']);
            $rentalStations = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findBy(['area' => $area]);

            //订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation','ps');

            $rentalOrders =
                $qb
                    ->andWhere($qb->expr()->eq('ps.area',':area'))
                    ->andwhere( $qb->expr()->isNull('o.cancelTime') )
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->setParameter('area', $area)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->orderBy('o.createTime','ASC')
                    ->getQuery()
                    ->getResult() ;

            //在库时长
            $car_count=[];$dispatch_count=[];$arr_index=[];$countTime=[];$count=[];
            if($rentalCars){
                $stay_time = [];
                $stay_time_dispatch=[];

                foreach($rentalCars as $car){
                    if(!empty($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($dispatch_count[$car->getId()])){
                        $dispatch_count[$car->getId()]=0;
                    }
                    if(!isset($car_count[$car->getId()])){
                        $car_count[$car->getId()]=0;
                    }
                    $car_count[$car->getId()]+=1;
                    $qb =
                        $this
                            ->getDoctrine()
                            ->getRepository('AutoManagerBundle:DispatchRentalCar')
                            ->createQueryBuilder('dr');
                    $dispatchs =
                        $qb
                            ->select('dr')
                            ->andWhere($qb->expr()->eq('dr.rentalCar', ':car'))
                            ->andWhere($qb->expr()->gte('dr.createTime', ':startTime'))
                            ->andWhere($qb->expr()->lte('dr.createTime', ':endTime'))
                            ->andWhere($qb->expr()->eq('dr.status', ':status'))
                            ->setParameter('status', 1)
                            ->setParameter('car', $car->getId())
                            ->setParameter('startTime', $start_time)
                            ->setParameter('endTime', $end_time)
                            ->orderBy('dr.createTime', 'ASC')
                            ->getQuery()
                            ->getResult();

                    $cars=array();$n=0;$time=[];$tid=null;$t=[];
                    if($dispatchs){
                        $count_dispatch=[];
                        $count[$car->getId()]+=1;

                        if(count($dispatchs)==1){
                            foreach($dispatchs as $dispatch){
                                if($dispatch->getRentalStation()->getArea()->getId()==$area){
                                    if($dispatch->getKind()==1){
                                        $t['startTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        $t['startTime']=$dispatch->getRentalOrder()->getEndTime();
                                    }
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                    $t['endTime']=$dispatchTime;
                                }
                                if($dispatch->getRentalStation()->getArea()->getId()!=$area){
                                    if($dispatch->getKind()==1){
                                        $t['endTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        if($dispatch->getRentalOrder()->getEndTime()){
                                            $t['endTime']=$dispatch->getRentalOrder()->getEndTime();
                                        }else{
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime;
                                        }

                                    }

                                    $dispatchTime=(new \DateTime($start_time));
                                    $t['startTime']=$dispatchTime;
                                }
                                $time[]=$t;
                                // var_dump($time);
                            }
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if($dispatch->getRentalStation()->getArea()->getId()!=$area){
                                    if (!isset($stay_time[$area][$car->getId()])) {
                                        $stay_time[$area][$car->getId()] = 0;
                                    }
                                    if(!empty($stay_time[$area][$car->getId()])){
                                        $stay_time[$area][$car->getId()]=0;
                                    }
                                    foreach($time as $tim) {
                                        if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                            $stay_time[$area][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                    ->getTimestamp());
                                        }
                                    }
                                }else{
                                    if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    foreach($time as $tim){
                                        if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                    ->getTimestamp());
                                        }
                                        // echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                        $stay_time=$stay_time_dispatch;
                                    }
                                }


                            }
                        }else{
                            if(!isset($count_dispatch[$area][$car->getId()])){
                                $count_dispatch[$area][$car->getId()]=0;
                            }
                            if(!empty($count_dispatch[$area][$car->getId()])){
                                $count_dispatch[$area][$car->getId()]=0;
                            }
                            foreach($dispatchs as $dispatch){
                                $cars[$n]=$dispatch;
                                $n+=1;
                                if(!isset($count_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $count_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                $count_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                            }
//                        echo '$count_dispatch'.var_dump($count_dispatch);
                            if($count_dispatch[$area][$car->getId()]==1){
                                foreach($dispatchs as $dispatch){
                                    $cars[$n]=$dispatch;
                                    $n+=1;
                                }
//                            echo 'count($dispatchs)'.count($dispatchs);
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(($area==$cars[$i]->getRentalStation()->getArea()->getId())&&($car->getId()==$cars[$i]->getRentalCar()->getId())){
//                                    echo 'test序号'.$i.'RentalStation'.$cars[$i]->getRentalStation()->getId();
                                        if($i==0){
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }
                                            }
//                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
//                                        $t['endTime']=$dispatchTime;

//                                        echo 'one';

                                        }else if($i==(count($dispatchs)-1)){
                                            if($cars[$i]->getKind()==1){
                                                if($cars[$i+1]){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==2){
                                                if($cars[$i]->getRentalOrder()->getEndTime()){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==1){
                                                // $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                $t['startTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                //  $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }

                                        }else{
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }

                                            }

                                        }

                                        $time[]=$t;
                                    }
//echo '$time'.var_dump($time);
                                }
                                foreach($dispatchs as $dispatch){
                                    $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                    if($dispatch->getRentalStation()->getArea()->getId()!=$area){
                                        if (!isset($stay_time[$area][$car->getId()])) {
                                            $stay_time[$area][$car->getId()] = 0;
                                        }
                                        if(!empty($stay_time[$area][$car->getId()])){
                                            $stay_time[$area][$car->getId()]=0;
                                        }
                                        foreach($time as $tim) {
                                            if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                                $stay_time[$area][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                        ->getTimestamp());
                                            }
                                        }
                                    }else{
                                        if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        foreach($time as $tim){
                                            if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                            }
                                            if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                        ->getTimestamp());
                                            }
                                            //   echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                            $stay_time=$stay_time_dispatch;
                                        }
                                    }


                                }

                            }else
                            {
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(isset($t['endTime'])){
                                        $t=[];
                                    }
                                    if(!empty($arr_index)){
                                        foreach($arr_index as $index){
                                            if($i!=$index){
                                                if($cars[$i]->getRentalStation()->getArea()->getId()==$area){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }

                                                }
                                            }else if($i==$index&&$cars[$i-1]->getRentalStation()->getArea()->getId()!=$area){
                                                if($cars[$i]->getRentalStation()->getArea()->getId()==$area){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }
                                                    if(($i+1)==count($dispatchs)){
                                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                        $t['endTime']=$dispatchTime;
                                                        $time[]=$t;
                                                        break;
                                                    }

                                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                                        if($cars[$j]->getRentalStation()->getArea()->getId()==$area){
                                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                            $t['endTime']=$dispatchTime;
                                                            $time[]=$t;
                                                            //    echo 'endTime'.var_dump($t['endTime']);

                                                        }else{
                                                            if($cars[$j]->getKind()==1){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }else if($cars[$j]->getKind()==2){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                if($cars[$j]->getRentalOrder()->getEndTime()){
                                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                                }else{
                                                                    $t['endTime']=$dispatchTime;

                                                                }
                                                                $time[]=$t;
                                                                break;
                                                            }else{
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }
                                                        }}
                                                }
                                                //   echo '$tstartTime'.var_dump($t);

                                            }else if(($i==$index&&$cars[$i]->getRentalStation()->getArea()->getId()==$area)){
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                $t['endTime']=$dispatchTime;
                                                $time[]=$t;
                                                break;

                                            }
                                        }
                                    }else{
                                        if($cars[$i]->getRentalStation()->getArea()->getId()==$area){
                                            if($i==0){
                                                if($cars[$i]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?new \DateTime():(new \DateTime($start_time));
                                                    $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getCreateTime()->getTimestamp()?$cars[$i]->getCreateTime():$dispatchTime;
                                                    //  echo '$t["startTime"]'.var_dump($t['startTime']);
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                                    if($cars[$i]->getRentalOrder()->getEndTime()) {
                                                        $t['startTime'] = $dispatchTime->getTimestamp() > $cars[$i]->getRentalOrder()->getEndTime()->getTimestamp() ?$cars[$i]->getRentalOrder()->getEndTime()  :$dispatchTime ;
                                                    }else{
                                                        $t['startTime']=$dispatchTime;
                                                    }
                                                }
                                            }else{
                                                if($cars[$i]->getKind()==1){
                                                    $t['startTime']=$cars[$i]->getCreateTime();
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                }
                                            }
                                        }
                                    }
                                    if(($i+1)==count($dispatchs)){
                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                        $t['endTime']=$dispatchTime;
                                        $time[]=$t;
                                        break;
                                    }
                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                        if($cars[$j]->getRentalStation()->getArea()->getId()==$area){
                                            //array_push($arr_index,$j);
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }

                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                        if($cars[$j]->getRentalStation()->getArea()->getId()!=$area){
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                    }

                                }}
//                       echo 'Time'.var_dump($time);
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if(!isset($countTime[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $countTime[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;

                                }
                                foreach($time as $tim){

                                    if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                ->getTimestamp());
                                    }
                                    // echo '$stay_time_dispatch'.var_dump($stay_time_dispatch);
                                    if($dispatch->getRentalStation()->getArea()->getId()==$area&&($countTime[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]+1)==count($time)){
                                        $stay_time=$stay_time_dispatch;
                                    }

                                    $countTime[$dispatch->getRentalStation()->getArea()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                                }

                            }
                        }
                    }else{
                        $stay_start = $car->getCreateTime()->format('Y-m-d H:i:s')>$start_time?$car->getCreateTime()->format
                        ('Y-m-d H:i:s'):$start_time;
                        $stay_end = (new \DateTime())->format('Y-m-d H:i:s')>$end_time?
                            $end_time:(new \DateTime())->format('Y-m-d  H:i:s');
                        if(!isset($stay_time[$car->getRentalStation()->getArea()->getParent()->getId()][$car->getId()])){
                            $stay_time[$car->getRentalStation()->getArea()->getParent()->getId()][$car->getId()]=0;
                        }

                        $stay_time[$car->getRentalStation()->getArea()->getParent()->getId()][$car->getId()] = (new \DateTime($stay_end))
                                ->getTimestamp() -(new \DateTime($stay_start))
                                ->getTimestamp();
                        /* echo 'test'.((new \DateTime('2016-04-13  13:48:52'))->getTimestamp() -(new \DateTime('2016-04-06 09:04:39'))->getTimestamp());
                         echo '$stay_start'.$stay_start;
                         echo '$stay_end'.$stay_end;
                         echo '$stay_time'.var_dump($stay_time);*/
                    }


                }
                $car_count_data=null;
            }else{
                $stay_time=null;
            }

        }elseif($city){
            //获取车
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.returnStation', 'op');
            $returnCars =
                $qb ->join('op.area','ar')
                    ->where($qb->expr()->eq('ar.parent',':city') )
                    ->andWhere($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('city', $city)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();
            $return_cars=[];$rental_car_number=0;
            if($returnCars){
                foreach($returnCars as $return){
                    $return_cars[$rental_car_number]=$return->getRentalCar();
                    $rental_car_number+=1;
                }
            }else{
                $return_cars=null;
            }
            $qbUp =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation', 'op');
            $PickUpCars =
                $qbUp->join('op.area','ar')
                    ->where($qbUp->expr()->eq('ar.parent',':city') )
                    ->andWhere($qbUp->expr()->isNull('o.cancelTime'))
                    ->andWhere($qbUp->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qbUp->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qbUp->expr()->orX(
                        $qbUp->expr()->gte('o.endTime', ':startTime'),
                        $qbUp->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('city', $city)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();

            $pick_cars=[];$pick_car_number=0;
            if($PickUpCars){
                foreach($PickUpCars as $pick){
                    $pick_cars[$pick_car_number]=$pick->getRentalCar();
                    $pick_car_number+=1;
                }
            }else{
                $pick_cars=null;
            }

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.rentalStation','rs');
            $stationCars=
                $qb->join('rs.area','ar')
                    ->where($qb->expr()->eq('ar.parent',':city') )
                    ->setParameter('city', $city)
                    ->getQuery()
                    ->getResult() ;

            if(count($return_cars)!=0||count($pick_cars)!=0||count($stationCars)!=0){
                if(count($return_cars)!=0){
                    $rentalCars=$return_cars;
                }
                if(count($pick_cars)!=0){
                    $rentalCars=$pick_cars;
                }
                if(count($stationCars)!=0){
                    $rentalCars=$stationCars;
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars);
                }
                if(count($return_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
                if(count($pick_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($pick_cars, $stationCars);
                    // $rentalCars=array_unique($rentalCarsOne);
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0&&count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars,$stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
            }else{
                $rentalCars=null;
            }
            //获取车结束

            $rentalStation=null;
            $Area= null;
            $City=$this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:area')
                ->findOneBy(['id'=>$city]);
            $Province=  null;

            //全部城区
            $cantons = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => $city], ['id' => 'ASC']);

            $qbrs =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->createQueryBuilder('rs');
            $rentalStations =
                $qbrs
                    ->select('rs')
                    ->join('rs.area','ar')
                    ->andWhere($qb->expr()->eq('ar.parent',':city'))
                    ->setParameter('city', $city)
                    ->getQuery()
                    ->getResult() ;
            //全部省
            $provinces = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC']);

            //订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->join('ps.area','ar');

            $rentalOrders =
                $qb
                    //->join('ar.parent','ct')
                   // ->join('ct.parent','pr')
                    ->andWhere($qb->expr()->eq('ar.parent',':city'))
                    ->andwhere( $qb->expr()->isNull('o.cancelTime') )
                   // ->andwhere( $qb->expr()->isNotNull('o.endTime') )
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime',':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('city', $city)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->orderBy('o.createTime','ASC')
                    ->getQuery()
                    ->getResult() ;

            //在库时长
            $car_count=[];$dispatch_count=[];$arr_index=[];$countTime=[];$count=[];
            if($rentalCars){
                $stay_time = [];
                $stay_time_dispatch=[];

                foreach($rentalCars as $car){
                    if(!empty($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($dispatch_count[$car->getId()])){
                        $dispatch_count[$car->getId()]=0;
                    }
                    if(!isset($car_count[$car->getId()])){
                        $car_count[$car->getId()]=0;
                    }
                    $car_count[$car->getId()]+=1;
                    $qb =
                        $this
                            ->getDoctrine()
                            ->getRepository('AutoManagerBundle:DispatchRentalCar')
                            ->createQueryBuilder('dr');
                    $dispatchs =
                        $qb
                            ->select('dr')
                            ->andWhere($qb->expr()->eq('dr.rentalCar', ':car'))
                            ->andWhere($qb->expr()->gte('dr.createTime', ':startTime'))
                            ->andWhere($qb->expr()->lte('dr.createTime', ':endTime'))
                            ->andWhere($qb->expr()->eq('dr.status', ':status'))
                            ->setParameter('status', 1)
                            ->setParameter('car', $car->getId())
                            ->setParameter('startTime', $start_time)
                            ->setParameter('endTime', $end_time)
                            ->orderBy('dr.createTime', 'ASC')
                            ->getQuery()
                            ->getResult();

                    $cars=array();$n=0;$time=[];$tid=null;$t=[];
                    if($dispatchs){
                        $count_dispatch=[];
                        $count[$car->getId()]+=1;

                        if(count($dispatchs)==1){
                            foreach($dispatchs as $dispatch){
                                if($dispatch->getRentalStation()->getArea()->getParent()->getId()==$city){
                                    if($dispatch->getKind()==1){
                                        $t['startTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        $t['startTime']=$dispatch->getRentalOrder()->getEndTime();
                                    }
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                    $t['endTime']=$dispatchTime;
                                }
                                if($dispatch->getRentalStation()->getArea()->getParent()->getId()!=$city){
                                    if($dispatch->getKind()==1){
                                        $t['endTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        if($dispatch->getRentalOrder()->getEndTime()){
                                            $t['endTime']=$dispatch->getRentalOrder()->getEndTime();
                                        }else{
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime;
                                        }

                                    }

                                    $dispatchTime=(new \DateTime($start_time));
                                    $t['startTime']=$dispatchTime;
                                }
                                $time[]=$t;
                                // var_dump($time);
                            }
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if($dispatch->getRentalStation()->getArea()->getParent()->getId()!=$city){
                                    if (!isset($stay_time[$city][$car->getId()])) {
                                        $stay_time[$city][$car->getId()] = 0;
                                    }
                                    if(!empty($stay_time[$city][$car->getId()])){
                                        $stay_time[$city][$car->getId()]=0;
                                    }
                                    foreach($time as $tim) {
                                        if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                            $stay_time[$city][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                    ->getTimestamp());
                                        }
                                    }
                                }else{
                                    if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    foreach($time as $tim){
                                        if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                    ->getTimestamp());
                                        }
                                        // echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                        $stay_time=$stay_time_dispatch;
                                    }
                                }


                            }
                        }else{
                            if(!isset($count_dispatch[$city][$car->getId()])){
                                $count_dispatch[$city][$car->getId()]=0;
                            }
                            if(!empty($count_dispatch[$city][$car->getId()])){
                                $count_dispatch[$city][$car->getId()]=0;
                            }
                            foreach($dispatchs as $dispatch){
                                $cars[$n]=$dispatch;
                                $n+=1;
                                if(!isset($count_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $count_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                $count_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                            }
//                        echo '$count_dispatch'.var_dump($count_dispatch);
                            if($count_dispatch[$city][$car->getId()]==1){
                                foreach($dispatchs as $dispatch){
                                    $cars[$n]=$dispatch;
                                    $n+=1;
                                }
//                            echo 'count($dispatchs)'.count($dispatchs);
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(($city==$cars[$i]->getRentalStation()->getArea()->getParent()->getId())&&($car->getId()==$cars[$i]->getRentalCar()->getId())){
//                                    echo 'test序号'.$i.'RentalStation'.$cars[$i]->getRentalStation()->getId();
                                        if($i==0){
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }
                                            }
//                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
//                                        $t['endTime']=$dispatchTime;

//                                        echo 'one';

                                        }else if($i==(count($dispatchs)-1)){
                                            if($cars[$i]->getKind()==1){
                                                if($cars[$i+1]){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==2){
                                                if($cars[$i]->getRentalOrder()->getEndTime()){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==1){
                                                // $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                $t['startTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                //  $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }

                                        }else{
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }

                                            }

                                        }

                                        $time[]=$t;
                                    }
//echo '$time'.var_dump($time);
                                }
                                foreach($dispatchs as $dispatch){
                                    $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                    if($dispatch->getRentalStation()->getArea()->getParent()->getId()!=$city){
                                        if (!isset($stay_time[$city][$car->getId()])) {
                                            $stay_time[$city][$car->getId()] = 0;
                                        }
                                        if(!empty($stay_time[$city][$car->getId()])){
                                            $stay_time[$city][$car->getId()]=0;
                                        }
                                        foreach($time as $tim) {
                                            if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                                $stay_time[$city][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                        ->getTimestamp());
                                            }
                                        }
                                    }else{
                                        if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        foreach($time as $tim){
                                            if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                            }
                                            if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                        ->getTimestamp());
                                            }
                                            //   echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                            $stay_time=$stay_time_dispatch;
                                        }
                                    }


                                }

                            }else
                            {
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(isset($t['endTime'])){
                                        $t=[];
                                    }
                                    if(!empty($arr_index)){
                                        foreach($arr_index as $index){
                                            if($i!=$index){
                                                if($cars[$i]->getRentalStation()->getArea()->getParent()->getId()==$city){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }

                                                }
                                            }else if($i==$index&&$cars[$i-1]->getRentalStation()->getArea()->getParent()->getId()!=$city){
                                                if($cars[$i]->getRentalStation()->getArea()->getParent()->getId()==$city){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }
                                                    if(($i+1)==count($dispatchs)){
                                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                        $t['endTime']=$dispatchTime;
                                                        $time[]=$t;
                                                        break;
                                                    }

                                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                                        if($cars[$j]->getRentalStation()->getArea()->getParent()->getId()==$city){
                                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                            $t['endTime']=$dispatchTime;
                                                            $time[]=$t;
                                                            //    echo 'endTime'.var_dump($t['endTime']);

                                                        }else{
                                                            if($cars[$j]->getKind()==1){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }else if($cars[$j]->getKind()==2){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                if($cars[$j]->getRentalOrder()->getEndTime()){
                                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                                }else{
                                                                    $t['endTime']=$dispatchTime;

                                                                }
                                                                $time[]=$t;
                                                                break;
                                                            }else{
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }
                                                        }}
                                                }
                                                //   echo '$tstartTime'.var_dump($t);

                                            }else if(($i==$index&&$cars[$i]->getRentalStation()->getArea()->getParent()->getId()==$city)){
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                $t['endTime']=$dispatchTime;
                                                $time[]=$t;
                                                break;

                                            }
                                        }
                                    }else{
                                        if($cars[$i]->getRentalStation()->getArea()->getParent()->getId()==$city){
                                            if($i==0){
                                                if($cars[$i]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?new \DateTime():(new \DateTime($start_time));
                                                    $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getCreateTime()->getTimestamp()?$cars[$i]->getCreateTime():$dispatchTime;
                                                    //  echo '$t["startTime"]'.var_dump($t['startTime']);
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                                    if($cars[$i]->getRentalOrder()->getEndTime()) {
                                                        $t['startTime'] = $dispatchTime->getTimestamp() > $cars[$i]->getRentalOrder()->getEndTime()->getTimestamp() ?$cars[$i]->getRentalOrder()->getEndTime()  :$dispatchTime ;
                                                    }else{
                                                        $t['startTime']=$dispatchTime;
                                                    }
                                                }
                                            }else{
                                                if($cars[$i]->getKind()==1){
                                                    $t['startTime']=$cars[$i]->getCreateTime();
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                }
                                            }
                                        }
                                    }
                                    if(($i+1)==count($dispatchs)){
                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                        $t['endTime']=$dispatchTime;
                                        $time[]=$t;
                                        break;
                                    }
                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                        if($cars[$j]->getRentalStation()->getArea()->getParent()->getId()==$city){
                                            //array_push($arr_index,$j);
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }

                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                        if($cars[$j]->getRentalStation()->getArea()->getParent()->getId()!=$city){
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                    }

                                }}
//                       echo 'Time'.var_dump($time);
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if(!isset($countTime[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $countTime[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;

                                }
                                foreach($time as $tim){

                                    if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                ->getTimestamp());
                                    }
                                    // echo '$stay_time_dispatch'.var_dump($stay_time_dispatch);
                                    if($dispatch->getRentalStation()->getArea()->getParent()->getId()==$city&&($countTime[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]+1)==count($time)){
                                        $stay_time=$stay_time_dispatch;
                                    }

                                    $countTime[$dispatch->getRentalStation()->getArea()->getParent()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                                }

                            }
                        }
                    }else{
                        $stay_start = $car->getCreateTime()->format('Y-m-d H:i:s')>$start_time?$car->getCreateTime()->format
                        ('Y-m-d H:i:s'):$start_time;
                        $stay_end = (new \DateTime())->format('Y-m-d H:i:s')>$end_time?
                            $end_time:(new \DateTime())->format('Y-m-d  H:i:s');
                        if(!isset($stay_time[$car->getRentalStation()->getArea()->getParent()->getId()][$car->getId()])){
                            $stay_time[$car->getRentalStation()->getArea()->getParent()->getId()][$car->getId()]=0;
                        }

                        $stay_time[$car->getRentalStation()->getArea()->getParent()->getId()][$car->getId()] = (new \DateTime($stay_end))
                                ->getTimestamp() -(new \DateTime($stay_start))
                                ->getTimestamp();
                        /* echo 'test'.((new \DateTime('2016-04-13  13:48:52'))->getTimestamp() -(new \DateTime('2016-04-06 09:04:39'))->getTimestamp());
                         echo '$stay_start'.$stay_start;
                         echo '$stay_end'.$stay_end;
                         echo '$stay_time'.var_dump($stay_time);*/
                    }


                }
                $car_count_data=null;
            }else{
                $stay_time=null;
            }



        }elseif($province){
            //获取车
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.returnStation', 'op');
            $returnCars =
                $qb ->join('op.area','ar')
                    ->join('ar.parent','p')
                    ->where($qb->expr()->eq('p.parent',':province') )
                    ->andWhere($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('province', $province)
                    ->setParameter('endTime', $end_time)
                    ->setParameter('startTime', $start_time)
                    ->getQuery()
                    ->getResult();
            $return_cars=[];$rental_car_number=0;
            if($returnCars){
                foreach($returnCars as $return){
                    $return_cars[$rental_car_number]=$return->getRentalCar();
                    $rental_car_number+=1;
                }
            }else{
                $return_cars=null;
            }
            $qbUp =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation', 'op');
            $PickUpCars =
                $qbUp->join('op.area','ar')
                    ->join('ar.parent','p')
                    ->where($qbUp->expr()->eq('p.parent',':province') )
                    ->andWhere($qbUp->expr()->isNull('o.cancelTime'))
                    ->andWhere($qbUp->expr()->gte('o.createTime', ':startTime'))
                    ->andWhere($qbUp->expr()->lte('o.createTime', ':endTime'))
                    ->andWhere($qbUp->expr()->orX(
                        $qbUp->expr()->gte('o.endTime', ':startTime'),
                        $qbUp->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('province', $province)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult();

            $pick_cars=[];$pick_car_number=0;
            if($PickUpCars){
                foreach($PickUpCars as $pick){
                    $pick_cars[$pick_car_number]=$pick->getRentalCar();
                    $pick_car_number+=1;
                }
            }else{
                $pick_cars=null;
            }

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.rentalStation','rs');
            $stationCars=
                $qb->join('rs.area','ar')
                    ->join('ar.parent','p')
                    ->where($qb->expr()->eq('p.parent',':province') )
                    ->setParameter('province', $province)
                    ->getQuery()
                    ->getResult() ;

            if(count($return_cars)!=0||count($pick_cars)!=0||count($stationCars)!=0){
                if(count($return_cars)!=0){
                    $rentalCars=$return_cars;
                }
                if(count($pick_cars)!=0){
                    $rentalCars=$pick_cars;
                }
                if(count($stationCars)!=0){
                    $rentalCars=$stationCars;
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars);
                }
                if(count($return_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
                if(count($pick_cars)!=0 && count($stationCars)!=0){
                    $rentalCars=array_merge ($pick_cars, $stationCars);
                    // $rentalCars=array_unique($rentalCarsOne);
                }
                if(count($return_cars)!=0 && count($pick_cars)!=0&&count($stationCars)!=0){
                    $rentalCars=array_merge ($return_cars, $pick_cars,$stationCars);
                    //$rentalCars=array_unique($rentalCarsOne);
                }
            }else{
                $rentalCars=null;
            }
            //获取车结束


            //订单
            $qbor =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o')
                    ->select('o')
                    ->join('o.pickUpStation','ps');

            $rentalOrders =
                $qbor ->join('ps.area','ar')
                    ->join('ar.parent','ct')
                    ->andWhere($qbor->expr()->eq('ct.parent',':province'))
                    //->andwhere( $qb->expr()->isNotNull('o.endTime') )
                    ->andwhere( $qbor->expr()->isNull('o.cancelTime') )
                    ->andWhere($qbor->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qbor->expr()->lte('o.createTime',':endTime'))
                    ->andWhere($qbor->expr()->orX(
                        $qbor->expr()->gte('o.endTime',':startTime'),
                        $qbor->expr()->isNull('o.endTime')
                    ))
                    ->setParameter('province', $province)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->orderBy('o.createTime','ASC')
                    ->getQuery()
                    ->getResult() ;
            $rentalStation=null;
            $Area= null;
            $City=null;
            $Province=  $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:area')
                ->findOneBy(['id'=>$province]);
            //全部省
            $citys = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => $province], ['id' => 'ASC']);

            $qbrs =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->createQueryBuilder('rs');
            $rentalStations =
                $qbrs
                    ->select('rs')
                    ->join('rs.area','ar')
                    ->join('ar.parent','pr')
                    ->andWhere($qb->expr()->eq('pr.parent',':province'))
                    ->setParameter('province', $province)
                    ->getQuery()
                    ->getResult() ;
            //全部省
            $provinces = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC']);

            //在库时长
            $car_count=[];$dispatch_count=[];$arr_index=[];$countTime=[];$count=[];
            if($rentalCars){
                $stay_time = [];
                $stay_time_dispatch=[];

                foreach($rentalCars as $car){
                    if(!empty($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($count[$car->getId()])){
                        $count[$car->getId()]=0;
                    }
                    if(!isset($dispatch_count[$car->getId()])){
                        $dispatch_count[$car->getId()]=0;
                    }
                    if(!isset($car_count[$car->getId()])){
                        $car_count[$car->getId()]=0;
                    }
                    $car_count[$car->getId()]+=1;
                    $qb =
                        $this
                            ->getDoctrine()
                            ->getRepository('AutoManagerBundle:DispatchRentalCar')
                            ->createQueryBuilder('dr');
                    $dispatchs =
                        $qb
                            ->select('dr')
                            ->andWhere($qb->expr()->eq('dr.rentalCar', ':car'))
                            ->andWhere($qb->expr()->gte('dr.createTime', ':startTime'))
                            ->andWhere($qb->expr()->lte('dr.createTime', ':endTime'))
                            ->andWhere($qb->expr()->eq('dr.status', ':status'))
                            ->setParameter('status', 1)
                            ->setParameter('car', $car->getId())
                            ->setParameter('startTime', $start_time)
                            ->setParameter('endTime', $end_time)
                            ->orderBy('dr.createTime', 'ASC')
                            ->getQuery()
                            ->getResult();

                    $cars=array();$n=0;$time=[];$tid=null;$t=[];
                    if($dispatchs){
                        $count_dispatch=[];
                        $count[$car->getId()]+=1;

                        if(count($dispatchs)==1){
                            foreach($dispatchs as $dispatch){
                                if($dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province){
                                    if($dispatch->getKind()==1){
                                        $t['startTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        $t['startTime']=$dispatch->getRentalOrder()->getEndTime();
                                    }
                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                    $t['endTime']=$dispatchTime;
                                }
                                if($dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()!=$province){
                                    if($dispatch->getKind()==1){
                                        $t['endTime']=$dispatch->getCreateTime();
                                    }
                                    if($dispatch->getKind()==2){
                                        if($dispatch->getRentalOrder()->getEndTime()){
                                            $t['endTime']=$dispatch->getRentalOrder()->getEndTime();
                                        }else{
                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                            $t['endTime']=$dispatchTime;
                                        }

                                    }

                                    $dispatchTime=(new \DateTime($start_time));
                                    $t['startTime']=$dispatchTime;
                                }
                                $time[]=$t;
                                // var_dump($time);
                            }
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if($dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()!=$province){
                                    if (!isset($stay_time[$province][$car->getId()])) {
                                        $stay_time[$province][$car->getId()] = 0;
                                    }
                                    if(!empty($stay_time[$province][$car->getId()])){
                                        $stay_time[$province][$car->getId()]=0;
                                    }
                                    foreach($time as $tim) {
                                        if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                            $stay_time[$province][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                    ->getTimestamp());
                                        }
                                    }
                                }else{
                                    if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    foreach($time as $tim){
                                        if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                    ->getTimestamp());
                                        }
                                        // echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                        $stay_time=$stay_time_dispatch;
                                    }
                                }


                            }
                        }else{
                            if(!isset($count_dispatch[$province][$car->getId()])){
                                $count_dispatch[$province][$car->getId()]=0;
                            }
                            if(!empty($count_dispatch[$province][$car->getId()])){
                                $count_dispatch[$province][$car->getId()]=0;
                            }
                            foreach($dispatchs as $dispatch){
                                $cars[$n]=$dispatch;
                                $n+=1;
                                if(!isset($count_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $count_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                $count_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                            }
//                        echo '$count_dispatch'.var_dump($count_dispatch);
                            if($count_dispatch[$province][$car->getId()]==1){
                                foreach($dispatchs as $dispatch){
                                    $cars[$n]=$dispatch;
                                    $n+=1;
                                }
//                            echo 'count($dispatchs)'.count($dispatchs);
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(($province==$cars[$i]->getRentalStation()->getArea()->getParent()->getParent()->getId())&&($car->getId()==$cars[$i]->getRentalCar()->getId())){
//                                    echo 'test序号'.$i.'RentalStation'.$cars[$i]->getRentalStation()->getId();
                                        if($i==0){
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }
                                            }
//                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
//                                        $t['endTime']=$dispatchTime;

//                                        echo 'one';

                                        }else if($i==(count($dispatchs)-1)){
                                            if($cars[$i]->getKind()==1){
                                                if($cars[$i+1]){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==2){
                                                if($cars[$i]->getRentalOrder()->getEndTime()){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $dispatchTime=(new \DateTime());
                                                }
                                                $t['endTime']=$dispatchTime;
                                            }
                                            if($cars[$i]->getKind()==1){
                                                // $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i+1]->getCreateTime()->getTimestamp()?(new \DateTime()):$cars[$i+1]->getCreateTime();
                                                $t['startTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                //  $dispatchTime=(new \DateTime())->getTimestamp()>$cars[$i]->getRentalOrder()->getEndTime()->getTimestamp()?(new \DateTime()):$cars[$i]->getRentalOrder()->getEndTime();
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }

                                        }else{
                                            if($cars[$i]->getKind()==1){
                                                $t['startTime']=$cars[$i]->getCreateTime();
                                            }
                                            if($cars[$i]->getKind()==2){
                                                $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                            }
                                            if($cars[$i+1]->getKind()==1){
                                                $t['endTime']=$cars[$i+1]->getCreateTime();
                                            }
                                            if($cars[$i+1]->getKind()==2){
                                                if($cars[$i+1]->getRentalOrder()->getEndTime()){
                                                    $t['endTime']=$cars[$i+1]->getRentalOrder()->getEndTime();
                                                }else{
                                                    $t['endTime']=new \DateTime();
                                                }

                                            }

                                        }

                                        $time[]=$t;
                                    }
//echo '$time'.var_dump($time);
                                }
                                foreach($dispatchs as $dispatch){
                                    $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                    if($dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()!=$province){
                                        if (!isset($stay_time[$province][$car->getId()])) {
                                            $stay_time[$province][$car->getId()] = 0;
                                        }
                                        if(!empty($stay_time[$province][$car->getId()])){
                                            $stay_time[$province][$car->getId()]=0;
                                        }
                                        foreach($time as $tim) {
                                            if (isset($tim['endTime']) && isset($tim['startTime'])) {
                                                $stay_time[$province][$car->getId()] += ($tim['endTime']->getTimestamp() - $tim['startTime']
                                                        ->getTimestamp());
                                            }
                                        }
                                    }else{
                                        if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                            $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                        }
                                        foreach($time as $tim){
                                            if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                            }
                                            if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                                $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                        ->getTimestamp());
                                            }
                                            //   echo 'onlyDisPatch'.var_dump($stay_time_dispatch);

                                            $stay_time=$stay_time_dispatch;
                                        }
                                    }


                                }

                            }else
                            {
                                for($i=0;$i<count($dispatchs);$i++){
                                    if(isset($t['endTime'])){
                                        $t=[];
                                    }
                                    if(!empty($arr_index)){
                                        foreach($arr_index as $index){
                                            if($i!=$index){
                                                if($cars[$i]->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }

                                                }
                                            }else if($i==$index&&$cars[$i-1]->getRentalStation()->getArea()->getParent()->getParent()->getId()!=$province){
                                                if($cars[$i]->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province){
                                                    if($cars[$i]->getKind()==1){
                                                        $t['startTime']=$cars[$i]->getCreateTime();
                                                    }
                                                    if($cars[$i]->getKind()==2){
                                                        $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                    }
                                                    if(($i+1)==count($dispatchs)){
                                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                        $t['endTime']=$dispatchTime;
                                                        $time[]=$t;
                                                        break;
                                                    }

                                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                                        if($cars[$j]->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province){
                                                            $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                            $t['endTime']=$dispatchTime;
                                                            $time[]=$t;
                                                            //    echo 'endTime'.var_dump($t['endTime']);

                                                        }else{
                                                            if($cars[$j]->getKind()==1){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }else if($cars[$j]->getKind()==2){
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                if($cars[$j]->getRentalOrder()->getEndTime()){
                                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                                }else{
                                                                    $t['endTime']=$dispatchTime;

                                                                }
                                                                $time[]=$t;
                                                                break;
                                                            }else{
                                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                                $t['endTime']=$dispatchTime;
                                                                $time[]=$t;
                                                                break;
                                                            }
                                                        }}
                                                }
                                                //   echo '$tstartTime'.var_dump($t);

                                            }else if(($i==$index&&$cars[$i]->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province)){
                                                $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                $t['endTime']=$dispatchTime;
                                                $time[]=$t;
                                                break;

                                            }
                                        }
                                    }else{
                                        if($cars[$i]->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province){
                                            if($i==0){
                                                if($cars[$i]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?new \DateTime():(new \DateTime($start_time));
                                                    $t['startTime']=$dispatchTime->getTimestamp()>$cars[$i]->getCreateTime()->getTimestamp()?$cars[$i]->getCreateTime():$dispatchTime;
                                                    //  echo '$t["startTime"]'.var_dump($t['startTime']);
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($start_time))->getTimestamp()?(new \DateTime($start_time)):new \DateTime();
                                                    if($cars[$i]->getRentalOrder()->getEndTime()) {
                                                        $t['startTime'] = $dispatchTime->getTimestamp() > $cars[$i]->getRentalOrder()->getEndTime()->getTimestamp() ?$cars[$i]->getRentalOrder()->getEndTime()  :$dispatchTime ;
                                                    }else{
                                                        $t['startTime']=$dispatchTime;
                                                    }
                                                }
                                            }else{
                                                if($cars[$i]->getKind()==1){
                                                    $t['startTime']=$cars[$i]->getCreateTime();
                                                }
                                                if($cars[$i]->getKind()==2){
                                                    $t['startTime']=$cars[$i]->getRentalOrder()->getEndTime();
                                                }
                                            }
                                        }
                                    }
                                    if(($i+1)==count($dispatchs)){
                                        $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                        $t['endTime']=$dispatchTime;
                                        $time[]=$t;
                                        break;
                                    }
                                    for($j=$i+1;$j<count($dispatchs);$j++){
                                        if($cars[$j]->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province){
                                            //array_push($arr_index,$j);
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }

                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                        if($cars[$j]->getRentalStation()->getArea()->getParent()->getParent()->getId()!=$province){
                                            if($j==(count($dispatchs)-1)){
                                                if($cars[$j]->getKind()==1){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getCreateTime()->getTimestamp()?$cars[$j]->getCreateTime():$dispatchTime;
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $dispatchTime=(new \DateTime())->getTimestamp()>(new \DateTime($end_time))->getTimestamp()?(new \DateTime($end_time)):new \DateTime();
                                                    if($cars[$j]->getRentalOrder()->getEndTime()){
                                                        $t['endTime']=$dispatchTime->getTimestamp()>$cars[$j]->getRentalOrder()->getEndTime()->getTimestamp()?$cars[$j]->getRentalOrder()->getEndTime():$dispatchTime;
                                                    }else{
                                                        $t['endTime']=$dispatchTime;

                                                    }
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }else{
                                                if($cars[$j]->getKind()==1){
                                                    $t['endTime']=$cars[$j]->getCreateTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                                if($cars[$j]->getKind()==2){
                                                    $t['endTime']=$cars[$j]->getRentalOrder()->getEndTime();
                                                    $time[]=$t;
                                                    break;
                                                }
                                            }

                                        }
                                    }

                                }}
//                       echo 'Time'.var_dump($time);
                            foreach($dispatchs as $dispatch){
                                $dispatch_count[$dispatch->getRentalCar()->getId()]+=1;
                                if(!isset($countTime[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $countTime[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                }
                                if(!empty($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                    $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;

                                }
                                foreach($time as $tim){

                                    if(!isset($stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]=0;
                                    }
                                    if(isset($tim['endTime'])&&isset($tim['startTime'])){
                                        $stay_time_dispatch[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()] +=( $tim['endTime']->getTimestamp() -$tim['startTime']
                                                ->getTimestamp());
                                    }
                                    // echo '$stay_time_dispatch'.var_dump($stay_time_dispatch);
                                    if($dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()==$province&&($countTime[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]+1)==count($time)){
                                        $stay_time=$stay_time_dispatch;
                                    }

                                    $countTime[$dispatch->getRentalStation()->getArea()->getParent()->getParent()->getId()][$dispatch->getRentalCar()->getId()]+=1;
                                }

                            }
                        }
                    }else{
                        $stay_start = $car->getCreateTime()->format('Y-m-d H:i:s')>$start_time?$car->getCreateTime()->format
                        ('Y-m-d H:i:s'):$start_time;
                        $stay_end = (new \DateTime())->format('Y-m-d H:i:s')>$end_time?
                            $end_time:(new \DateTime())->format('Y-m-d  H:i:s');
                        if(!isset($stay_time[$car->getRentalStation()->getArea()->getParent()->getParent()->getId()][$car->getId()])){
                            $stay_time[$car->getRentalStation()->getArea()->getParent()->getParent()->getId()][$car->getId()]=0;
                        }

                        $stay_time[$car->getRentalStation()->getArea()->getParent()->getParent()->getId()][$car->getId()] = (new \DateTime($stay_end))
                                ->getTimestamp() -(new \DateTime($stay_start))
                                ->getTimestamp();
                        /* echo 'test'.((new \DateTime('2016-04-13  13:48:52'))->getTimestamp() -(new \DateTime('2016-04-06 09:04:39'))->getTimestamp());
                         echo '$stay_start'.$stay_start;
                         echo '$stay_end'.$stay_end;
                         echo '$stay_time'.var_dump($stay_time);*/
                    }


                }
                $car_count_data=null;
            }else{
                $stay_time=null;
            }


        }else{
            $rentalCars=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findAll();
            $rentalStation=null;
            $Area= null;
            $City=null;
            $Province=  null;
            //全部省
            $provinces = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC']);
            $rentalStations = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findAll();
            //订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb
                    ->select('o')
                    ->where( $qb->expr()->isNull('o.cancelTime') )
                    //->andwhere( $qb->expr()->isNotNull('o.endTime') )
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
//                    ->andWhere($qb->expr()->orX(
//                        $qb->expr()->gte('o.endTime',':startTime'),
//                        $qb->expr()->isNull('o.endTime')
//                    ))
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->orderBy('o.createTime','ASC')
                    ->getQuery()
                    ->getResult() ;
//            echo count($rentalCars);
//            echo 'order'.count($rentalOrders);
        }

        //echo count($rentalOrders);


        $rentalTime = 0;
        //$stay_time=number_format($stay_time/3600,2, '.', '');
        //出租时长
        foreach($rentalOrders as $order){
            $start =  $order->getCreateTime()->getTimestamp() ;
            $end = $order->getEndTime() ? $order->getEndTime()->getTimestamp(): ($end_timestamp>strtotime(date("Y-m-d H:i:s"))?strtotime(date("Y-m-d H:i:s")):$end_timestamp);

            if ($order->getEndTime()) {
                if ($order->getEndTime()->getTimestamp() >= $end_timestamp) {
                    $end = $end_timestamp;
                }
            }
            $rentalTime+=($end-$start);
        }
        $rental_time=number_format($rentalTime/3600,2, '.', '');
       //echo '$rental_time'.$rental_time;
        //下线原因
        $deviceBreakDown=0;//12设备故障
        $charging=0;//13车辆充电
        $CarAccident=0;//14：车辆故障/事故
        $CarDebug=0;//15：调配车辆
        $other=0;//其他
        $t=[];
        $otherTime=0;$CarDebugTime=0;$CarAccidentTime=0;$chargingTime=0;$deviceBreakDownTime=0;
        if($rentalCars){
        foreach($rentalCars as $key=>$car){
           if(!isset($t['endTime'])){
                $t['endTime']=null;
            }
            if($t['endTime']){$t=[];}
            if($car->getOnline()){
                if($car->getOnline()->getCreateTime()->getTimestamp()>=(new \DateTime($start_time))->getTimestamp() && $car->getOnline()->getCreateTime()->getTimestamp()<=(new \DateTime($end_time))->getTimestamp()){

                    if($car->getOnline()->getStatus()==1){
                        $t['endTime']=$car->getOnline()->getCreateTime();
                    }else{
                        $offLineEnd=(new \DateTime($end_time))->getTimestamp()>(new \DateTime())->getTimestamp()?(new \DateTime()):(new \DateTime($end_time));
                        $t['endTime']=$offLineEnd;
                    }

                    if($car->getOnline()->getStatus()==0){
                        $reasons=$car->getOnline()->getReason();
                        foreach($reasons as $reason){
                            if($reason==12){
                                $deviceBreakDown+=1;
                                $t['starTime']=$car->getOnline()->getCreateTime();
                                $deviceBreakDownTime+=$t['endTime']->getTimestamp()-$t['starTime']->getTimestamp();
                            }elseif($reason==13){
                                $charging+=1;
                                $t['starTime']=$car->getOnline()->getCreateTime();
                                $chargingTime +=$t['endTime']->getTimestamp()-$t['starTime']->getTimestamp();
                            }elseif($reason==14){
                                $CarAccident+=1;
                                $t['starTime']=$car->getOnline()->getCreateTime();
                                $CarAccidentTime +=$t['endTime']->getTimestamp()-$t['starTime']->getTimestamp();
                            }elseif($reason==15){
                                $CarDebug+=1;
                                $t['starTime']=$car->getOnline()->getCreateTime();
                                $CarDebugTime +=$t['endTime']->getTimestamp()-$t['starTime']->getTimestamp();

                            }else{
                                $other+=1;
                                $t['starTime']=$car->getOnline()->getCreateTime();
                                $otherTime +=$t['endTime']->getTimestamp()-$t['starTime']->getTimestamp();
                          // echo ($otherTime/3600);var_dump($t);
                            }
                        }
                    }

                }

            }
        }
        }
        //车型收入
        $rental_amount=[];
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');
        $rentalPayOrders =
            $qb
                ->select('o')
                ->where($qb->expr()->isNull('o.cancelTime'))
                ->andWhere($qb->expr()->isNotNull('o.useTime'))
                ->andWhere($qb->expr()->gte('o.payTime', ':startTime'))
                ->andWhere($qb->expr()->lte('o.payTime', ':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->getQuery()
                ->getResult();
        foreach ($rentalOrders as $order) {
            if (!isset($rental_amount[$order->getRentalCar()->getCar()->getId()])) {
                $rental_amount[$order->getRentalCar()->getCar()->getId()] = 0;
            }
        }
        foreach ($rentalPayOrders as $order) {
            if (!isset($rental_amount[$order->getRentalCar()->getCar()->getId()])) {
                $rental_amount[$order->getRentalCar()->getCar()->getId()] = 0;
            }
            if($order->getPayTime()){
                if ($order->getPayTime()->format('Y-m-d H:i:s') > $start_time && $order->getPayTime()->format('Y-m-d H:i:s')
                    < $end_time
                ) {
                    $rental_amount[$order->getRentalCar()->getCar()->getId()] += $order->getAmount();
                }
            }else{
                $rental_amount[$order->getRentalCar()->getCar()->getId()] =0;
            }
        }

        $car_types=[];$rental_car=[];
        if($rentalCars){
        foreach($rentalCars as $rentalCar){
            $rental_car[]= $rentalCar->getId();
            $car_types[]=$rentalCar->getCar()->getId();
        }
        }

        $carTypes=array_unique($car_types);
        $cars=array_unique($rental_car);
        $stayTime=0;
        if($rental_station){
            foreach($cars as $car){
                if(!isset($stay_time[$rental_station][$car])){
                    $stay_time[$rental_station][$car]=0;
                }
                $stayTime +=$stay_time[$rental_station][$car];
            }
        }elseif($area){
            foreach($cars as $car){
                if(!isset($stay_time[$area][$car])){
                    $stay_time[$area][$car]=0;
                }
                $stayTime +=$stay_time[$area][$car];
            }
        }elseif($city){
            foreach($cars as $car){
                if(!isset($stay_time[$city][$car])){
                    $stay_time[$city][$car]=0;
                }
                $stayTime +=$stay_time[$city][$car];
            }
        }elseif($province){
            foreach($cars as $car){
                if(!isset($stay_time[$province][$car])){
                    $stay_time[$province][$car]=0;
                }
                $stayTime +=$stay_time[$province][$car];
            }
        }else{
            foreach($rentalCars as $car) {
                $stay_start = $car->getCreateTime()->format('Y-m-d H:i:s') > $start_time ? $car->getCreateTime()->format
                ('Y-m-d H:i:s') : $start_time;
                $stay_end = (new \DateTime())->format('Y-m-d H:i:s') > $end_time ? $end_time : (new \DateTime())->format('Y-m-d  H:i:s');
                $stayTime+=(new \DateTime($stay_end))->getTimestamp() - (new \DateTime($stay_start))
                        ->getTimestamp();
            }
        }
        //echo '$stayTime'.$stayTime;
        $staytime=number_format($stayTime/3600,2, '.', '');
        //echo 'staytime'.$staytime;
        $countRentalStations=count($rentalStations);
        $countRentalCars=count($cars);
        $countCars=count($carTypes);
        $countOrders=count($rentalOrders);

        return ['provinces'=>$provinces,'rentalStations'=>$rentalStations,'countRentalStations'=>$countRentalStations,
            'countRentalCars'=>$countRentalCars,'countCars'=>$countCars,'rentalCars'=>$rentalCars,'stay_time'=>$staytime,
            'rental_time'=>$rental_time,'countOrders'=>$countOrders,'deviceBreakDown'=>$deviceBreakDown,'charging'=>$charging,
            'CarAccident'=>$CarAccident,'CarDebug'=>$CarDebug,'other'=>$other,'rentalOrders'=>$rentalOrders,'rental_amount'=>$rental_amount,
            'province'=>$Province,'city'=>$City,'area'=>$Area,'rental_station'=>$rentalStation,'startTime'=>$start_time,'endTime'=>$end_time,
        'cantons'=>$cantons,'citys'=>$citys,'day'=>$day];
    }


    /**
     * @Route("/order", methods="GET", name="auto_admin_chart_order")
     * @Template()
     */
    public function orderAction(Request $req)
    {   date_default_timezone_set("PRC");
        $province=$req->query->get('province');
        $city=$req->query->get('city');
        $area=$req->query->get('area');
        $rental_station=$req->query->get('rental_station');
        $day=$req->query->get('day');
        $startTime = $req->query->get('start_time');
        $endTime = $req->query->get('end_time');
        $end_time = $endTime ? $endTime : date("Y-m-d H:i:s");
        if($day){
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *$day));
        }else{
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *1));
        }
        $cantons=null;
        $citys=null;
        $provinces=null;

        //用户使用频次
        $memebers=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findAll();
       $oneFrenquency=0;
        $twoFrenquency=0;
        $sixFrenquency=0;
        $elevenFrenquency=0;
        $sixtyFrenquency=0;
        $twentyFrenquency=0;
        foreach($memebers as $member){
            if($rental_station){
                //时间段订单
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalOrder')
                        ->createQueryBuilder('o');
                $MemberRentalOrders =
                    $qb
                        ->select('o')
                        ->where( $qb->expr()->isNull('o.cancelTime') )
                        ->andWhere($qb->expr()->orX(
                            $qb->expr()->gte('o.endTime', ':startTime'),
                            $qb->expr()->isNull('o.endTime')
                        ))
                        ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                        ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                        ->andWhere($qb->expr()->eq('o.pickUpStation',':station'))
                        ->andWhere($qb->expr()->isNotNull('o.useTime'))
                        ->andWhere($qb->expr()->eq('o.member',':member'))
                        ->setParameter('member', $member)
                        ->setParameter('station', $rental_station)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->getQuery()
                        ->getResult() ;

                //全部省
                $provinces = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => null], ['id' => 'ASC']);
                $rentalStations = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->findAll();
            }elseif($area){

                //时间段订单
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalOrder')
                        ->createQueryBuilder('o');
                $MemberRentalOrders =
                    $qb
                        ->select('o')
                        ->join('o.pickUpStation','ps')
                        ->where( $qb->expr()->isNull('o.cancelTime') )
                        ->andWhere($qb->expr()->orX(
                            $qb->expr()->gte('o.endTime', ':startTime'),
                            $qb->expr()->isNull('o.endTime')
                        ))
                        ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                        ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                        ->andWhere($qb->expr()->eq('ps.area',':area'))
                        ->andWhere($qb->expr()->isNotNull('o.useTime'))
                        ->andWhere($qb->expr()->eq('o.member',':member'))
                        ->setParameter('member', $member)
                        ->setParameter('area', $area)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->getQuery()
                        ->getResult() ;

                //全部省
                $provinces = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => null], ['id' => 'ASC']);
                $rentalStations = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->findBy(['area' => $area]);

            }elseif($city){
                //时间段订单
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalOrder')
                        ->createQueryBuilder('o');
                $MemberRentalOrders =
                    $qb
                        ->select('o')
                        ->join('o.pickUpStation','ps')
                        ->join('ps.area','ar')
                        ->where( $qb->expr()->isNull('o.cancelTime') )
                        ->andWhere($qb->expr()->orX(
                            $qb->expr()->gte('o.endTime', ':startTime'),
                            $qb->expr()->isNull('o.endTime')
                        ))
                        ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                        ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                        ->andWhere($qb->expr()->eq('ar.parent',':city'))
                        ->andWhere($qb->expr()->isNotNull('o.useTime'))
                        ->andWhere($qb->expr()->eq('o.member',':member'))
                        ->setParameter('member', $member)
                        ->setParameter('city', $city)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->getQuery()
                        ->getResult() ;
                //全部城区
                $cantons = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => $city], ['id' => 'ASC']);

                $qbrs =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalStation')
                        ->createQueryBuilder('rs');
                $rentalStations =
                    $qbrs
                        ->select('rs')
                        ->join('rs.area','ar')
                        ->andWhere($qb->expr()->eq('ar.parent',':city'))
                        ->setParameter('city', $city)
                        ->getQuery()
                        ->getResult() ;
                //全部省
                $provinces = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => null], ['id' => 'ASC']);

            }elseif($province){
                //时间段订单
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalOrder')
                        ->createQueryBuilder('o');
                $MemberRentalOrders =
                    $qb
                        ->select('o')
                        ->join('o.pickUpStation','ps')
                        ->join('ps.area','ar')
                        ->join('ar.parent','pr')
                        ->where( $qb->expr()->isNull('o.cancelTime') )
                        ->andWhere($qb->expr()->isNotNull('o.useTime'))
                        ->andWhere($qb->expr()->orX(
                            $qb->expr()->gte('o.endTime', ':startTime'),
                            $qb->expr()->isNull('o.endTime')
                        ))
                        ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                        ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                        ->andWhere($qb->expr()->eq('pr.parent',':province'))
                        ->andWhere($qb->expr()->eq('o.member',':member'))
                        ->setParameter('member', $member)
                        ->setParameter('province', $province)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->getQuery()
                        ->getResult() ;
                //全部省
                $citys = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => $province], ['id' => 'ASC']);

                $qbrs =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalStation')
                        ->createQueryBuilder('rs');
                $rentalStations =
                    $qbrs
                        ->select('rs')
                        ->join('rs.area','ar')
                        ->join('ar.parent','pr')
                        ->andWhere($qb->expr()->eq('pr.parent',':province'))
                        ->setParameter('province', $province)
                        ->getQuery()
                        ->getResult() ;
                //全部省
                $provinces = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => null], ['id' => 'ASC']);

            }else{
                //时间段订单
                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RentalOrder')
                        ->createQueryBuilder('o');
                $MemberRentalOrders =
                    $qb
                        ->select('o')
                        ->where( $qb->expr()->isNull('o.cancelTime') )
                        ->andWhere($qb->expr()->orX(
                            $qb->expr()->gte('o.endTime', ':startTime'),
                            $qb->expr()->isNull('o.endTime')
                        ))
                        ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                        ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                        ->andWhere($qb->expr()->eq('o.member',':member'))
                        ->setParameter('member', $member)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->getQuery()
                        ->getResult() ;
                //全部省
                $provinces = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Area')
                    ->findBy(['parent' => null], ['id' => 'ASC']);
                $rentalStations = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->findAll();
            }
            if(count($MemberRentalOrders)==1){
                $oneFrenquency+=1;
            }
            if(2<=count($MemberRentalOrders)&&count($MemberRentalOrders)<=5){
                $twoFrenquency+=1;
            }
            if(6<=count($MemberRentalOrders)&&count($MemberRentalOrders)<=10){
                $sixFrenquency+=1;
            }
            if(11<=count($MemberRentalOrders)&&count($MemberRentalOrders)<=15){
                $elevenFrenquency+=1;
            }
            if(16<=count($MemberRentalOrders)&&count($MemberRentalOrders)<=20){
                $sixtyFrenquency+=1;
            }
            if(21<=count($MemberRentalOrders)){
                $twentyFrenquency+=1;
            }

        }

        //用户使用频次结束

        if($rental_station){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('rc');
            $rentalCars= $qb
                ->select('rc')
                ->where( $qb->expr()->eq('rc.rentalStation',':rentalStation') )
                ->setParameter('rentalStation', $rental_station)
                ->getQuery()
                ->getResult() ;

            $rentalStation= $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findOneBy(['id'=>$rental_station]);
            $Area= null;
            $City=null;
            $Province=  null;
            //累计订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');

            $allOrders  =
                $qb
                    ->select('o')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->eq('o.pickUpStation',':station'))
                    ->setParameter('station', $rental_station)
                    ->getQuery()
                    ->getResult();

            //时间段订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb
                    ->select('o')
                    ->where( $qb->expr()->isNull('o.cancelTime') )
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->andWhere($qb->expr()->eq('o.pickUpStation',':station'))
                    //->andWhere($qb->expr()->isNotNull('o.useTime'))
                    ->setParameter('station', $rental_station)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;


        }elseif($area){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.rentalStation','rs');
            $rentalCars=
                $qb
                    ->where($qb->expr()->eq('rs.area',':area') )
                    ->setParameter('area', $area)
                    ->getQuery()
                    ->getResult() ;
            $rentalStation=null;
            $City=null;
            $Province=  null;
            $Area= $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:area')
                ->findOneBy(['id'=>$area]);

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');

            $allOrders  =
                $qb
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->eq('ps.area',':area'))
                    ->setParameter('area', $area)
                    ->getQuery()
                    ->getResult();

            //时间段订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->where( $qb->expr()->isNull('o.cancelTime') )
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->andWhere($qb->expr()->eq('ps.area',':area'))
                    ->andWhere($qb->expr()->isNotNull('o.useTime'))
                    ->setParameter('area', $area)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;

        }elseif($city){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.rentalStation','rs');
            $rentalCars=
                $qb->join('rs.area','ar')
                    ->where($qb->expr()->eq('ar.parent',':city') )
                    ->setParameter('city', $city)
                    ->getQuery()
                    ->getResult() ;
            $rentalStation=null;
            $Area= null;
            $City=$this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:area')
                ->findOneBy(['id'=>$city]);
            $Province=  null;

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');

            $allOrders  =
                $qb
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->join('ps.area','ar')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->eq('ar.parent',':city'))
                    ->setParameter('city', $city)
                    ->getQuery()
                    ->getResult();

            //时间段订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->join('ps.area','ar')
                    ->where( $qb->expr()->isNull('o.cancelTime') )
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->andWhere($qb->expr()->eq('ar.parent',':city'))
                    ->andWhere($qb->expr()->isNotNull('o.useTime'))
                    ->setParameter('city', $city)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;


        }elseif($province){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c')
                    ->select('c')
                    ->join('c.rentalStation','rs');
            $rentalCars=
                $qb->join('rs.area','ar')
                    ->join('ar.parent','p')
                    ->where($qb->expr()->eq('p.parent',':province') )
                    ->setParameter('province', $province)
                    ->getQuery()
                    ->getResult() ;
            $rentalStation=null;
            $Area= null;
            $City=null;
            $Province=  $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:area')
                ->findOneBy(['id'=>$province]);
            //总的订单，不分时间段
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');

            $allOrders  =
                $qb
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->join('ps.area','ar')
                    ->join('ar.parent','pr')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->andWhere($qb->expr()->eq('pr.parent',':province'))
                    ->setParameter('province', $province)
                    ->getQuery()
                    ->getResult();

            //时间段订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb
                    ->select('o')
                    ->join('o.pickUpStation','ps')
                    ->join('ps.area','ar')
                    ->join('ar.parent','pr')
                    ->where( $qb->expr()->isNull('o.cancelTime') )
                    ->andWhere($qb->expr()->isNotNull('o.useTime'))
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gte('o.endTime', ':startTime'),
                        $qb->expr()->isNull('o.endTime')
                    ))
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->andWhere($qb->expr()->eq('pr.parent',':province'))
                    ->setParameter('province', $province)
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;


        }else{
            $rentalCars=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findAll();
            $rentalStation=null;
            $Area= null;
            $City=null;
            $Province=  null;
            //总的订单，不分时间段
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');

            $allOrders  =
                $qb
                    ->select('o')
                    ->where($qb->expr()->isNull('o.cancelTime'))
                    ->getQuery()
                    ->getResult();


            //时间段订单
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('o');
            $rentalOrders =
                $qb
                    ->select('o')
                    ->where( $qb->expr()->isNull('o.cancelTime') )
//                    ->andWhere($qb->expr()->orX(
//                        $qb->expr()->gte('o.endTime', ':startTime'),
//                        $qb->expr()->isNull('o.endTime')
//                    ))
                    ->andWhere($qb->expr()->gte('o.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('o.createTime',':endTime'))
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;
        }
        //每天的订单数
        $everyDayOrder=array();
        if($rentalOrders){
            foreach($rentalOrders as $order){
                foreach($order->getCreateTime() as $k=> $time){
                    if($k=='date'){  $Time=substr($time,0,10);
//                    $timeStamp=(new \DateTime($Time))->getTimestamp();
//                    echo $timeStamp;
                        if(!isset($everyDayOrder[$Time])){
                            $everyDayOrder[$Time]=1;
                        }else{
                            $everyDayOrder[$Time]+=1;
                        }
                    }
                }
            }
        }else{
            $everyDayOrder=0;
        }
        $time_list=[];$num=0;
        for($i = strtotime($start_time); $i <= strtotime($end_time); $i += 86400)
        {
            $ThisDate=date("Y-m-d",$i);
            $time_list[$num]=$ThisDate;
            $num +=1;
        }
        /*foreach($time_list as $timeList){
            if($rentalOrders){
                foreach($rentalOrders as $order){
                    foreach($order->getCreateTime() as $k=> $time){
                        if($k=='date'){
                                $Time=substr($time,0,10);
//                    $timeStamp=(new \DateTime($Time))->getTimestamp();
//                    echo $timeStamp;
                            if($timeList==$Time){
                                if(!isset($everyDayOrder[$Time])){
                                    $everyDayOrder[$Time]=1;
                                }else{
                                    $everyDayOrder[$Time]+=1;echo 'yes';
                                }
                            }else{
                                $everyDayOrder[$timeList]=0;
                            }
                        }
                    }
                }
            }else{
                $everyDayOrder=0;
            }
        }*/


        //总的营收、实收
        $rental_revenue = 0;
        $rental_all_amount=0;
        foreach($allOrders as $order){
            $order_cost = $this->get('auto_manager.order_helper')->get_charge_details($order);
            $rental_revenue += $order_cost['cost'];
            $rental_all_amount += $order->getAmount();
        }
        //时间段内的实收,优惠券
        $order_amount=0;$order_coupon=0;
        if($rentalOrders){
            foreach($rentalOrders as $order){
                $order_amount+=$order->getAmount();
                if (!empty($order->getCoupon()) && $order->getCoupon()->getUseTime()) {
                    $order_coupon+= $order->getCoupon()->getKind()->getAmount();
                   // echo '车'.$order->getRentalCar()->getId().'订单'.$order->getId().'优惠券'.$order->getCoupon()->getKind()->getAmount();
                }
            }


        }else{
            $order_amount=0;
            $order_coupon=0;
        }


        $member=$qb->select('COUNT(DISTINCT o.member)')
            ->getQuery()
            ->getSingleScalarResult();
        //新的营收、实收
        $new_rental_revenue = 0;
        $new_rental_amount=0;
        foreach($rentalOrders as $order){
            $order_cost = $this->get('auto_manager.order_helper')->get_charge_details($order);
            $new_rental_revenue += $order_cost['cost'];
            $new_rental_amount += $order->getAmount();
        }

        $rentalTime = 0;
        $stayTime=0;
        //在库时长
        foreach($rentalCars as $car) {
            $stay_start = $car->getCreateTime()->format('Y-m-d H:i:s') > $start_time ? $car->getCreateTime()->format
            ('Y-m-d H:i:s') : $start_time;
            $stay_end = (new \DateTime())->format('Y-m-d H:i:s') > $end_time ? $end_time : (new \DateTime())->format('Y-m-d  H:i:s');
            $stayTime+=(new \DateTime($stay_end))->getTimestamp() - (new \DateTime($stay_start))
                    ->getTimestamp();
        }
        $stay_time=number_format($stayTime/3600,2, '.', '');
        //出租时长
        foreach($rentalOrders as $order){
            $end = $order->getEndTime()?$order->getEndTime():(new \DateTime($end_time));
            //$start = $order->getCreateTime()->getTimestamp()>(new \DateTime($start_time))->getTimestamp()
            //   ?$order->getCreateTime():(new \DateTime($start_time));
            $start = $order->getCreateTime();

            $rentalTime+=($end->getTimestamp()-$start->getTimestamp());
        }
        $rental_time=number_format($rentalTime/3600,2, '.', '');
        //下线原因
        $deviceBreakDown=0;//12设备故障
        $charging=0;//13车辆充电
        $CarAccident=0;//14：车辆故障/事故
        $CarDebug=0;//15：调配车辆
        $other=0;//其他
        foreach($rentalCars as $car){
            if($car->getOnline()){
                if($car->getOnline()->getStatus()){
                    if($car->getOnline()->getStatus()==12){
                        $deviceBreakDown+=1;
                    }elseif($car->getOnline()->getStatus()==13){
                        $charging+=1;
                    }elseif($car->getOnline()->getStatus()==14){
                        $CarAccident+=1;
                    }elseif($car->getOnline()->getStatus()==15){
                        $CarDebug+=1;
                    }else{
                        $other+=1;
                    }
                }
            }
        }

        //车型收入
        $rental_amount=[];
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');
        $rentalPayOrders =
            $qb
                ->select('o')
                ->where($qb->expr()->isNull('o.cancelTime'))
                ->andWhere($qb->expr()->isNotNull('o.useTime'))
                ->andWhere($qb->expr()->gte('o.payTime', ':startTime'))
                ->andWhere($qb->expr()->lte('o.payTime', ':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->getQuery()
                ->getResult();
        foreach ($rentalOrders as $order) {
            if (!isset($rental_amount[$order->getRentalCar()->getCar()->getId()])) {
                $rental_amount[$order->getRentalCar()->getCar()->getId()] = 0;
            }
            if($order->getPayTime()){
                if ($order->getPayTime()->format('Y-m-d H:i:s') > $start_time && $order->getPayTime()->format('Y-m-d H:i:s')
                    < $end_time
                ) {
                    $rental_amount[$order->getRentalCar()->getCar()->getId()] += $order->getAmount();
                }
            }else{
                $rental_amount[$order->getRentalCar()->getCar()->getId()] =0;
            }
        }

        //单次时长
        $oneHour=0;
        $threeHour=0;
        $fourHour=0;
        $sevenHour=0;
        $twelveHour=0;
        $sixtyHour=0;
        foreach($rentalOrders as $order){
            $startTimeStamp=$order->getCreateTime()->getTimestamp();
            if($order->getEndTime()){
                $endTimeStamp=$order->getEndTime()->getTimestamp();
            }else if( (new \DateTime($end_time))->getTimestamp() > (new \DateTime())->getTimestamp()  ){
                $endTimeStamp=(new \DateTime())->getTimestamp();
            }else{
                $endTimeStamp=(new \DateTime($end_time))->getTimestamp();
            }

            $timeGap=($endTimeStamp-$startTimeStamp)/3600;
            if($timeGap<=1){
                $oneHour+=1;
            }
            if(1<$timeGap&&$timeGap<=3){
                $threeHour+=1;
            }
            if(3<$timeGap&&$timeGap<=6){
                $fourHour+=1;
            }
            if(6<$timeGap&&$timeGap<=12){
                $sevenHour+=1;
            }
            if(12<$timeGap&&$timeGap<=16){
                $twelveHour+=1;
            }
            if(16<$timeGap){
                $sixtyHour+=1;
            }
        }
        //单次使用时长结束

        //用户分布
        foreach($rentalOrders as $rentalOrder){

        }
        //用户分布结束

        //总订单数
        $countOrders=count($allOrders);
        //新增订单数
        $countNewOrders=count($rentalOrders);
        return ['provinces'=>$provinces,'rentalStations'=>$rentalStations,
            'rentalCars'=>$rentalCars,'stay_time'=>$stay_time,
            'rental_time'=>$rental_time,'countOrders'=>$countOrders,'order_amount'=>$order_amount,'order_coupon'=>$order_coupon,
            'CarAccident'=>$CarAccident,'CarDebug'=>$CarDebug,'other'=>$other,'rentalOrders'=>$rentalOrders,'rental_amount'=>$rental_amount,
            'province'=>$Province,'city'=>$City,'area'=>$Area,'rental_station'=>$rentalStation,'startTime'=>$start_time,'endTime'=>$end_time,
            'rental_revenue'=>$rental_revenue,'allOrders'=>$allOrders,'rental_all_amount'=>$rental_all_amount,'new_rental_revenue'=>$new_rental_revenue,
            'new_rental_amount'=>$new_rental_amount,'countNewOrders'=>$countNewOrders,'member'=>$member,'everyDayOrder'=>$everyDayOrder,'time_list'=>$time_list,
        'oneFrenquency'=>$oneFrenquency,'twoFrenquency'=>$twoFrenquency,'sixFrenquency'=>$sixFrenquency,
            'elevenFrenquency'=>$elevenFrenquency, 'sixtyFrenquency'=>$sixtyFrenquency,'twentyFrenquency'=>$twentyFrenquency,
            'oneHour'=>$oneHour,'threeHour'=>$threeHour,'fourHour'=>$fourHour,'sevenHour'=>$sevenHour,'twelveHour'=>$twelveHour,
            'sixtyHour'=>$sixtyHour,'cantons'=>$cantons,'citys'=>$citys];
    }




    /**
     * @Route("/register", methods="GET", name="auto_admin_chart_register")
     * @Template()
     */
    public function registerAction(Request $req)
    {   date_default_timezone_set("PRC");
        $day=$req->query->get('day');
        $startTime = $req->query->get('start_time');
        $endTime = $req->query->get('end_time');
        $end_time = $endTime ? $endTime : date("Y-m-d H:i:s");
        if($day){
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *$day));
        }else{
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *1));
        }

            //累计注册
            $allMember =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Member')
                    ->findAll();

            //新增注册
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Member')
                    ->createQueryBuilder('m');
            $newMembers =
                $qb
                    ->select('m')
                    ->andWhere($qb->expr()->gte('m.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('m.createTime',':endTime'))
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;

        //每天的订单数
        $everyDayMember=array();
        if($newMembers){
            foreach($newMembers as $member){
                foreach($member->getCreateTime() as $k=> $time){
                    if($k=='date'){  $Time=substr($time,0,10);
//                    $timeStamp=(new \DateTime($Time))->getTimestamp();
//                    echo $timeStamp;
                        if(!isset($everyDayMember[$Time])){
                            $everyDayMember[$Time]=1;
                        }else{
                            $everyDayMember[$Time]+=1;
                        }
                    }
                }
            }
        }else{
            $everyDayMember=0;
        }
        $time_list=[];$num=0;
        for($i = strtotime($start_time); $i <= strtotime($end_time); $i += 86400)
        {
            $ThisDate=date("Y-m-d",$i);
            $time_list[$num]=$ThisDate;
            $num +=1;
        }

        //总订单数
        $countMembers=count($allMember);
        //新增订单数
        $countNewMembers=count($newMembers);
        return ['countMembers'=>$countMembers,'countNewMembers'=>$countNewMembers,
        'startTime'=>$start_time,'endTime'=>$end_time,'time_list'=>$time_list,'everyDayMember'=>$everyDayMember,
        'newMembers'=>$newMembers];
    }



    /**
     * @Route("/identification", methods="GET", name="auto_admin_chart_identification")
     * @Template()
     */
    public function identificationAction(Request $req)
    {   date_default_timezone_set("PRC");
        $day=$req->query->get('day');
        $startTime = $req->query->get('start_time');
        $endTime = $req->query->get('end_time');
        $end_time = $endTime ? $endTime : date("Y-m-d H:i:s");
        if($day){
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *$day));
        }else{
            $start_time = $startTime ? $startTime : date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) - 86400 *1));
        }

            //全部认证
            $allMembers=$this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:AuthMember')
                ->findAll();
            //新增认证
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:AuthMember')
                    ->createQueryBuilder('am');
            $newMembers =
                $qb
                    ->select('am')
                    ->andWhere($qb->expr()->gte('am.createTime',':startTime'))
                    ->andWhere($qb->expr()->lte('am.createTime',':endTime'))
                    ->setParameter('startTime', $start_time)
                    ->setParameter('endTime', $end_time)
                    ->getQuery()
                    ->getResult() ;

        //每天的注册数
        $everyDayMember=array();
        if($newMembers){
            foreach($newMembers as $member){
                foreach($member->getCreateTime() as $k=> $time){
                    if($k=='date'){  $Time=substr($time,0,10);
//                    $timeStamp=(new \DateTime($Time))->getTimestamp();
//                    echo $timeStamp;
                        if(!isset($everyDayMember[$Time])){
                            $everyDayMember[$Time]=1;
                        }else{
                            $everyDayMember[$Time]+=1;
                        }
                    }
                }
            }
        }else{
            $everyDayMember=0;
        }
        $time_list=[];$num=0;
        for($i = strtotime($start_time); $i <= strtotime($end_time); $i += 86400)
        {
            $ThisDate=date("Y-m-d",$i);
            $time_list[$num]=$ThisDate;
            $num +=1;
        }
        //over

        //认证成功,失败人数
        $identifySuccess=0;$identifyFail=0;
        foreach($newMembers as $newMember){
            if($newMember->getStatus()== 299){
                $identifySuccess+=1;
            }
            if($newMember->getStatus()== 202){
                $identifyFail+=1;
            }
        }
        //认证失败原因
        $reason1=0;$reason2=0;$reason3=0;$reason4=0;$reason5=0;$reason6=0;$reason7=0;$reason8=0;$reason9=0;
        foreach($newMembers as $newMember) {
            if ($newMember->getLicenseAuthError() != 0) {
                $reason = $this->get('auto_manager.member_helper')->get_auth_error($newMember->getLicenseAuthError());
            }else{
                $reason=null;
            }
            if($reason=='证件照片内容不清晰'){
                $reason1+=1;
            }elseif($reason=='缺少驾驶证或驾驶证副页'){
                $reason2+=1;
            }elseif($reason=='驾驶证领取时间未满1年'){
                $reason3+=1;
            }elseif($reason=='驾驶证已过期'){
                $reason4+=1;
            }elseif($reason=='驾驶证与驾驶证副页信息不符'){
                $reason5+=1;
            }elseif($reason=='证件信息与交管系统信息不符'){
                $reason6+=1;
            }elseif($reason=='电话无人接听'){
                $reason7+=1;
            }elseif($reason=='不是本人'){
                $reason8+=1;
            }elseif($reason=='无法提供身份证信息'){
                $reason9+=1;
            }
        }
        //黑名单
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('b');
        $blacks =
            $qb
                ->select('b')
                ->andWhere($qb->expr()->gte('b.createTime',':startTime'))
                ->andWhere($qb->expr()->lte('b.createTime',':endTime'))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gte('b.endTime', ':endTime'),
                    $qb->expr()->isNull('b.endTime')
                ))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->getQuery()
                ->getResult() ;
        //注册用户
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->createQueryBuilder('m');
        $members =
            $qb
                ->select('m')
                ->andWhere($qb->expr()->gte('m.createTime',':startTime'))
                ->andWhere($qb->expr()->lte('m.createTime',':endTime'))
                ->setParameter('startTime', $start_time)
                ->setParameter('endTime', $end_time)
                ->getQuery()
                ->getResult() ;
        //注册用户结束
        //未认证
        $uncertifieds=0;
        foreach($members as $member){
            $auth
                =$this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:AuthMember')
                    ->findOneBy(['member'=>$member]);
            if(empty($auth)){
                $uncertifieds+=1;
            }
        }

        //累计认证
        $countMembers=count($allMembers);
        //新增认证
        $countNewMembers=count($newMembers);
        //黑名单
        $black=count($blacks);
        return ['countMembers'=>$countMembers,'countNewMembers'=>$countNewMembers,'startTime'=>$start_time,
        'endTime'=>$end_time,'black'=>$black,'uncertifieds'=>$uncertifieds,'identifySuccess'=>$identifySuccess,'identifyFail'=>$identifyFail,
        'reason1'=>$reason1,'reason2'=>$reason2,'reason3'=>$reason3,'reason4'=>$reason4,'reason5'=>$reason5,'reason6'=>$reason6,
            'reason7'=>$reason7,'reason8'=>$reason8,'reason9'=>$reason9,
        'members'=>$newMembers,'time_list'=>$time_list,'everyDayMember'=>$everyDayMember];
    }






}
