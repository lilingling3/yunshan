<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/17
 * Time: 下午4:25
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class CouponHelper extends AbstractHelper{

    const FIR_STEP = 3600*1;
    const SEC_STEP = 3600*2;
    const THI_STEP = 3600*4;
    const FOU_STEP = 3600*8;

    public function get_coupon_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Coupon $c,$orderValid= null) {

            $coupon = [
                'couponID'=>$c->getId(),
                'name'=>$c->getKind()->getName(),
                'amount'=>$c->getKind()->getAmount(),
                'endTime'=>$c->getEndTime()->format('Y/m/d'),
                'needHour'=>$c->getKind()->getNeedHour(),
                'needAmount'=>$c->getKind()->getNeedAmount(),
                'carLevel'=>empty($c->getKind()->getCarLevel())?'':$c->getKind()->getCarLevel()->getName(),
                'valid'=>$this->get_coupon_status($c),
                'orderValid'=>$orderValid
            ];

            return $coupon;
        };
    }

    public function get_order_coupon_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Coupon $c) {

            $coupon = [
                'couponID'=>$c->getId(),
                'name'=>$c->getKind()->getName(),
                'amount'=>$c->getKind()->getAmount(),
                'endTime'=>$c->getEndTime()->format('Y/m/d'),
                'needHour'=>$c->getKind()->getNeedHour(),
                'needAmount'=>$c->getKind()->getNeedAmount(),
                'carLevel'=>empty($c->getKind()->getCarLevel())?'':$c->getKind()->getCarLevel()->getName(),
                'valid'=>$this->get_coupon_status($c),
                'orderValid'=>\Auto\Bundle\ManagerBundle\Entity\Coupon::ORDER_COUPON_USABLE
            ];

            return $coupon;
        };
    }

    public function get_coupon_status(\Auto\Bundle\ManagerBundle\Entity\Coupon $c){

        $status = \Auto\Bundle\ManagerBundle\Entity\Coupon::COUPON_USABLE;
        if($c->getEndTime()->format('Y/m/d')<date('Y/m/d')){
            $status = \Auto\Bundle\ManagerBundle\Entity\Coupon::COUPON_EXPIRE;//过期
        }
        if($c->getUseTime()){
            $status = \Auto\Bundle\ManagerBundle\Entity\Coupon::COUPON_USED;//已用
        }
        return $status;
    }

    public function get_member_coupon_count($member){
        $qb = $this->em->createQueryBuilder();

        $coupon_count =
            $qb
                ->select($qb->expr()->count('c'))
                ->from('AutoManagerBundle:Coupon', 'c')
                ->join('c.kind','k')
                ->where($qb->expr()->eq('c.member', ':member'))
                ->andWhere($qb->expr()->gte('c.endTime',':endTime'))
                ->andWhere($qb->expr()->isNull('c.useTime'))
                ->setParameter('member', $member)
                ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
                ->getQuery()
                ->getSingleScalarResult()
        ;

        return $coupon_count;
    }

    public function send_coupon(\Auto\Bundle\ManagerBundle\Entity\Member $member=null,
                                \Auto\Bundle\ManagerBundle\Entity\CouponKind
$kind,\Auto\Bundle\ManagerBundle\Entity\CouponActivity $activity=null,$mobile='',$offset=null){

        $date = (new \DateTime((new \DateTime('+'.$kind->getValidDay().' days'))->format('Y-m-d')));
        $coupon = new \Auto\Bundle\ManagerBundle\Entity\Coupon();
        $coupon
            ->setKind($kind)
            ->setEndTime($date)
        ;

        if (!empty($offset)) {
            $coupon->setCreateTime(new \DateTime('-'.$offset.'seconds'));
        } else {
            $coupon->setCreateTime(new \DateTime());
        }

        if(!empty($member)){
            $coupon->setMember($member);
        }

        if(!empty($activity)){
            $coupon->setActivity($activity);
        }
        if($mobile && empty($member)){
            $coupon->setMobile($mobile);
        }

        $man = $this->em;
        $man->persist($coupon);
        $man->flush();

        return $coupon;
    }

    public function send_activity_coupon(\Auto\Bundle\ManagerBundle\Entity\CouponActivity $activity,
                                         \Auto\Bundle\ManagerBundle\Entity\Member $member=null,$mobile=''){

        $amount = 0;

        if(count($activity->getKinds()) == $activity->getCount()){

            foreach($activity->getKinds() as $key => $kind){

                $this->send_coupon($member,$kind,$activity,$mobile,$key%$activity->getCount());
                $amount=$amount+$kind->getAmount();

            }
            // foreach($activity->getKinds() as $kind){

            //     $this->send_coupon($member,$kind,$activity,$mobile);
            //     $amount=$amount+$kind->getAmount();

            // }
        }else{

            $kinds = $activity->getKinds()->toArray();
            $total_kind = count($kinds);

            for($i=0;$i<$activity->getCount();$i++){
                $kind = $kinds[rand(0,$total_kind-1)];
                $this->send_coupon($member,$kind,$activity,$mobile,1);
                $amount=$amount+$kind->getAmount();
            }

        }

        $send_mobile = empty($member)?$mobile:$member->getMobile();

        $this->smsHelper->add($send_mobile,'亲~ 我们已经为您献上了'.$amount.'元优惠券，记得在有效期内使用不要浪费哦。如有疑问，请联系客服400-111-8220');

        return $amount;

    }

    /**
     * 活动入口
     * @param  \Auto\Bundle\ManagerBundle\Entity\RentalOrder $order [订单]
     * @return -
     */
    public function coupon_activity_entrance(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order) {

        if (!empty($order)) {

            $this->send_coupon_activity($order);
        }
    }

    /**
     * 深圳驾呗推广活动
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalOrder $order
     * @return -
     */
    public function send_coupon_activity(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order) {

        // 起止时间
        $start_time = strtotime("2017-04-29 00:00:00");
        $end_time   = strtotime("2017-05-31 23:59:59");
        $create_time = $order->getCreateTime()->getTimestamp();


        if($start_time < $create_time && $create_time < $end_time)
        {

            // 获得订单结束时间
            $order_endtime = !empty($order->getCancelTime()) ? $order->getCancelTime()->getTimestamp()
                                                             : $order->getEndTime()->getTimestamp()
            ;
            // file_put_contents( '/data/www/data/test-car.log', '1start&', FILE_APPEND);
            // 计算订单持续时间
            $order_period = $order_endtime - $create_time;
            
            // file_put_contents( '/data/www/data/test-car.log', 'period:'. $order_period .'&', FILE_APPEND);
            // 取车点是不是深圳
            $qb = $this->em->createQueryBuilder();
            $area = 
                $qb
                    ->select('a')
                    ->from('AutoManagerBundle:Area', 'a')
                    ->where($qb->expr()->like('a.name',':name'))
                    ->setParameter('name', "深圳市")
                    ->getQuery()
                    ->getOneorNullResult();

            // file_put_contents( '/data/www/data/test-car.log', 'area:'. $area->getId() .'&', FILE_APPEND);

            if (empty($area)) {
                file_put_contents( '/data/www/data/test-car.log', 'area: is null&', FILE_APPEND);
                return false;
            }

            $redis = $this->container->get('snc_redis.default');

            $redis_cmd = $redis->createCommand('smembers',array("city-station-list-".$area->getId()));
            $stationList = $redis->executeCommand($redis_cmd);

            // file_put_contents( '/data/www/data/test-car.log', 'stationList:'. json_encode($stationList) .'&', FILE_APPEND);
            if (empty($stationList)) {
                
                // file_put_contents( '/data/www/data/test-car.log', 'stationList: is null&', FILE_APPEND);
                $qbs = $this->em->createQueryBuilder();

                $stationList = 
                    $qbs
                        ->select('s')
                        ->from('AutoManagerBundle:Station', 's')
                        ->join('s.area','j')
                        ->where($qbs->expr()->orX(
                            $qbs->expr()->eq('s.area', ':area'),
                            $qbs->expr()->eq('j.parent', ':area')
                        ))
                        ->setParameter('area', $area)
                        ->getQuery()
                        ->getResult()
                ;

                if (empty($stationList)) {
                    file_put_contents( '/data/www/data/test-car.log', 'stations: is null&', FILE_APPEND);
                    return false;
                }

                $stations = [];
                foreach ($stationList as $key => $value) {
                    
                    // file_put_contents( '/data/www/data/test-car.log', 'stationid: '.$value->getId().'&', FILE_APPEND);
                    $stations[] = $value->getId();


                }
                
                // file_put_contents( '/data/www/data/test-car.log', 'stations: is null&', FILE_APPEND);
                $redis_cmd = $redis->createCommand('sadd',array("city-station-list-".$area->getId(), $stations));
                $redis->executeCommand($redis_cmd);

                $redis_cmd = $redis->createCommand('EXPIRE',array("city-station-list-".$area->getId(), 3600*1));
                $redis->executeCommand($redis_cmd);
            }

            $redis_cmd = $redis->createCommand('SISMEMBER',array("city-station-list-".$area->getId(), $order->getPickUpStation()->getId()));
            $isMember = $redis->executeCommand($redis_cmd);
            // file_put_contents( '/data/www/data/test-car.log', 'isMember: '. $isMember .'&', FILE_APPEND);
            if (!$isMember) {
                file_put_contents( '/data/www/data/test-car.log', 'isMember: false&', FILE_APPEND);
                return false;
            }

            $coupon_kind = "";

            if ($order_period >= self::FIR_STEP) {
             
                if ($order_period >= self::SEC_STEP) {
                    
                    if ($order_period >= self::THI_STEP) {
                    
                        if ($order_period >= self::FOU_STEP) {
                            $coupon_kind = 36; // 第四阶梯
                        } else {
                            $coupon_kind = 35; // 第三阶梯
                        }
                    } else {
                        $coupon_kind = 34; // 第二阶梯
                    }
                } else {
                    $coupon_kind = 33; // 第一阶梯
                }

            }

            // 发券
            if (!empty($coupon_kind)) {

                
                $qbc = $this->em->createQueryBuilder();
                $kind = 
                    $qbc
                        ->select('k')
                        ->from('AutoManagerBundle:CouponKind', 'k')
                        ->where($qbc->expr()->eq('k.id',':kind'))
                        ->setParameter('kind', $coupon_kind)
                        ->getQuery()
                        ->getOneorNullResult();
                // file_put_contents( '/data/www/data/test-car.log', 'kind:'. $kind->getId() .'&', FILE_APPEND);
                if (empty($kind)) {
                    file_put_contents( '/data/www/data/test-car.log', 'kind: false&', FILE_APPEND);
                    return false;
                }
                
                $this->send_coupon($order->getMember(), $kind);
            }
            
        }
    }


    public function send_qingming_coupon(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $order) {

        $start_time = strtotime("2017-04-02 00:00:00");
        $end_time   = strtotime("2017-04-04 23:59:59");
        $now_time = $order->getCreateTime()->getTimestamp();
        if($start_time < $now_time && $now_time < $end_time)
        {
           // $canceltime = $order->getCancelTime();
            $canceltime = $order->getUseTime();
            if(empty($canceltime)){
                $canceltimeok = false;
            }else{
                $canceltimeok = true;
            }
            $activity_id = $order->getMember()->getId();
            $mobile = $order->getMember()->getMobile();
            $redis = $this->container->get('snc_redis.default');
            $redis_cmd= $redis->createCommand('GET',array("activaty-qingming-".$activity_id));
            $flag = $redis->executeCommand($redis_cmd);
            if(!$flag && true == $canceltimeok){
                $redis_cmd= $redis->createCommand('SET',array("activaty-qingming-".$activity_id,$mobile));
                $redis->executeCommand($redis_cmd);
                $redis_cmd_gps= $redis->createCommand('EXPIRE',array("activaty-qingming-".$activity_id,259200));
                $redis->executeCommand($redis_cmd_gps);
                $coupon = true;
            }else{
                $coupon = false;
            }
            if($coupon){

                $qb = $this->em->createQueryBuilder();
                $activity = 
                    $qb
                        ->select('c')
                        ->from('AutoManagerBundle:CouponActivity', 'c')
                        ->where($qb->expr()->eq('c.id', ':id'))
                        ->setParameter('id', 6)
                        ->getQuery()
                        ->getOneorNullResult()
                ;

                $this->send_activity_coupon($activity,$order->getMember());
            }
        }
    }


    public function setSMSHelper($smsHelper)
    {
        $this->smsHelper = $smsHelper;
    }


}