<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/14
 * Time: 下午4:55
 */

namespace Auto\Bundle\ManagerBundle\Helper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CarHelper extends AbstractHelper{

    public function get_car_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Car $c) {

            $c->getId() == 3 ||  $c->getId() == 7 ? $car_id = 2 : $car_id = $c->getId();


            if($car_id == 8){

                $car_key_img_path = 'http://lecarx.com/photograph/e1/2a/e12ac28c1550725062fe8f9c6d91e5266de6b607.png';

            }else{

                $car_key_img_path = 'http://lecarx.com/photograph/78/90/7890d49410c08198f8f6403af0057a3f11f28b8a.png';

            }

            $car = [
                'carID'                       => $car_id,
                'seat'                        => $c->getSeats(),
                'name'                        => $c->getName(),
                'bodyType'                    => $c->getBodyType()->getName(),
                'keyPositionImgUrl'           => $car_key_img_path,
                'useCarIntroduceUrl'          => $this->router->generate('auto_wap_car_introduce_show',['id'=>$car_id], UrlGeneratorInterface::ABSOLUTE_URL),
                'carDiscount'                 =>$this->get_car_discount($c)
            ];

            return $car;
        };
    }

    /**
     * 获取车型基本信息
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\Car $c
     *
     * @return \Array
     */
    public function get_car_data_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Car $c) {

            $car = [
                'carID'         => $c->getId(),
                'seat'          => $c->getSeats(),
                'name'          => $c->getName(),
                'bodyType'      => $c->getBodyType()->getName(),
                'carDiscount'   => $this->get_car_discount($c)
            ];

            return $car;
        };
    }

    //获取车辆折扣
    public function get_car_discount(\Auto\Bundle\ManagerBundle\Entity\Car $c){


        $qb = $this->em->createQueryBuilder();

        $carDiscount =
            $qb
                ->select('c')
                ->from('AutoManagerBundle:CarDiscount', 'c')
                ->andWhere($qb->expr()->lte('c.startTime',':time'))
                ->andWhere($qb->expr()->gte('c.endTime',':time'))
                ->andwhere($qb->expr()->eq('c.car', ':car'))
                ->setParameter('time', (new \DateTime()))
                ->setParameter('car', $c)
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

    public function get_car_introduction_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Car $c) {

            $c->getId() == 3 || $c->getId() == 7 ? $car_id = 2 : $car_id = $c->getId();

            $car = [
                'carID'                       => $car_id,
                'name'                        => $c->getName(),
                'url'                         => $this->router->generate('auto_wap_car_introduce_show',['id'=>$car_id]),
            ];

            return $car;

        };
    }




    public function get_car_introduction_data_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Car $c) {

            $c->getId() == 3 || $c->getId() == 7 ? $car_id = 2 : $car_id = $c->getId();

            $car = [
                'carID'                       => $car_id,
                'name'                        => $c->getName(),
                'url'                         => $this->router->generate('auto_wap_car_introduce_show2',['id'=>$car_id]),
            ];

            return $car;

        };
    }




    /**
     * @param \Auto\Bundle\ManagerBundle\Entity\CarDiscount $o
     * @return bool
     * 添加折扣时需判断同一个车型在设置的时间段内是否存在已有车型折扣，有则返回true，否则返回false
     */
    public function check_car_discount_overlaps(\Auto\Bundle\ManagerBundle\Entity\CarDiscount $o){

        $qb = $this->em->createQueryBuilder();

        $qb = $qb->select('r')
            ->from('AutoManagerBundle:CarDiscount', 'r')
            ->andWhere($qb->expr()->lte('r.startTime',':time'))
            ->andWhere($qb->expr()->gte('r.endTime',':time'))
            ->andwhere($qb->expr()->eq('r.car', ':car'))
            ->setParameter('time', $o->getStartTime())
            ->setParameter('car', $o->getCar())
            ;

        if($o->getId()){
            $qb = $qb->andWhere($qb->expr()->neq('r.id',':id'))
                ->setParameter('id', $o->getId())
            ;
        }
        $car_discount_start_time =
            $qb

                ->getQuery()
                ->getResult()
        ;

        if(!empty( $car_discount_start_time) ) {
            return true;
        }
        $qb2 = $this->em->createQueryBuilder();
        $qb2 = $qb2->select('r')
            ->from('AutoManagerBundle:CarDiscount', 'r')
            ->andWhere($qb2->expr()->lte('r.startTime',':time'))
            ->andWhere($qb2->expr()->gte('r.endTime',':time'))
            ->andwhere($qb2->expr()->eq('r.car', ':car'))
            ->setParameter('time', $o->getEndTime())
            ->setParameter('car', $o->getCar())
        ;

        if($o->getId()){
            $qb2 = $qb2->andWhere($qb2->expr()->neq('r.id',':id'))
                ->setParameter('id', $o->getId())
            ;
        }
        $car_discount_end_time =
            $qb2
                ->getQuery()
                ->getResult()
        ;

        if(!empty( $car_discount_end_time) ) {
            return true;
        }

        $qb3 = $this->em->createQueryBuilder();
        $qb3 = $qb3->select('r')
            ->from('AutoManagerBundle:CarDiscount', 'r')
            ->andWhere($qb3->expr()->gte('r.startTime',':startTime'))
            ->andWhere($qb3->expr()->lte('r.endTime',':endTime'))
            ->andwhere($qb3->expr()->eq('r.car', ':car'))
            ->setParameter('startTime', $o->getStartTime())
            ->setParameter('endTime', $o->getEndTime())
            ->setParameter('car', $o->getCar())
        ;

        if($o->getId()){
            $qb3 = $qb3->andWhere($qb3->expr()->neq('r.id',':id'))
                ->setParameter('id', $o->getId())
            ;
        }
        $discount_time =
            $qb3
                ->getQuery()
                ->getResult()
        ;

        if(!empty( $discount_time) ) {
            return true;
        }

        return false;

    }




    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }


}