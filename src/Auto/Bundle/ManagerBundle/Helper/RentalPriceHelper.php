<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/20
 * Time: 上午9:48
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class RentalPriceHelper extends AbstractHelper{

    public function get_rental_price_normalizer(){
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalPrice $p) {

            return [

                'rentalPriceID' =>$p->getId()?$p->getId():null,
                'name'=>$p->getName()?$p->getName():null,
                'startTime'=>$p->getStartTime()->format('H:i')?$p->getStartTime()->format('H:i'):null,
                'endTime'=>$p->getEndTime()->format('H:i')?$p->getEndTime()->format('H:i'):null,
                'price'=>$p->getPrice()?$p->getPrice():0,
                'originalPrice'=>$p->getPrice()?$p->getPrice():0,
                'maxHour'=>$p->getMaxHour()?$p->getMaxHour():0,
                'minHour'=>$p->getMinHour()?$p->getMinHour():0,
                'unit'=>'元/分钟',
                'time'=>'',
                'amount'=>''
            ];
        };
    }

    public function get_rental_car_price(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){

        $qb = $this->em->createQueryBuilder();
        return
            $qb
                ->select('p')
                ->from('AutoManagerBundle:RentalPrice', 'p')
                ->where($qb->expr()->eq('p.car', ':car'))
                ->andWhere($qb->expr()->eq('p.area', ':area'))

                ->andWhere($qb->expr()->lte('p.startTime', ':date'))
                ->andWhere($qb->expr()->gt('p.endTime',':date'))

                ->setParameter('date', date('Y-m-d'))
                ->setParameter('car', $r->getCar())
                ->setParameter('area', $r->getRentalStation()->getArea()->getParent())
                ->orderBy('p.minHour')

                ->getQuery()
                ->getResult()
            ;
    }


    //获取车辆折扣
    public function get_car_discount(\Auto\Bundle\ManagerBundle\Entity\Car $r){


        $qb = $this->em->createQueryBuilder();

        $carDiscount =
            $qb
                ->select('c')
                ->from('AutoManagerBundle:CarDiscount', 'c')
                ->andWhere($qb->expr()->lte('c.startTime',':time'))
                ->andWhere($qb->expr()->gte('c.endTime',':time'))
                ->andwhere($qb->expr()->eq('c.car', ':car'))
                ->setParameter('time', (new \DateTime()))
                ->setParameter('car', $r)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if(!empty($carDiscount)) {

            $discount = $carDiscount->getDiscount();

        }else{

            $discount = 1 ;


        }

        return   $discount;

    }


    public function get_rental_order_price(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o){

        $r = $o->getRentalCar();

        $qb = $this->em->createQueryBuilder();
        return
            $qb
                ->select('p')
                ->from('AutoManagerBundle:RentalPrice', 'p')
                ->where($qb->expr()->eq('p.car', ':car'))
                ->andWhere($qb->expr()->eq('p.area', ':area'))

                ->andWhere($qb->expr()->lte('p.startTime', ':date'))
                ->andWhere($qb->expr()->gt('p.endTime',':date'))

                ->setParameter('date', $o->getCreateTime()->format('Y-m-d'))
                ->setParameter('car', $r->getCar())
                ->setParameter('area', $o->getPickUpStation()->getArea()->getParent())
                ->orderBy('p.minHour')
                ->getQuery()
                ->getResult()
            ;
    }



    /**
     * 获取折扣列表
     * @return array
     */
    public function get_discount_list(){
        $arrDiscount = [
            '0.95'=>'9.5折',
            '0.9'=>'9折',
            '0.85'=>'8.5折',
            '0.8'=>'8折',
            '0.75'=>'7.5折',
            '0.7'=>'7折',
            '0.65'=>'6.5折',
            '0.6'=>'6折',
            '0.55'=>'5.5折',
            '0.5'=>'5折',
            '0.45'=>'4.5折',
            '0.4'=>'4折',
        ];
        return $arrDiscount;
    }

}