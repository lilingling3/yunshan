<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/17
 * Time: 下午4:25
 */

namespace Auto\Bundle\ManagerBundle\Helper;


use Symfony\Component\Validator\Constraints\DateTime;

class OrderHelper extends AbstractHelper{
    public function get_rental_order_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o) {


            $create_time = new \DateTime($o->getCreateTime()->format('Y-m-d H:i:s'));

            if($o->getCancelTime()){
                $offset = $o->getCancelTime()->getTimestamp() - $o->getCreateTime()->getTimestamp();
            }else{
                $offset = ($o->getEndTime()?$o->getEndTime()->getTimestamp():(new \DateTime())->getTimestamp())
                    -$o->getCreateTime()->getTimestamp();
            }
            $box_password = '';

            if(!empty($o->getUseTime())&&empty($o->getEndTime())){

                $redis_cmd = $this->redisHelper->createCommand('get',array($o->getRentalCar()->getDeviceCompany()
                        ->getEnglishName().'-password-'.$o->getRentalCar()->getBoxId()));

                $box_password = $this->redisHelper->executeCommand($redis_cmd);

            }

            

            $order = [
                'orderID'                   => $o->getId(),
                'type'                      =>1,//使用类型 租赁
                'createOffSeconds'          =>($create_time->modify("+15 minutes")->getTimestamp() - (new
            \DateTime())->getTimestamp())>0?$create_time->getTimestamp() - (new
            \DateTime())->getTimestamp():0
                    ,
                'amount'                    =>$o->getAmount(),
                'walletAmount'              =>$o->getWalletAmount()?$o->getWalletAmount():0,
                //减免金额
                'reliefAmount'              =>$o->getReliefAmount()?$o->getReliefAmount():0,
                'status'                    =>$this->get_order_status($o),
                'createTime'                =>$o->getCreateTime()->format('Y/m/d H:i'),
                'useTime'                    =>$o->getUseTime()?$o->getUseTime()->format('Y/m/d H:i'):null,
                'endTime'                   =>$o->getEndTime()?$o->getendTime()->format('Y/m/d H:i'):null,
                'payTime'                   =>$o->getPayTime()?$o->getPayTime()->format('Y/m/d H:i'):null,
                'cancelTime'                =>$o->getCancelTime()?$o->getCancelTime()->format('Y/m/d H:i'):null,
                'offset'                    =>$offset,
                'mileage'                   =>$this->get_rental_order_mileage($o),
                'rentalCar'                 => call_user_func($this->rentalCarHelper->get_rental_car_normalizer(),$o->getRentalCar(),12*60),
                'coupon'                    =>$o->getCoupon()?call_user_func($this->couponHelper->get_order_coupon_normalizer(),$o->getCoupon()):null,
                'costDetail'                => $this->get_charge_details($o),
                'returnRentalStation'       =>call_user_func($this->stationHelper->get_station_normalizer(),
                    empty($o->getReturnStation())?$o->getRentalCar()->getRentalStation():$o->getReturnStation()),
                'pickUpRentalStation'       =>call_user_func($this->stationHelper->get_station_normalizer(),
                    empty($o->getReturnStation())?$o->getRentalCar()->getRentalStation():$o->getPickUpStation()),
                'boxPassword'               =>$box_password?$box_password:'',

                // 完成订单对应车辆租赁点折扣
                'rentalStationDiscount'      =>$this->order_rental_station_discount($o),

                'orderCarDiscount'               =>$this->order_car_discount($o),

            ];

            return $order;
        };
    }





    //2.4.0
    public function get_rental_order_data_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o) {


            $create_time = new \DateTime($o->getCreateTime()->format('Y-m-d H:i:s'));

            if($o->getCancelTime()){
                $offset = $o->getCancelTime()->getTimestamp() - $o->getCreateTime()->getTimestamp();
            }else{
                $offset = ($o->getEndTime()?$o->getEndTime()->getTimestamp():(new \DateTime())->getTimestamp())
                    -$o->getCreateTime()->getTimestamp();
            }
            $box_password = '';

            if(!empty($o->getUseTime())&&empty($o->getEndTime())){

                $redis_cmd = $this->redisHelper->createCommand('get',array($o->getRentalCar()->getDeviceCompany()
                        ->getEnglishName().'-password-'.$o->getRentalCar()->getBoxId()));

                $box_password = $this->redisHelper->executeCommand($redis_cmd);

            }



            $order = [
                'orderID'                   => $o->getId(),
                'type'                      =>1,//使用类型 租赁
                'createOffSeconds'          =>($create_time->modify("+15 minutes")->getTimestamp() - (new
                    \DateTime())->getTimestamp())>0?$create_time->getTimestamp() - (new
                    \DateTime())->getTimestamp():0
                ,
                'amount'                    =>$o->getAmount(),
                'walletAmount'              =>$o->getWalletAmount()?$o->getWalletAmount():0,
                //减免金额
                'reliefAmount'              =>$o->getReliefAmount()?$o->getReliefAmount():0,
                'status'                    =>$this->get_order_status($o),
                'createTime'                =>$o->getCreateTime()->format('Y/m/d H:i'),
                'useTime'                    =>$o->getUseTime()?$o->getUseTime()->format('Y/m/d H:i'):null,
                'endTime'                   =>$o->getEndTime()?$o->getendTime()->format('Y/m/d H:i'):null,
                'payTime'                   =>$o->getPayTime()?$o->getPayTime()->format('Y/m/d H:i'):null,
                'cancelTime'                =>$o->getCancelTime()?$o->getCancelTime()->format('Y/m/d H:i'):null,
                'offset'                    =>$offset,
                'mileage'                   =>$this->get_rental_order_mileage($o),
                'rentalCar'                 => call_user_func($this->rentalCarHelper->get_rental_car_data_normalizer(),$o->getRentalCar(),12*60),
                'coupon'                    =>$o->getCoupon()?call_user_func($this->couponHelper->get_order_coupon_normalizer(),$o->getCoupon()):null,
                'costDetail'                => $this->get_charge_details($o),
                'returnRentalStation'       =>call_user_func($this->stationHelper->get_station_data_normalizer(),
                    empty($o->getReturnStation())?$o->getRentalCar()->getRentalStation():$o->getReturnStation()),
                'pickUpRentalStation'       =>call_user_func($this->stationHelper->get_station_data_normalizer(),
                    empty($o->getReturnStation())?$o->getRentalCar()->getRentalStation():$o->getPickUpStation()),
                'boxPassword'               =>$box_password?$box_password:'',

                // 完成订单对应车辆租赁点折扣
                'rentalStationDiscount'      =>$this->order_rental_station_discount($o),

                'orderCarDiscount'               =>$this->order_car_discount($o),

            ];

            return $order;
        };
    }





    //2.4.0订单中的对应租赁点折扣信息获取
    public function order_rental_station_discount(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o){

        $qb = $this->em->createQueryBuilder();

        $rental_station_discount =
            $qb
                ->select('r')
                ->from('AutoManagerBundle:RentalStationDiscount', 'r')
                ->andWhere($qb->expr()->lte('r.startTime',':time'))
                ->andWhere($qb->expr()->gte('r.endTime',':time'))
                ->andwhere($qb->expr()->eq('r.rentalStation', ':rentalStation'))
                ->setParameter('time', $o->getCreateTime())
                ->setParameter('rentalStation', $o->getPickUpStation())
                ->getQuery()
                ->getOneOrNullResult()
        ;


        if(!empty($rental_station_discount)) {

            $station_discount = [
                'kind' => $rental_station_discount->getKind(),
                'startTime' => $rental_station_discount->getStartTime()->format('Y/m/d H:i'),
                'endTime ' => $rental_station_discount->getEndTime()->format('Y/m/d H:i'),
                'discount' => $rental_station_discount->getDiscount(),
            ];

        }else{

            $station_discount = [
                'kind' => '',
                'startTime' => '',
                'endTime ' => '',
                'discount' => '1',

            ];


        }

                return  $station_discount;

    }

    /**
     * 每日日报数据
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalOrder $o
     *
     * @return array
     */
    public function get_daily_data_normalizer(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o) {

        // 计算用车时长
        $create_time = new \DateTime($o->getCreateTime()->format('Y-m-d H:i:s'));

        if($o->getCancelTime()){
            $offset = $o->getCancelTime()->getTimestamp() - $o->getCreateTime()->getTimestamp();
        }else{
            $offset = ($o->getEndTime()?$o->getEndTime()->getTimestamp():(new \DateTime())->getTimestamp())
                - $o->getCreateTime()->getTimestamp();
        }

        $periodTime = number_format($offset / 3600, 2);
        $day = intval($offset / 86400);
        $hour= intval(intval($offset / 3600) % 24);
        $minute = intval(intval($offset / 60) % 60);
        $secend = intval($offset % 60);

        $status = "";
        // order status
        if (empty($o->getCancelTime())) {

            if (empty($o->getEndTime())) {
                $status = "租赁中";
            } else if(empty($o->getPayTime())) {
                $status = "待支付";
            } else {
                $status = "已完成";
            }
            
        } else {
            $status = "已取消";
        }


        $order = [
            'userMobile'        => $o->getMember()->getMobile(),
            'userName'          => $o->getMember()->getName(),
            'licenseplate'      => $o->getRentalCar()->getLicensePlace()->getName().$o->getRentalCar()->getLicensePlate(),
            'carModel'          => $o->getRentalCar()->getCar()->getName(),
            'createTime'        => $o->getCreateTime()->format('Y/m/d H:i'),
            'useTime'           => $o->getUseTime() ? $o->getUseTime()->format('Y/m/d H:i') : null,
            'endTime'           => $o->getEndTime() ? $o->getendTime()->format('Y/m/d H:i') : null,
            'costTime'          => $day."天".$hour."小时".$minute."分钟".$secend."秒",
            'orderStatus'       => $status,
            'dueAmout'          => $o->getDueAmount() ? $o->getDueAmount() : 0,
            'mileage'           => $this->get_rental_order_mileage($o),
            'pickUpStation'     => $o->getPickUpStation()->getName(),
            'costTimeD'         => $periodTime,
            'actualAmount'      => $o->getAmount() ? $o->getAmount() : 0,
            'coupon'            => $o->getCoupon() ? $o->getCoupon()->getKind()->getAmount() : null,
            'walletAmount'      => $o->getWalletAmount() ? $o->getWalletAmount() : 0,
            'rentalType'        => $periodTime > 6 ? "长租" : "短租",
            'rentalPeriod'      => $o->getCreateTime()->format('H')
        ];

        return $order;

    }


    //获取订单中用户下单时候的车辆折扣
    public function order_car_discount(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o){


        $qb = $this->em->createQueryBuilder();


        $carDiscount =
            $qb
                ->select('c')
                ->from('AutoManagerBundle:CarDiscount', 'c')
                ->andWhere($qb->expr()->lte('c.startTime',':time'))
                ->andWhere($qb->expr()->gte('c.endTime',':time'))
                ->andwhere($qb->expr()->eq('c.car', ':car'))
                ->setParameter('time', $o->getCreateTime())
                ->setParameter('car',$o->getRentalCar()->getCar())
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






    public function get_rental_order_mileage(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o){

        if(!$o->getStartMileage()){

            return $o->getMileage()?round($o->getMileage()/1000,2):0;

        }else{
            $end_mileage = $o->getEndMileage();

            if(!$end_mileage){
                
                $redis_cmd = $this->redisHelper->createCommand('lindex',array($o->getRentalCar()->getDeviceCompany()
                        ->getEnglishName()."-mileage-".$o->getRentalCar()->getBoxId(),0));

                $mileage_arr = $this->redisHelper->executeCommand($redis_cmd);

                $mileage_arr = json_decode($mileage_arr,true);
                $end_mileage = $mileage_arr['mileage'];
            }

            return $end_mileage - $o->getStartMileage();

        }
    }

    public function get_operator_rental_order_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o) {


            $a = $this->get_charge_details($o);


            $create_time = new \DateTime($o->getCreateTime()->format('Y-m-d H:i:s'));

            if($o->getCancelTime()){
                $offset = $o->getCancelTime()->getTimestamp() - $o->getCreateTime()->getTimestamp();
            }else{
                $offset = ($o->getEndTime()?$o->getEndTime()->getTimestamp():(new \DateTime())->getTimestamp())
                    -$o->getCreateTime()->getTimestamp();
            }

//            $offset = $o->getUseTime()?($o->getEndTime()?$o->getEndTime()->getTimestamp():(new \DateTime())
//                    ->getTimestamp())
//                -$o->getUseTime()->getTimestamp():0;

            $order = [
                'orderID'                   => $o->getId(),
                'type'                      =>1,//使用类型 租赁
                'createOffSeconds'          =>($create_time->modify("+15 minutes")->getTimestamp() - (new
                    \DateTime())->getTimestamp())>0?$create_time->getTimestamp() - (new
                    \DateTime())->getTimestamp():0
                ,
                'userMobile'                =>$o->getMember()->getMobile(),
                'userName'                  =>$o->getMember()->getName(),
                'amount'                    =>$o->getAmount(),
                'status'                    =>$this->get_order_status($o),
                'createTime'                =>$o->getCreateTime()->format('Y/m/d H:i'),
                'useTime'                   =>$o->getUseTime()?$o->getUseTime()->format('Y/m/d H:i'):null,
                'endTime'                   =>$o->getEndTime()?$o->getendTime()->format('Y/m/d H:i'):null,
                'payTime'                   =>$o->getPayTime()?$o->getPayTime()->format('Y/m/d H:i'):null,
                'offset'                    =>$offset,
                'mileage'                   =>$o->getMileage()?round($o->getMileage()/1000,2):0,
                'rentalCar'                 => call_user_func($this->rentalCarHelper->get_rental_car_normalizer(),$o->getRentalCar(),12*60),
                'coupon'                    =>$o->getCoupon()?call_user_func($this->couponHelper->get_order_coupon_normalizer(),$o->getCoupon()):null,
                'costDetail'                => $this->get_charge_details($o),
                'returnRentalStation'       =>call_user_func($this->stationHelper->get_station_normalizer(),
                    empty($o->getReturnStation())?$o->getRentalCar()->getRentalStation():$o->getReturnStation()),
                'pickUpRentalStation'       =>call_user_func($this->stationHelper->get_station_normalizer(),
                    empty($o->getReturnStation())?$o->getRentalCar()->getRentalStation():$o->getPickUpStation()),
            ];

            return $order;
        };
    }


    public function get_member_end_order_count(\Auto\Bundle\ManagerBundle\Entity\Member $member=null){
        $qb = $this->em->createQueryBuilder();

        $order_count =
            $qb
                ->select($qb->expr()->count('o'))
                ->from('AutoManagerBundle:BaseOrder', 'o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNotNull('o.endTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->andWhere($qb->expr()->isNotNull('o.payTime'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getSingleScalarResult()
        ;
        return $order_count;
    }




    public function get_order_status(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o){
        $status = \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER;

        if($o->getUseTime()){
            $status = \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER;
        }
        if($o->getEndTime()){
            $status = \Auto\Bundle\ManagerBundle\Entity\RentalOrder::BACK_NO_PAY_ORDER;
        }
        if($o->getPayTime()){
            $status = \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PAYED_ORDER;
        }
        if($o->getCancelTime()){
            $status = \Auto\Bundle\ManagerBundle\Entity\RentalOrder::CANCEL_ORDER;
        }

        return $status;

    }



    public function get_charge_details (\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o){

        $charge = $this->get_rental_order_cost($o);

        $charge['cost'] = number_format($charge['cost'], 2, '.', '');
        return ['charge'=>$charge,'cost'=>$charge['cost']];
    }


    public function get_rental_order_cost(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $o)
    {
        if(!empty($o->getCancelTime())) return null;
        $start_time = $o->getCreateTime()->format('Y-m-d H:i:s');
        $end_time = $o->getEndTime()?$o->getEndTime()->format('Y-m-d H:i:s'):date('Y-m-d H:i:s');
        $minute = floor((strtotime($end_time) - strtotime($start_time))/(60));

        $rentalPrice = array_map($this->rentalPriceHelper->get_rental_price_normalizer(),$this->rentalPriceHelper->get_rental_order_price($o));



        foreach($rentalPrice as $key=>$price){


            if($minute>=$price['minHour']*60){    //当命中

                if(isset($rentalPrice[$key-1]['maxHour'])){   //当已经计算过上一个计价

                    if($price['maxHour']!=0){

                        if($minute>$price['maxHour']*60){  //当时间超过当前计价范围

                            $rentalPrice[$key]['amount'] = ($price['maxHour']-$price['minHour'])*60*$price['price'];
                            $rentalPrice[$key]['time'] = ($price['maxHour']-$price['minHour'])*60*60;

                        }else{    //当在当前计价范围

                            $rentalPrice[$key]['amount'] =number_format(($minute-$price['minHour']*60)*($price['price']), 2, '.', '');
                            $rentalPrice[$key]['time'] = ($minute-$price['minHour']*60)*60;
                        }
                    }else{

                            $rentalPrice[$key]['amount'] =number_format(($minute-$price['minHour']*60)*($price['price']), 2, '.', '');
                            $rentalPrice[$key]['time'] = ($minute-$price['minHour']*60)*60;

                    }




                }else{   //当没有计算过上一个计价

                    if($minute>$price['maxHour']*60){  //当时间超过当前计价范围

                        $rentalPrice[$key]['amount'] = $price['maxHour']*60*$price['price'];
                        $rentalPrice[$key]['time'] = $price['maxHour']*60*60;

                    }else{    //当在当前计价范围


                        if ($o->getCreateTime()->getTimestamp() <= strtotime("2017-04-28 20:40:59")) {

                            if($minute<=30){

                                $rentalPrice[$key]['amount'] =number_format(30*$price['price'], 2, '.', '');
                                $rentalPrice[$key]['time'] = $minute*60;

                            }else{
                                $rentalPrice[$key]['amount'] = number_format($minute*$price['price'], 2, '.', '');
                                $rentalPrice[$key]['time'] = $minute*60;
                            }

                        }else {

                            if($minute<=15){

                                $rentalPrice[$key]['amount'] =number_format(15*$price['price'], 2, '.', '');
                                $rentalPrice[$key]['time'] = $minute*60;

                            }else{
                                $rentalPrice[$key]['amount'] = number_format($minute*$price['price'], 2, '.', '');
                                $rentalPrice[$key]['time'] = $minute*60;
                            }
                        }
                    }

                }



            }else{
                break;
            }

        }


        $cost = array_reduce($rentalPrice,function($v1,$v2){
            return $v1+$v2['amount'];
        });



        return ['rentalPrice'=>$rentalPrice,'cost'=>$cost];


    }

    public function get_progress_rental_order($member){

        $qb = $this->em->createQueryBuilder();
        $order =
            $qb
                ->select('o')
                ->from('AutoManagerBundle:RentalOrder', 'o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNull('o.endTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->setParameter('member', $member)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;
        return empty($order)?null:$order;

    }




    public function get_progress_rental_order_by_car($rentalCar){

        $qb = $this->em->createQueryBuilder();
        $order =
            $qb
                ->select('o')
                ->from('AutoManagerBundle:RentalOrder', 'o')
                ->where($qb->expr()->eq('o.rentalCar', ':rentalCar'))
                ->andWhere($qb->expr()->isNull('o.endTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->setParameter('rentalCar', $rentalCar)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;
        return empty($order)?null:$order;

    }


    public  function  get_no_pay_rental_order($member){
        $qb = $this->em->createQueryBuilder();
        $order =
            $qb
                ->select('o')
                ->from('AutoManagerBundle:RentalOrder', 'o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNull('o.payTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->andWhere($qb->expr()->isNotNull('o.endTime'))
                ->setParameter('member', $member)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;
        return empty($order)?null:$order->getId();

    }



    public function setRentalPriceHelper($rentalPriceHelper)
    {
        $this->rentalPriceHelper = $rentalPriceHelper;
    }

    public function setRentalCarHelper($rentalCarHelper)
    {
        $this->rentalCarHelper = $rentalCarHelper;
    }

    public function setCouponHelper($couponHelper)
    {
        $this->couponHelper = $couponHelper;
    }

    public function setStationHelper($stationHelper)
    {
        $this->stationHelper = $stationHelper;
    }

    public function setRedisHelper($redisHelper){

        $this->redisHelper = $redisHelper;

    }

}
