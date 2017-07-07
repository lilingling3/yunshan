<?php

namespace Auto\Bundle\ManagerBundle\Helper;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;

class RentalCarHelper extends AbstractHelper{


    public function get_rental_car_normalizer(){
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalCar $r,$time=5) {
            $range = $this->get_rental_car_range($r,$time);

            $rental_car = [
                'rentalCarID'      => $r->getId(),
                'license' => $r->getLicense(),
                'car'     => call_user_func($this->carHelper->get_car_normalizer(),
                    $r->getCar()),
                'status'  => $this->get_rental_car_status($r),
                'mileage' =>$range>0?$range:0,
                'online'   => empty($r->getOnline())?0:$r->getOnline()->getStatus(),
                'back'    => \Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK,
                'image'  =>$this->templating->render(
                    '{{ localname|photograph }}',
                    ['localname' => $r->getCar()->getImage()]
                ),
                'licenseImage'=>isset($r->getImages()[0])?$this->templating->render(
                '{{ localname|photograph }}',
                ['localname' => array_values($r->getImages())[0]]
            ):null,
                'rentalStation' => call_user_func($this->stationHelper->get_station_normalizer(),
                    $r->getRentalStation()),
                'rentalPrice'  => array_map($this->rentalPriceHelper
                    ->get_rental_price_normalizer(),
                    $this->rentalPriceHelper->get_rental_car_price($r)),
                'chassisNumber'             =>$r->getChassisNumber(),
                'engineNumber'              =>$r->getEngineNumber(),
                'rentalCarDiscount' =>$this->get_rental_car_discount($r),
                'position'=>$this->get_rental_car_position($r),
                'rentalPriceText'=>'30分钟起租',
                'discountText'=>'(已优惠50%)'
            ];

            return $rental_car;
        };
    }

    /**
     * @param string $image : 比如 '1654131.png'
     * @return string
     */
    public function get_rental_car_image($image)
    {
        return $this->templating->render(
            '{{ localname|photograph }}',
            ['localname' => $image]
        );
    }


    //2.4.0
    public function get_rental_car_data_normalizer(){
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalCar $r,$time=5) {
            $range = $this->get_rental_car_range($r,$time);

            $rental_car = [
                'rentalCarID'      => $r->getId(),
                'license' => $r->getLicense(),
                'car'     => call_user_func($this->carHelper->get_car_normalizer(),
                    $r->getCar()),
                'status'  => $this->get_rental_car_status($r),
                'mileage' =>$range>0?$range:0,
                'online'   => empty($r->getOnline())?0:$r->getOnline()->getStatus(),
                'back'    => \Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK,
                'image'  =>$this->curlHelper->base_url().$this->templating->render(
                        '{{ localname|photograph }}',
                        ['localname' => $r->getCar()->getImage()]
                    ),
                'licenseImage'=>isset($r->getImages()[0])?$this->curlHelper->base_url().$this->templating->render(
                        '{{ localname|photograph }}',
                        ['localname' => array_values($r->getImages())[0]]
                    ):null,
                'rentalStation' => call_user_func($this->stationHelper->get_station_data_normalizer(),
                    $r->getRentalStation()),
                'rentalPrice'  => array_map($this->rentalPriceHelper
                    ->get_rental_price_normalizer(),
                    $this->rentalPriceHelper->get_rental_car_price($r)),
                'chassisNumber'             =>$r->getChassisNumber(),
                'engineNumber'              =>$r->getEngineNumber(),
                'rentalCarDiscount' =>$this->get_rental_car_discount($r),
                'position'=>$this->get_rental_car_position($r),
                'rentalPriceText'=>'1小时起租,每15分钟计费一次',
                'discountText'=>'(已优惠50%)'
            ];

            return $rental_car;
        };
    }

    /**
     * 获取租车信息
     *
     * @param \Auto\Bundle\ManagerBundle\Entity\RentalCar  $r
     * @param int $time
     * @return \Array
     */
    public function get_rental_car_data_normalizer2(){
        return function (\Auto\Bundle\ManagerBundle\Entity\RentalCar $r,$time=5) {
            $range = $this->get_rental_car_range($r,$time);

            $rental_car = [
                'rentalCarID'   => $r->getId(),
                'license'       => $r->getLicense(),
                'car'           => call_user_func($this->carHelper->get_car_data_normalizer(),
                    $r->getCar()),
                'status'        => $this->get_rental_car_status($r),
                'mileage'       => $range > 0 ? $range : 0,
                'online'        => empty($r->getOnline())?0:$r->getOnline()->getStatus(),
                'back'          => \Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK,
                'image'         => $this->curlHelper->base_url().$this->templating->render(
                        '{{ localname|photograph }}',
                        ['localname' => $r->getCar()->getImage()]
                    ),
                'licenseImage'  => isset($r->getImages()[0])?$this->curlHelper->base_url().$this->templating->render(
                        '{{ localname|photograph }}',
                        ['localname' => array_values($r->getImages())[0]]
                    ):null,
//                'rentalStation' => call_user_func($this->stationHelper->get_station_data_normalizer(),
//                    $r->getRentalStation()),
                'rentalPrice'   => array_map($this->rentalPriceHelper->get_rental_price_normalizer(),
                    $this->rentalPriceHelper->get_rental_car_price($r)),
//                'chassisNumber' => $r->getChassisNumber(),
//                'engineNumber'  => $r->getEngineNumber(),
                'rentalCarDiscount' => $this->get_rental_car_discount($r),
                'position'          => $this->get_rental_car_position($r),
                'rentalPriceText'   => '1小时起租,每15分钟计费一次',
                'discountText'      => '(已优惠50%)'
            ];

            return $rental_car;
        };
    }

    //获取日间夜间价格 app首页车辆选择（折扣后价格）
    public function get_rental_car_discount_price(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){

        $carPrice =  array_map($this->rentalPriceHelper
                ->get_rental_price_normalizer(),
            $this->rentalPriceHelper->get_rental_car_price($r))
        ;

        $stationDiscountData = call_user_func( $this->stationHelper->get_station_discount_normalizer(),
            $r->getRentalStation());

        $rentalCarDiscount =$this->carHelper->get_car_discount($r->getCar());

        !empty($carPrice[0]['price'])?$carPrice[0]['price'] = number_format($carPrice[0]['price']*$stationDiscountData['discount']*$rentalCarDiscount, 1, '.', ''):'';

        !empty($carPrice[0]['price'])?$price_body=explode(".",$carPrice[0]['price']):'';

        if(!empty($price_body)) {

            if ($price_body[1] == 0) {

                $carPrice[0]['price'] = $price_body[0];

            }

        }


        !empty($carPrice[1]['price'])?$carPrice[1]['price'] = number_format($carPrice[1]['price']*$stationDiscountData['discount']*$rentalCarDiscount, 1, '.', ''):'';

        !empty($carPrice[1]['price'])?$price_body=explode(".",$carPrice[1]['price']):'';

        if(!empty($price_body)) {

            if ($price_body[1] == 0) {

                $carPrice[1]['price'] = $price_body[0];

            }

        }

        return  $carPrice;

    }

    public function get_rental_car_discount(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){

        $rentalCarDiscount =$this->carHelper->get_car_discount($r->getCar());

        return $rentalCarDiscount;

    }


    public function get_rental_car_status(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){

        $qb = $this->em->createQueryBuilder();
        $order =
            $qb
                ->select('o')
                ->from('AutoManagerBundle:RentalOrder', 'o')
                ->where($qb->expr()->eq('o.rentalCar', ':car'))
                ->andWhere($qb->expr()->isNull('o.endTime'))
                ->andWhere($qb->expr()->isNull('o.cancelTime'))
                ->setParameter('car', $r)
                ->getQuery()
                ->getResult()
            ;

        if(empty($order)){
            return  $status = \Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE;
        }else{
            return \Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_UNABLE;
        }

    }


    public function get_rental_car_mileage(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){

        $qb = $this->em->createQueryBuilder();
        $record =
            $qb
                ->select('m')
                ->from('AutoManagerBundle:MileageRecords', 'm')
                ->where($qb->expr()->eq('m.rentalCar', ':rentalCar'))
                ->setParameter('rentalCar', $r)
                ->setMaxResults(1)
                ->orderBy('m.id','DESC')
                ->getQuery()
                ->getOneOrNullResult()
        ;

        return ($record)?$record->getMileage():0;

    }

    /**
     * 获取车辆当前里程数，没有则返回0
     * @param RentalCar $r
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function get_rental_car_current_mileage(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){
        $renturn_mileage = 0;
        if($r->getBoxId()){
            $redis_cmd= $this->redisHelper->createCommand('LINDEX',array($r->getDeviceCompany()->getEnglishName().'-mileage-'.$r->getBoxId(),0));
            $mileage_json = $this->redisHelper->executeCommand($redis_cmd);
            $mileage_arr = json_decode($mileage_json,true);
            $renturn_mileage = isset($mileage_arr['mileage'])?$mileage_arr['mileage']:0;
        }
        return $renturn_mileage;

    }


    public function get_rental_car_position(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r){

        $rentalCar = $this->em->getRepository('AutoManagerBundle:RentalCar')->find($r);
        if(empty($rentalCar->getBoxId())){
            return null;
        }
        $redis_cmd= $this->redisHelper->createCommand('lindex',array($r->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId(),0));
        $gps_json = $this->redisHelper->executeCommand($redis_cmd);
        $gps_arr = json_decode($gps_json,true);

        return [
            'latitude'                  =>$gps_arr['coordinate'][1],
            'longitude'                 =>$gps_arr['coordinate'][0],
            'time'                      =>date('Y-m-d H:i:s',$gps_arr['time'])
        ];

    }


    public function get_online_options(){

        return [
            1=>"车辆外观已清洁",
            2=>"车辆轮胎完好",
            3=>"车辆内饰已清洁",
            4=>"保单复印件已有",
            5=>"车辆行驶本已有",
            6=>"车辆交强险标志存在",
            7=>"车辆年检标志存在",
            8=>"车辆备胎已有",
            9=>"车辆换胎工具已有",
            10=>"车辆充电线已有",
            11=>"车辆控制设备可用"
        ];

    }


    public function get_offline_options(){

        return
            [12=>"设备故障",
            13=>"车辆充电",
            14=>"车辆故障/事故",
            15=>"调配车辆",
            16=>"用户还车",
            17=>"人工还车",
            18=>"车辆整备",
            19=>"设备故障",
            20=>"号牌丢失",
            21=>"轮胎损坏"
            ];


    }

    /**
     * 获取车辆剩余里程
     *
     * @param RentalCar $r 车辆对象
     * @param int $time
     * @return float|int
     */
    public function get_rental_car_range(\Auto\Bundle\ManagerBundle\Entity\RentalCar $r,$time=5){

        $range = 0;

        if(empty($r->getBoxId())){
            return 0;
        }

        // 获取里程数据
        $redis_cmd  = $this->redisHelper->createCommand('lindex',array($r->getDeviceCompany()->getEnglishName().'-range-'.$r->getBoxId(),0));
        $range_json = $this->redisHelper->executeCommand($redis_cmd);

        if($range_json){

            $range_arr = json_decode($range_json,true);
            $range = $range_arr['range'];
        }

        // 如果取不到数据，根据电量来转换
        if($range == 0){

            $redis_cmd  = $this->redisHelper->createCommand('lindex',array($r->getDeviceCompany()->getEnglishName().'-power-'.$r->getBoxId(),0));
            $power_json = $this->redisHelper->executeCommand($redis_cmd);

            if($power_json){

                $power_arr = json_decode($power_json,true);

                // 该车型的行驶里程 * 当前车辆剩余电量百分比 - 2 (km)
                $range = $power_arr['power'] * $r->getCar()->getDriveMileage() / 100 - 2;
            }
        }

        return $range;

    }


    public function car_start_operate($box,$operate,$password=''){

        $kernel = $this->container->get("kernel");

        $application = new Application($kernel);
        $application->setAutoExit(false);


        if($password&&$operate=='encode'){

            $input = new ArrayInput(array(
                'command' => 'auto:carstart:operate',
                '--id' => $box,
                '--operate'=>$operate,
                '--password'=>$password,
            ));

        }else{

            $input = new ArrayInput(array(
                'command' => 'auto:carstart:operate',
                '--id' => $box,
                '--operate'=>$operate,
            ));
        }


        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();

        return $content;
    }


    //2.4.0
    public function action(RentalCar $rentalCar,$action,$member=null,$password=''){

        if($member)
            $this->monologHelper->info($member->getId().' '.$action.' '.$rentalCar->getLicense());

        if($rentalCar->getDeviceCompany()->getEnglishName() =='carStart'){
            return $this->carStartHelper->action($rentalCar->getBoxId(),$action,$password);
        }

        if($rentalCar->getDeviceCompany()->getEnglishName() =='baoJia'){
            return $this->baoJiaHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='yunshanZhihui'){
            return $this->yunshanZhihuiHelper->operate($rentalCar->getBoxId(),$action,$member->getId());
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='feeZu'){
            return $this->feeZuHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() == self::DEVICE_COMPANY_ZHI_XIN_TONG){
            return $this->zhiXinTongHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='cloudBox'){
            return $this->cloudBoxHelper->operate($rentalCar->getBoxId(),$action,$password);
        }

    }





    public function operate(RentalCar $rentalCar,$action,$member=null,$password=''){

        if($member)
            $this->monologHelper->info($member->getId().' '.$action.' '.$rentalCar->getLicense());

        if($rentalCar->getDeviceCompany()->getEnglishName() =='carStart'){
            return $this->carStartHelper->operate($rentalCar->getBoxId(),$action,$password);
        }

        if($rentalCar->getDeviceCompany()->getEnglishName() =='feeZu'){

            if ($rentalCar->getCar()->getId() == 5 && $action == "open") {
                $this->feeZuHelper->operate($rentalCar->getBoxId(), "find",$password);
            }

            return $this->feeZuHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='baoJia'){
            return $this->baoJiaHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() ==self::DEVICE_COMPANY_ZHI_XIN_TONG){
            return $this->zhiXinTongHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='yunshanZhihui'){
            $memberId = 'NoUser';
            if($member){
                $memberId = $member->getId();
            }
            return $this->yunshanZhihuiHelper->operate($rentalCar->getLicensePlace()->getName().$rentalCar->getLicensePlate(),$rentalCar->getBoxId(),$action,$memberId);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='cloudBox'){
            return $this->cloudBoxHelper->operate($rentalCar->getBoxId(),$action,$password);
        }

    }

    public function partnerOperate(RentalCar $rentalCar,$action,$member,$password=''){

        $this->monologHelper->info($member . ' '.$action.' '.$rentalCar->getLicense());

        if($rentalCar->getDeviceCompany()->getEnglishName() =='carStart'){
            return $this->carStartHelper->operate($rentalCar->getBoxId(),$action,$password);
        }

        if($rentalCar->getDeviceCompany()->getEnglishName() =='feeZu'){
            return $this->feeZuHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='baoJia'){
            return $this->baoJiaHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() ==self::DEVICE_COMPANY_ZHI_XIN_TONG){
            return $this->yunshanZhihuiHelper->operate($rentalCar->getBoxId(),$action,$password);
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='yunshanZhihui'){
            return $this->yunshanZhihuiHelper->operate($rentalCar->getBoxId(),$action,$member->getId());
        }
        if($rentalCar->getDeviceCompany()->getEnglishName() =='cloudBox'){
            return $this->cloudBoxHelper->operate($rentalCar->getBoxId(),$action,$password);
        }

    }


    public function setStationHelper($stationHelper)
    {
        $this->stationHelper = $stationHelper;
    }

    public function setRentalPriceHelper($rentalPriceHelper)
    {
        $this->rentalPriceHelper = $rentalPriceHelper;
    }

    public function setCarHelper($carHelper)
    {
        $this->carHelper = $carHelper;
    }

    public function setOrderHelper($orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    public function setAMapHelper($aMapHelper){

        $this->aMapHelper = $aMapHelper;

    }

    public function setCarStartHelper($carStartHelper){

        $this->carStartHelper = $carStartHelper;

    }
    public function setFeeZuHelper($feeZuHelper){

        $this->feeZuHelper = $feeZuHelper;

    }
    public function setBaoJiaHelper($baoJiaHelper){

        $this->baoJiaHelper = $baoJiaHelper;

    }
    public function setZhiXinTongHelper($zhiXinTongHelper){
        $this->zhiXinTongHelper = $zhiXinTongHelper;
    }
    public function setYunshanZhihuiHelper($yunshanZhihuiHelper){

        $this->yunshanZhihuiHelper = $yunshanZhihuiHelper;

    }

    public function setCloudBoxHelper($cloudBoxHelper){

        $this->cloudBoxHelper = $cloudBoxHelper;

    }

    public function setRedisHelper($redisHelper){

        $this->redisHelper = $redisHelper;

    }
    public function setMonologHelper($monologHelper){

        $this->monologHelper = $monologHelper;

    }
    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }




}