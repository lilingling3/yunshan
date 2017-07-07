<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/5/30
 * Time: 17:39
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StatisticsOperateCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:statistics:operate')
            ->setDescription('statistics operate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set("PRC");
        $date = date('Y-m-d',strtotime("-1 day"));
        //$date = '2016-05-15';//测试代码需要注释
        echo $date;
        $this->statisticsOperateHistory($date);
        dump( ' Operate OK ' );
//        for($i = 30; $i > 0; $i--){
//            $date = date('Y-m-d',strtotime("-$i day"));
//            echo $date;
//            $this->statisticsOperateHistory($date);
//            //sleep(2);//每个租赁点统计间隔2秒
//            dump( ' Operate OK ' );
//        }
    }

    protected function statisticsOperateHistory($date){
        $start_time = $date.' 00:00:00';
        $end_time = $date.' 23:59:59';
        $man = $this->getContainer()->get('doctrine')->getManager();
        //查找记录是否已经存在
        $statisticsRecord=$man
            ->getRepository('AutoManagerBundle:StatisticsOperateRecord')
            ->findOneBy(['dateTime'=>new \DateTime($date)]);
        if(!empty($statisticsRecord)){
            return true;
        }
        $this->statisticsOperateHistoryTwoDay();

        $qbrc =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('o');
        $rentalCarCounts =
            $qbrc->select($qbrc->expr()->count('o'))
                ->getQuery()
                ->getSingleScalarResult();
        $pagesize = 100;
        $pages = intval(ceil($rentalCarCounts/$pagesize));
        for($i=0;$i<$pages;$i++){
            $rentalCarAll =
                $qbrc->select('o')
                    ->orderBy('o.id', 'ASC')
                    ->setFirstResult( $i*$pagesize )
                    ->setMaxResults( $pagesize )
                    ->getQuery()
                    ->getResult();
            foreach ($rentalCarAll as $rentalCar) {
                //获取当前车辆，start_time与end_time之间、状态为1的所有移库记录
                $qbdrc =
                    $man
                        ->getRepository('AutoManagerBundle:DispatchRentalCar')
                        ->createQueryBuilder('o');
                $dispatchAlls =
                    $qbdrc
                        ->select('o')
                        //->leftJoin('o.rentalOrder','ro')
                        ->andWhere($qbdrc->expr()->gte('o.createTime', ':startTime'))
                        ->andWhere($qbdrc->expr()->lte('o.createTime', ':endTime'))
                        ->andWhere($qbdrc->expr()->eq('o.status', ':status'))
                        ->andWhere($qbdrc->expr()->eq('o.rentalCar', ':rentalCar'))
                        ->setParameter('status', 1)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->setParameter('rentalCar', $rentalCar)
                        ->orderBy('o.createTime', 'ASC')
                        ->getQuery()
                        ->getResult();

                $qbdrc =
                    $man
                        ->getRepository('AutoManagerBundle:DispatchRentalCar')
                        ->createQueryBuilder('o');
                $query =
                    $qbdrc
                        ->select('o')
                        ->andWhere($qbdrc->expr()->lt('o.createTime', ':startTime'))
                        ->andWhere($qbdrc->expr()->eq('o.status', ':status'))
                        ->andWhere($qbdrc->expr()->eq('o.rentalCar', ':rentalCar'))
                        ->setParameter('startTime', $start_time)
                        ->setParameter('status', 1)
                        ->setParameter('rentalCar', $rentalCar)
                        ->orderBy('o.createTime', 'DESC')
                        ->getQuery()
                        ->setMaxResults(1);
                try {
                    $dispatchLast =  $query->getSingleResult();
                } catch (\Doctrine\ORM\NoResultException $e) {
                    $dispatchLast = null;
                }
                $resultArr = array();
                $rental_station_temp = null;
                $startTimestamp = strtotime($start_time);
                $dispatchAllCount = count($dispatchAlls);

                if($dispatchLast){
                    if($dispatchLast->getKind() == 2){//如果是订单移车
                        $rentalOrderId = $dispatchLast->getRentalOrder()->getId();
                        $rentalOrder=$man
                            ->getRepository('AutoManagerBundle:RentalOrder')
                            ->findOneBy(['id'=>$rentalOrderId]);
                        if(!$rentalOrder){
                            echo ' error data！ ';
                        }else{
                            if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $start_time){
                                $rental_station_id = $rentalOrder->getPickUpStation()->getId();
                                $tempTime = $rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $end_time ? (strtotime($end_time)+1) : $rentalOrder->getEndTime()->getTimestamp();
                                $stay_time = $tempTime - $startTimestamp;
                                if(!isset($resultArr[$rental_station_id])){
                                    $resultArr[$rental_station_id] = $this->arrayInit($date,$rentalOrder->getPickUpStation(),$rentalCar);
                                }
                                $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                $startTimestamp = $tempTime;
                                //重新定义订单
                                $rentalOrderTemp = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
                                $rentalOrderTemp->setRentalCar($rentalOrder->getRentalCar());
                                $rentalOrderTemp->setPickUpStation($rentalOrder->getPickUpStation());
                                $rentalOrderTemp->setReturnStation($rentalOrder->getReturnStation());
                                $rentalOrderTemp->setUseTime($rentalOrder->getUseTime());
                                $rentalOrderTemp->setCreateTime(new \DateTime($start_time));
                                $rentalOrderTemp->setEndTime($rentalOrder->getEndTime());
                                //订单计算
                                $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrderTemp);
                                if(!is_array($order_cost)){
                                    echo ' order_cost is empty ';
                                    continue;
                                }
                                if($order_cost['rentalPrice'][0]['name'] == '日间'){
                                    $day_cost = $order_cost['rentalPrice'][0];
                                    $night_cost = $order_cost['rentalPrice'][1];
                                }else{
                                    $day_cost = $order_cost['rentalPrice'][1];
                                    $night_cost = $order_cost['rentalPrice'][0];
                                }
                                //dump($order_cost);//die;

                                $resultArr[$rental_station_id]['dayRentalTime'] += $day_cost['time'];
                                $resultArr[$rental_station_id]['nightRentalTime'] += $night_cost['time'];
                                //营收
                                $resultArr[$rental_station_id]['revenueAmount'] += $order_cost['cost'];
                                //实收
                                $resultArr[$rental_station_id]['rentalAmount'] += $rentalOrder->getAmount();
                                //优惠券
                                if($rentalOrder->getCoupon()){
                                    $resultArr[$rental_station_id]['couponAmount'] += $rentalOrder->getCoupon()->getKind()->getAmount();
                                }

                            }
                        }
                    }

                    $rental_station_temp = $dispatchLast->getRentalStation();
                }
                if($dispatchAllCount == 0){
                    if(!$rental_station_temp){
                        continue;
                    }
                    $rental_station_id = $rental_station_temp->getId();
                    $stay_time = strtotime($end_time)+1 - $startTimestamp;
                    if(!isset($resultArr[$rental_station_id])){
                        $resultArr[$rental_station_id] = $this->arrayInit($date,$rental_station_temp,$rentalCar);
                    }
                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                }else {
                    //dump($dispatchAlls);
                    foreach ($dispatchAlls as $key => $dispatch) {
                        if($key+1 == $dispatchAllCount){
                            if($dispatch->getKind() == 1){//如果是运营移车
                                if($rental_station_temp){
                                    $rental_station_id = $rental_station_temp->getId();
                                    $stay_time = $dispatch->getCreateTime()->getTimestamp() - $startTimestamp;
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rental_station_temp, $rentalCar);
                                    }
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                }

                                $startTimestamp = $dispatch->getCreateTime()->getTimestamp();
                                $rental_station_temp = $dispatch->getRentalStation();


                                $rental_station_id = $rental_station_temp->getId();
                                $stay_time = strtotime($end_time)+1 - $startTimestamp;
                                if (!isset($resultArr[$rental_station_id])) {
                                    $resultArr[$rental_station_id] = $this->arrayInit($date, $rental_station_temp, $rentalCar);
                                }
                                $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                            }else{//如果是订单移车
                                $rentalOrderId = $dispatch->getRentalOrder()->getId();
                                $rentalOrder=$man
                                    ->getRepository('AutoManagerBundle:RentalOrder')
                                    ->findOneBy(['id'=>$rentalOrderId]);
                                if(!$rentalOrder){
                                    echo ' error data！ ';
                                }else{
                                    $rental_station_id = $rentalOrder->getPickUpStation()->getId();
                                    if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $end_time){
                                        $stay_time = strtotime($end_time) + 1 - $startTimestamp;
                                    }else{
                                        $stay_time = $rentalOrder->getEndTime()->getTimestamp() - $startTimestamp;
                                    }
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rentalOrder->getPickUpStation(), $rentalCar);
                                    }
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                    if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $end_time) {
                                        //重新定义订单
                                        $rentalOrderTemp = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
                                        $rentalOrderTemp->setRentalCar($rentalOrder->getRentalCar());
                                        $rentalOrderTemp->setPickUpStation($rentalOrder->getPickUpStation());
                                        $rentalOrderTemp->setReturnStation($rentalOrder->getReturnStation());
                                        $rentalOrderTemp->setUseTime($rentalOrder->getUseTime());
                                        $rentalOrderTemp->setCreateTime($rentalOrder->getCreateTime());
                                        $rentalOrderTemp->setEndTime(new \DateTime($end_time));
                                        //订单计算
                                        $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrderTemp);
                                    }else{
                                        //订单计算
                                        $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrder);
                                    }
                                    if(!is_array($order_cost)){
                                        echo ' order_cost is empty ';
                                        continue;
                                    }
                                    if($order_cost['rentalPrice'][0]['name'] == '日间'){
                                        $day_cost = $order_cost['rentalPrice'][0];
                                        $night_cost = $order_cost['rentalPrice'][1];
                                    }else{
                                        $day_cost = $order_cost['rentalPrice'][1];
                                        $night_cost = $order_cost['rentalPrice'][0];
                                    }
                                    $resultArr[$rental_station_id]['dayRentalTime'] += $day_cost['time'];
                                    $resultArr[$rental_station_id]['nightRentalTime'] += $night_cost['time'];
                                    $resultArr[$rental_station_id]['orderCount'] += 1;
                                    if($rentalOrder->getCreateTime()->format('H:i:s') >= $day_cost['startTime'] && $rentalOrder->getCreateTime()->format('H:i:s') < $day_cost['endTime']){
                                        $resultArr[$rental_station_id]['dayOrderCount'] += 1;
                                    }else{
                                        $resultArr[$rental_station_id]['nightOrderCount'] += 1;
                                    }
                                    //营收
                                    $resultArr[$rental_station_id]['revenueAmount'] += $order_cost['cost'];

                                    if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') < $end_time){
                                        $rental_station_id = $rentalOrder->getReturnStation()->getId();
                                        $stay_time = strtotime($end_time) + 1 - $rentalOrder->getEndTime()->getTimestamp();
                                        if (!isset($resultArr[$rental_station_id])) {
                                            $resultArr[$rental_station_id] = $this->arrayInit($date, $rentalOrder->getReturnStation(), $rentalCar);
                                        }
                                        $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                    }

                                }

                            }

                        }else{
                            if($dispatch->getKind() == 2){//如果是订单移车
                                $rentalOrderId = $dispatch->getRentalOrder()->getId();
                                $rentalOrder=$man
                                    ->getRepository('AutoManagerBundle:RentalOrder')
                                    ->findOneBy(['id'=>$rentalOrderId]);
                                if(!$rentalOrder){
                                    echo ' error data！ ';
                                }else{
                                    $rental_station_id = $rentalOrder->getPickUpStation()->getId();
                                    $stay_time = $rentalOrder->getEndTime()->getTimestamp() - $startTimestamp;
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rentalOrder->getPickUpStation(), $rentalCar);
                                    }
                                    $startTimestamp = $rentalOrder->getEndTime()->getTimestamp();
                                    $rental_station_temp = $rentalOrder->getReturnStation();
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                    //订单计算
                                    $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrder);
                                    if(!is_array($order_cost)){
                                        echo ' order_cost is empty ';
                                        continue;
                                    }
                                    if($order_cost['rentalPrice'][0]['name'] == '日间'){
                                        $day_cost = $order_cost['rentalPrice'][0];
                                        $night_cost = $order_cost['rentalPrice'][1];
                                    }else{
                                        $day_cost = $order_cost['rentalPrice'][1];
                                        $night_cost = $order_cost['rentalPrice'][0];
                                    }
                                    $resultArr[$rental_station_id]['dayRentalTime'] += $day_cost['time'];
                                    $resultArr[$rental_station_id]['nightRentalTime'] += $night_cost['time'];
                                    $resultArr[$rental_station_id]['orderCount'] += 1;
                                    if($rentalOrder->getCreateTime()->format('H:i:s') >= $day_cost['startTime'] && $rentalOrder->getCreateTime()->format('H:i:s') < $day_cost['endTime']){
                                        $resultArr[$rental_station_id]['dayOrderCount'] += 1;
                                    }else{
                                        $resultArr[$rental_station_id]['nightOrderCount'] += 1;
                                    }
                                    //营收
                                    $resultArr[$rental_station_id]['revenueAmount'] += $order_cost['cost'];
                                    //实收
                                    $resultArr[$rental_station_id]['rentalAmount'] += $rentalOrder->getAmount();
                                    //优惠券
                                    if($rentalOrder->getCoupon()){
                                        $resultArr[$rental_station_id]['couponAmount'] += $rentalOrder->getCoupon()->getKind()->getAmount();
                                    }

                                }

                            }else{//移库订单
                                if($rental_station_temp){
                                    $rental_station_id = $rental_station_temp->getId();
                                    $stay_time = $dispatch->getCreateTime()->getTimestamp() - $startTimestamp;
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rental_station_temp, $rentalCar);
                                    }
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                }
                                $startTimestamp = $dispatch->getCreateTime()->getTimestamp();
                                $rental_station_temp = $dispatch->getRentalStation();
                            }

                        }

                    }
                }
                unset($dispatchAlls);
                //dump($resultArr);
                foreach ($resultArr as $arr) {
                    $arr['stayTime'] = number_format($arr['stayTime']/3600,2);
                    $arr['dayRentalTime'] = number_format($arr['dayRentalTime']/3600,2);
                    $arr['nightRentalTime'] = number_format($arr['nightRentalTime']/3600,2);
                    $arr['rentalTime'] = $arr['dayRentalTime'] + $arr['nightRentalTime'];
                    //dump($arr);//die;
                    $this->insertStatisticsOperateRecord($arr);
                }


            }
        }
        return true;
    }
    protected function statisticsOperateHistoryTwoDay(){
        $date = date('Y-m-d',strtotime("-2 day"));
        $man = $this->getContainer()->get('doctrine')->getManager();
        //查找记录是否已经存在
        $statisticsRecords=$man
            ->getRepository('AutoManagerBundle:StatisticsOperateRecord')
            ->findBy(['dateTime'=>new \DateTime($date)]);
        if(!empty($statisticsRecords)){//有则删除
            foreach ($statisticsRecords as $statisticsRecord) {
                $man->remove($statisticsRecord);
                $man->flush();
            }
        }
//        dump('ok');die;
        $start_time = $date.' 00:00:00';
        $end_time = $date.' 23:59:59';
        $qbrc =
            $man
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('o');
        $rentalCarCounts =
            $qbrc->select($qbrc->expr()->count('o'))
                ->getQuery()
                ->getSingleScalarResult();
        $pagesize = 100;
        $pages = intval(ceil($rentalCarCounts/$pagesize));
        for($i=0;$i<$pages;$i++){
            $rentalCarAll =
                $qbrc->select('o')
                    ->orderBy('o.id', 'ASC')
                    ->setFirstResult( $i*$pagesize )
                    ->setMaxResults( $pagesize )
                    ->getQuery()
                    ->getResult();
            foreach ($rentalCarAll as $rentalCar) {
                //获取当前车辆，start_time与end_time之间、状态为1的所有移库记录
                $qbdrc =
                    $man
                        ->getRepository('AutoManagerBundle:DispatchRentalCar')
                        ->createQueryBuilder('o');
                $dispatchAlls =
                    $qbdrc
                        ->select('o')
                        //->leftJoin('o.rentalOrder','ro')
                        ->andWhere($qbdrc->expr()->gte('o.createTime', ':startTime'))
                        ->andWhere($qbdrc->expr()->lte('o.createTime', ':endTime'))
                        ->andWhere($qbdrc->expr()->eq('o.status', ':status'))
                        ->andWhere($qbdrc->expr()->eq('o.rentalCar', ':rentalCar'))
                        ->setParameter('status', 1)
                        ->setParameter('startTime', $start_time)
                        ->setParameter('endTime', $end_time)
                        ->setParameter('rentalCar', $rentalCar)
                        ->orderBy('o.createTime', 'ASC')
                        ->getQuery()
                        ->getResult();

                $qbdrc =
                    $man
                        ->getRepository('AutoManagerBundle:DispatchRentalCar')
                        ->createQueryBuilder('o');
                $query =
                    $qbdrc
                        ->select('o')
                        ->andWhere($qbdrc->expr()->lt('o.createTime', ':startTime'))
                        ->andWhere($qbdrc->expr()->eq('o.status', ':status'))
                        ->andWhere($qbdrc->expr()->eq('o.rentalCar', ':rentalCar'))
                        ->setParameter('startTime', $start_time)
                        ->setParameter('status', 1)
                        ->setParameter('rentalCar', $rentalCar)
                        ->orderBy('o.createTime', 'DESC')
                        ->getQuery()
                        ->setMaxResults(1);
                try {
                    $dispatchLast =  $query->getSingleResult();
                } catch (\Doctrine\ORM\NoResultException $e) {
                    $dispatchLast = null;
                }
                $resultArr = array();
                $rental_station_temp = null;
                $startTimestamp = strtotime($start_time);
                $dispatchAllCount = count($dispatchAlls);

                if($dispatchLast){
                    if($dispatchLast->getKind() == 2){//如果是订单移车
                        $rentalOrderId = $dispatchLast->getRentalOrder()->getId();
                        $rentalOrder=$man
                            ->getRepository('AutoManagerBundle:RentalOrder')
                            ->findOneBy(['id'=>$rentalOrderId]);
                        if(!$rentalOrder){
                            echo ' error data！ ';
                        }else{
                            if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $start_time){
                                $rental_station_id = $rentalOrder->getPickUpStation()->getId();
                                $tempTime = $rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $end_time ? (strtotime($end_time)+1) : $rentalOrder->getEndTime()->getTimestamp();
                                $stay_time = $tempTime - $startTimestamp;
                                if(!isset($resultArr[$rental_station_id])){
                                    $resultArr[$rental_station_id] = $this->arrayInit($date,$rentalOrder->getPickUpStation(),$rentalCar);
                                }
                                $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                $startTimestamp = $tempTime;
                                //重新定义订单
                                $rentalOrderTemp = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
                                $rentalOrderTemp->setRentalCar($rentalOrder->getRentalCar());
                                $rentalOrderTemp->setPickUpStation($rentalOrder->getPickUpStation());
                                $rentalOrderTemp->setReturnStation($rentalOrder->getReturnStation());
                                $rentalOrderTemp->setUseTime($rentalOrder->getUseTime());
                                $rentalOrderTemp->setCreateTime(new \DateTime($start_time));
                                $rentalOrderTemp->setEndTime($rentalOrder->getEndTime());
                                //订单计算
                                $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrderTemp);
                                if(!is_array($order_cost)){
                                    echo ' order_cost is empty ';
                                    continue;
                                }
                                if($order_cost['rentalPrice'][0]['name'] == '日间'){
                                    $day_cost = $order_cost['rentalPrice'][0];
                                    $night_cost = $order_cost['rentalPrice'][1];
                                }else{
                                    $day_cost = $order_cost['rentalPrice'][1];
                                    $night_cost = $order_cost['rentalPrice'][0];
                                }
                                //dump($order_cost);//die;

                                $resultArr[$rental_station_id]['dayRentalTime'] += $day_cost['time'];
                                $resultArr[$rental_station_id]['nightRentalTime'] += $night_cost['time'];
                                //营收
                                $resultArr[$rental_station_id]['revenueAmount'] += $order_cost['cost'];
                                //实收
                                $resultArr[$rental_station_id]['rentalAmount'] += $rentalOrder->getAmount();
                                //优惠券
                                if($rentalOrder->getCoupon()){
                                    $resultArr[$rental_station_id]['couponAmount'] += $rentalOrder->getCoupon()->getKind()->getAmount();
                                }

                            }
                        }
                    }

                    $rental_station_temp = $dispatchLast->getRentalStation();
                }
                if($dispatchAllCount == 0){
                    if(!$rental_station_temp){
                        continue;
                    }
                    $rental_station_id = $rental_station_temp->getId();
                    $stay_time = strtotime($end_time)+1 - $startTimestamp;
                    if(!isset($resultArr[$rental_station_id])){
                        $resultArr[$rental_station_id] = $this->arrayInit($date,$rental_station_temp,$rentalCar);
                    }
                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                }else {
                    //dump($dispatchAlls);
                    foreach ($dispatchAlls as $key => $dispatch) {
                        if($key+1 == $dispatchAllCount){
                            if($dispatch->getKind() == 1){//如果是运营移车
                                if($rental_station_temp){
                                    $rental_station_id = $rental_station_temp->getId();
                                    $stay_time = $dispatch->getCreateTime()->getTimestamp() - $startTimestamp;
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rental_station_temp, $rentalCar);
                                    }
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                }

                                $startTimestamp = $dispatch->getCreateTime()->getTimestamp();
                                $rental_station_temp = $dispatch->getRentalStation();


                                $rental_station_id = $rental_station_temp->getId();
                                $stay_time = strtotime($end_time)+1 - $startTimestamp;
                                if (!isset($resultArr[$rental_station_id])) {
                                    $resultArr[$rental_station_id] = $this->arrayInit($date, $rental_station_temp, $rentalCar);
                                }
                                $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                            }else{//如果是订单移车
                                $rentalOrderId = $dispatch->getRentalOrder()->getId();
                                $rentalOrder=$man
                                    ->getRepository('AutoManagerBundle:RentalOrder')
                                    ->findOneBy(['id'=>$rentalOrderId]);
                                if(!$rentalOrder){
                                    echo ' error data！ ';
                                }else{
                                    $rental_station_id = $rentalOrder->getPickUpStation()->getId();
                                    if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $end_time){
                                        $stay_time = strtotime($end_time) + 1 - $startTimestamp;
                                    }else{
                                        $stay_time = $rentalOrder->getEndTime()->getTimestamp() - $startTimestamp;
                                    }
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rentalOrder->getPickUpStation(), $rentalCar);
                                    }
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                    if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') > $end_time) {
                                        //重新定义订单
                                        $rentalOrderTemp = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
                                        $rentalOrderTemp->setRentalCar($rentalOrder->getRentalCar());
                                        $rentalOrderTemp->setPickUpStation($rentalOrder->getPickUpStation());
                                        $rentalOrderTemp->setReturnStation($rentalOrder->getReturnStation());
                                        $rentalOrderTemp->setUseTime($rentalOrder->getUseTime());
                                        $rentalOrderTemp->setCreateTime($rentalOrder->getCreateTime());
                                        $rentalOrderTemp->setEndTime(new \DateTime($end_time));
                                        //订单计算
                                        $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrderTemp);
                                    }else{
                                        //订单计算
                                        $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrder);
                                    }
                                    if(!is_array($order_cost)){
                                        echo ' order_cost is empty ';
                                        continue;
                                    }
                                    if($order_cost['rentalPrice'][0]['name'] == '日间'){
                                        $day_cost = $order_cost['rentalPrice'][0];
                                        $night_cost = $order_cost['rentalPrice'][1];
                                    }else{
                                        $day_cost = $order_cost['rentalPrice'][1];
                                        $night_cost = $order_cost['rentalPrice'][0];
                                    }
                                    $resultArr[$rental_station_id]['dayRentalTime'] += $day_cost['time'];
                                    $resultArr[$rental_station_id]['nightRentalTime'] += $night_cost['time'];
                                    $resultArr[$rental_station_id]['orderCount'] += 1;
                                    if($rentalOrder->getCreateTime()->format('H:i:s') >= $day_cost['startTime'] && $rentalOrder->getCreateTime()->format('H:i:s') < $day_cost['endTime']){
                                        $resultArr[$rental_station_id]['dayOrderCount'] += 1;
                                    }else{
                                        $resultArr[$rental_station_id]['nightOrderCount'] += 1;
                                    }
                                    //营收
                                    $resultArr[$rental_station_id]['revenueAmount'] += $order_cost['cost'];

                                    if($rentalOrder->getEndTime()->format('Y-m-d H:i:s') < $end_time){
                                        $rental_station_id = $rentalOrder->getReturnStation()->getId();
                                        $stay_time = strtotime($end_time) + 1 - $rentalOrder->getEndTime()->getTimestamp();
                                        if (!isset($resultArr[$rental_station_id])) {
                                            $resultArr[$rental_station_id] = $this->arrayInit($date, $rentalOrder->getReturnStation(), $rentalCar);
                                        }
                                        $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                    }

                                }

                            }

                        }else{
                            if($dispatch->getKind() == 2){//如果是订单移车
                                $rentalOrderId = $dispatch->getRentalOrder()->getId();
                                $rentalOrder=$man
                                    ->getRepository('AutoManagerBundle:RentalOrder')
                                    ->findOneBy(['id'=>$rentalOrderId]);
                                if(!$rentalOrder){
                                    echo ' error data！ ';
                                }else{
                                    $rental_station_id = $rentalOrder->getPickUpStation()->getId();
                                    $stay_time = $rentalOrder->getEndTime()->getTimestamp() - $startTimestamp;
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rentalOrder->getPickUpStation(), $rentalCar);
                                    }
                                    $startTimestamp = $rentalOrder->getEndTime()->getTimestamp();
                                    $rental_station_temp = $rentalOrder->getReturnStation();
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                    //订单计算
                                    $order_cost = $this->getContainer()->get('auto_manager.order_helper')->get_rental_order_cost($rentalOrder);
                                    if(!is_array($order_cost)){
                                        echo ' order_cost is empty ';
                                        continue;
                                    }
                                    if($order_cost['rentalPrice'][0]['name'] == '日间'){
                                        $day_cost = $order_cost['rentalPrice'][0];
                                        $night_cost = $order_cost['rentalPrice'][1];
                                    }else{
                                        $day_cost = $order_cost['rentalPrice'][1];
                                        $night_cost = $order_cost['rentalPrice'][0];
                                    }
                                    $resultArr[$rental_station_id]['dayRentalTime'] += $day_cost['time'];
                                    $resultArr[$rental_station_id]['nightRentalTime'] += $night_cost['time'];
                                    $resultArr[$rental_station_id]['orderCount'] += 1;
                                    if($rentalOrder->getCreateTime()->format('H:i:s') >= $day_cost['startTime'] && $rentalOrder->getCreateTime()->format('H:i:s') < $day_cost['endTime']){
                                        $resultArr[$rental_station_id]['dayOrderCount'] += 1;
                                    }else{
                                        $resultArr[$rental_station_id]['nightOrderCount'] += 1;
                                    }
                                    //营收
                                    $resultArr[$rental_station_id]['revenueAmount'] += $order_cost['cost'];
                                    //实收
                                    $resultArr[$rental_station_id]['rentalAmount'] += $rentalOrder->getAmount();
                                    //优惠券
                                    if($rentalOrder->getCoupon()){
                                        $resultArr[$rental_station_id]['couponAmount'] += $rentalOrder->getCoupon()->getKind()->getAmount();
                                    }

                                }

                            }else{//移库订单
                                if($rental_station_temp){
                                    $rental_station_id = $rental_station_temp->getId();
                                    $stay_time = $dispatch->getCreateTime()->getTimestamp() - $startTimestamp;
                                    if (!isset($resultArr[$rental_station_id])) {
                                        $resultArr[$rental_station_id] = $this->arrayInit($date, $rental_station_temp, $rentalCar);
                                    }
                                    $resultArr[$rental_station_id]['stayTime'] += $stay_time;
                                }
                                $startTimestamp = $dispatch->getCreateTime()->getTimestamp();
                                $rental_station_temp = $dispatch->getRentalStation();
                            }

                        }

                    }
                }
                unset($dispatchAlls);
                //dump($resultArr);
                foreach ($resultArr as $arr) {
                    $arr['stayTime'] = number_format($arr['stayTime']/3600,2);
                    $arr['dayRentalTime'] = number_format($arr['dayRentalTime']/3600,2);
                    $arr['nightRentalTime'] = number_format($arr['nightRentalTime']/3600,2);
                    $arr['rentalTime'] = $arr['dayRentalTime'] + $arr['nightRentalTime'];
                    //dump($arr);//die;
                    $this->insertStatisticsOperateRecord($arr);
                }


            }
        }
        return true;
    }

    protected function arrayInit($date,$rentalStation,$rentalCar){
        $resultArr = array();
        $resultArr['date'] = new \DateTime($date);
        $resultArr['rental_station_id'] = $rentalStation;
        $resultArr['rental_car_id'] = $rentalCar;
        $resultArr['stayTime'] = 0;
        $resultArr['rentalTime'] = 0;
        $resultArr['dayRentalTime'] = 0;
        $resultArr['nightRentalTime'] = 0;
        $resultArr['orderCount'] = 0;
        $resultArr['dayOrderCount'] = 0;
        $resultArr['nightOrderCount'] = 0;
        $resultArr['revenueAmount'] = 0;
        $resultArr['couponAmount'] = 0;
        $resultArr['rentalAmount'] = 0;
        return $resultArr;
    }

    /**
     * 插入统计记录
     * @param $arr
     */
    protected function insertStatisticsOperateRecord($arr){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $statisticsOperateRecord = new \Auto\Bundle\ManagerBundle\Entity\StatisticsOperateRecord;
        $statisticsOperateRecord->setDateTime($arr['date']);
        $statisticsOperateRecord->setRentalCar($arr['rental_car_id']);
        $statisticsOperateRecord->setRentalStation($arr['rental_station_id']);
        if(isset($arr['stayTime'])){
            $statisticsOperateRecord->setStayTime($arr['stayTime']);
        }
        if(isset($arr['rentalTime'])){
            $statisticsOperateRecord->setRentalTime($arr['rentalTime']);
        }
        if(isset($arr['dayRentalTime'])){
            $statisticsOperateRecord->setDayRentalTime($arr['dayRentalTime']);
        }
        if(isset($arr['nightRentalTime'])){
            $statisticsOperateRecord->setNightRentalTime($arr['nightRentalTime']);
        }
        if(isset($arr['orderCount'])){
            $statisticsOperateRecord->setOrderCount($arr['orderCount']);
        }
        if(isset($arr['dayOrderCount'])){
            $statisticsOperateRecord->setDayOrderCount($arr['dayOrderCount']);
        }
        if(isset($arr['nightOrderCount'])){
            $statisticsOperateRecord->setNightOrderCount($arr['nightOrderCount']);
        }
        if(isset($arr['revenueAmount'])){
            $statisticsOperateRecord->setRevenueAmount($arr['revenueAmount']);
        }
        if(isset($arr['couponAmount'])){
            $statisticsOperateRecord->setCouponAmount($arr['couponAmount']);
        }
        if(isset($arr['rentalAmount'])){
            $statisticsOperateRecord->setRentalAmount($arr['rentalAmount']);
        }
        $man->persist($statisticsOperateRecord);
        $man->flush();
        return true;

    }


}