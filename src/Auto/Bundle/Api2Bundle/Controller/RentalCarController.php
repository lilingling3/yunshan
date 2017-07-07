<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/13
 * Time: 上午11:32
 */

namespace Auto\Bundle\Api2Bundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @Route("/rentalCar")
 */
class RentalCarController extends BaseController{
    const PER_PAGE = 20;

    /**
     * @Route("/choose", methods="POST" ,name="auto_api2_rentalCar_choose")
     */
    public function chooseAction(Request $req){

        $id = $req->request->getInt('rentalCarID');
        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        if(empty($rental_car)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>"该车不存在",
            ]);
        }

        return new JsonResponse(
            [
                'errorCode' =>  self::E_OK,
                'rentalCar' =>call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_data_normalizer(), $rental_car),
            ]
        );

    }

    function get_random_integer($length)
    {
        $key='';
        for($i=0;$i<$length;$i++)
        {
            $key .= rand(1,5);    //生成php随机数
        }
        return $key;
    }



    /**
     * @Route("/cloud/box/password", methods="POST")
     */
    public function getPasswordAction(Request $req){

        $id = $req->request->get('boxID');

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('get',array('cloudBox-password-'.$id));
        $password = $redis->executeCommand($redis_cmd);

        return new JsonResponse(
            [
                'errorCode' =>  self::E_OK,
                'password'  => $password
            ]
        );


    }






    /**
     * @Route("/order", methods="POST",name="auto_api2_rental_car_order")
     */
    public function orderAction(Request $req)
    {
        $cid = $req->request->getInt('rentalCarID');
        $source = $req->request->get('source');
        $back_station_id = $req->request->getInt('returnStationID');
        // $rsid = $req->request->getInt('rentalStationID');
        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();


        $authMember = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['member'=>$member]);



        if(empty($authMember)){

            return new JsonResponse([
                'errorCode' =>  self::E_MEMBER_NO_AUTH,
                'errorMessage' =>self::M_MEMBER_NO_AUTH,
            ]);
        }


        $auth_status = $this->get('auto_manager.member_helper')->getStatus($authMember);

        if($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_LICENSE_EXPIRE){

            return new JsonResponse([
                'errorCode' =>  self::E_MEMBER_AUTH_EXPIRE,
                'errorMessage' =>self::M_MEMBER_AUTH_EXPIRE,
            ]);

        }

        if($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED){

            return new JsonResponse([
                'errorCode' =>  self::E_MEMBER_AUTH_FAIL,
                'errorMessage' =>self::M_MEMBER_AUTH_FAIL,
            ]);

        }

        if($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::UPDATED_NO_AUTH){

            return new JsonResponse([
                'errorCode' =>  self::E_MEMBER_WAIT_AUTH,
                'errorMessage' =>self::M_MEMBER_WAIT_AUTH
            ]);

        }
        if($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::NO_UPDATE_AUTH){

            return new JsonResponse([
                'errorCode' =>  self::E_MEMBER_NO_AUTH,
                'errorMessage' =>self::M_MEMBER_NO_AUTH
            ]);

        }

        //是否存在缴费

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:PaymentOrder')
                ->createQueryBuilder('p')
        ;
        $payment_order = $qb
            ->select('p')
            ->where($qb->expr()->eq('p.member', ':member'))
            ->andWhere($qb->expr()->isNull('p.payTime'))
            ->setParameter('member', $this->getUser())
            ->getQuery()
            ->getResult();
        ;

        if(!empty($payment_order)){


            return new JsonResponse(
                [
                    'errorCode' =>  self::E_HAVE_PAYMENT_ORDER,
                    'errorMessage' =>self::M_HAVE_PAYMENT_ORDER
                ]
            );


        }


        $authMembers = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findBy(['IDNumber'=>$authMember->getIDNumber()]);

        $member_ids = [];

        foreach($authMembers as $auth){

            $member_ids[] = $auth->getMember()->getId();

        }




        if(!empty($member_ids)){

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('b');

            $no_pay_order =
                $qb
                    ->select('b')
                    ->join('b.member','m')
                    ->where($qb->expr()->in('m.id', ':ids'))
                    ->andWhere($qb->expr()->isNull('b.payTime'))
                    ->andWhere($qb->expr()->isNull('b.cancelTime'))
                    ->setParameter('ids', $member_ids)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult()
            ;

            if(!empty($no_pay_order)){

                $order_status = $this->get("auto_manager.order_helper")->get_order_status($no_pay_order);

                if($no_pay_order->getMember() == $member){
                    return new JsonResponse([
                        'errorCode' =>  self::E_HAS_ORDER,
                        'errorMessage' =>'您有未完成行程',
                        'orderID'=>$no_pay_order->getId(),
                        'status' => $order_status

                    ]);
                }else{
                    return new JsonResponse([
                        'errorCode' =>  self::E_OTHER_ACCOUNT_HAS_NO_PAY_ORDER,
                        'errorMessage' =>'您使用'.$no_pay_order->getMember()->getMobile().'手机号租车后有未完成行程，请完成后再租赁车辆。',
                        'orderID'=>$no_pay_order->getId(),
                        'status' => $order_status

                    ]);
                }


            }

        }




        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('i');

        $illegal_record =
            $qb
                ->select('i')
                ->join('i.order','o')
                ->join('o.member','m')
                ->where($qb->expr()->in('m.id', ':ids'))
                ->andWhere($qb->expr()->isNull('i.handleTime'))
                ->andWhere($qb->expr()->lte('i.createTime',':time'))
                ->setParameter('time', (new \DateTime())->modify("-3 days"))
                ->setParameter('ids', $member_ids)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;



        if(!empty($illegal_record)){

            if($illegal_record->getOrder()->getMember()->getId()==$member->getId()){
                return new JsonResponse([
                    'errorCode' =>  self::E_HAS_ILLEGAL_RECORD,
                    'errorMessage' =>self::M_HAS_ILLEGAL_RECORD,
                ]);

            }else{
                return new JsonResponse([
                    'errorCode' =>  self::E_HAS_ILLEGAL_RECORD,
                    'errorMessage' =>'您使用'.$illegal_record->getOrder()->getMember()->getMobile().'手机号租车时产生违章，请处理完违章后再租赁车辆。',
                ]);
            }

        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('b');

        $blacklist =
            $qb
                ->select('b')
                ->join('b.authMember','a')
                ->where($qb->expr()->eq('a.IDNumber', ':id'))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gt('b.endTime', ':now'),
                    $qb->expr()->isNull('b.endTime')
                ))
                ->setParameter('id', $authMember->getIDNumber())
                ->setParameter('now', (new \DateTime()))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if(!empty($blacklist)){

            $message = '';
            if($blacklist->getReason()==1)
                $message = '由于您的个人征信不良，您的驾驶证关联的所有手机将不能使用车辆租赁服务。有疑问请致电400-111-8220';

            if($blacklist->getReason()==2)
                $message = '由于您使用'.$blacklist->getAuthMember()->getMember()->getMobile().'手机在使用我们的服务时严重违反用户协议，您的驾驶证关联的所有手机将不能使用车辆租赁服务。有疑问请致电400-111-8220';

            if($blacklist->getReason()==3)
                $message = '由于您使用'.$blacklist->getAuthMember()->getMember()->getMobile().'手机在租赁过程中产生严重过失，您的驾驶证关联的所有手机将不能使用车辆租赁服务。有疑问请致电400-111-8220';


            return new JsonResponse([
                'errorCode' =>  self::E_MEMBER_IN_BLACKLIST,
                'errorMessage' =>$message,
            ]);
        }


        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($cid);

        if(empty($rental_car)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ]);
        }

        if(empty($rental_car->getOnline())||$rental_car->getOnline()->getStatus()==0){
            return new JsonResponse([
                'errorCode' =>  self::E_RENTAL_CAR_OFFLINE,
                'errorMessage' =>self::M_RENTAL_CAR_OFFLINE,
            ]);
        }

        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rental_car);

        if(!empty($car_order)){
            return new JsonResponse([
                'errorCode' =>  self::E_HAS_RENTAL_ORDER,
                'errorMessage' =>self::M_HAS_RENTAL_ORDER,
            ]);
        }


        $illegal = $this->get("auto_manager.illegal_record_helper")->get_member_illegal_order($member);

        if(!empty($illegal)){
            return new JsonResponse([
                'errorCode' =>  self::E_HAS_ILLEGAL_RECORD,
                'errorMessage' =>self::M_HAS_ILLEGAL_RECORD,
            ]);
        }



        if($back_station_id&&$back_station_id!=$rental_car->getRentalStation()->getId()){
            $backRentalStation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:rentalStation')
                ->find($back_station_id);

            $count = $this->get("auto_manager.station_helper")->get_parking_space_count($backRentalStation);

            if($count==0){
                return new JsonResponse([
                    'errorCode' =>  self::E_STATION_NO_PARKING_SPACE,
                    'errorMessage' =>self::M_STATION_NO_PARKING_SPACE,
                ]);

            }


        }else{
            $backRentalStation = $rental_car->getRentalStation();
        }


        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Station')
                ->createQueryBuilder('s');

        $rsid = $rental_car->getRentalStation();

        $rental_station  =  
            $qb
                ->select('s')
                ->where($qb->expr()->eq('s.id', ':stationID'))
                ->setParameter('stationID', $rsid)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (!empty($rental_station)) {

            $detail = call_user_func($this->get("auto_manager.station_helper")->get_station_normalizer(), $rental_station);

            // 2017-03-09 禁用押金
            // if ($detail['city'] == "三亚市") {
            if (false) {
                $qb = 
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:DepositArea')
                        ->createQueryBuilder('s');
                            
                $deposit =
                        $qb
                            ->select('s')
                            ->where($qb->expr()->eq('s.area', ':areaId'))
                            ->setParameter('areaId', $detail['areaID'])
                            ->setMaxResults(1)
                            ->getQuery()
                            ->getOneOrNullResult()
                        ;

                if (!empty($deposit)) {
                    $deposit_info = call_user_func($this->get("auto_manager.deposit_helper")->get_deposit_area_normalizer(),$deposit);

                    if ($deposit_info['isneed']) {
                        //
                        $qb = 
                            $this
                                ->getDoctrine()
                                ->getRepository('AutoManagerBundle:Deposit')
                                ->createQueryBuilder('s');
                                    
                        $depositinfo =
                                $qb
                                    ->select('s')
                                    ->where($qb->expr()->eq('s.member', ':member'))
                                    ->andWhere($qb->expr()->gte('s.total', ':amount'))
                                    ->setParameter('member', $member)
                                    ->setParameter('amount', $deposit_info['amount'])
                                    ->setMaxResults(1)
                                    ->getQuery()
                                    ->getOneOrNullResult()
                        ;

                        if (empty($depositinfo)) {
                            return new JsonResponse([

                                'errorCode' => self::E_NEED_PAY_DEPOSIT,
                                'errorMessage' =>'您需要支付押金',
                                
                            ]);
                        }
                    }
                }
            }

            // 深圳余额
            if ($detail['city'] == "深圳市") {

                if ($member->getWallet() == null || $member->getWallet() < 500 ){
                    
                    return new JsonResponse([
                        'errorCode' => self::E_NEED_PAY_DEPOSIT,
                        'errorMessage' =>'您的余额不足500请先充值',
                    ]);
                }
            }
        }




        

        $order = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
        $order->setRentalCar($rental_car);
        $order->setMember($member);
        $order->setPickUpStation($rental_car->getRentalStation());
        $order->setReturnStation($backRentalStation);
        if($order->getRentalCar()->getRentalStation()->getBackType()
            ==\Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK){
            $order->setReturnStation($rental_car->getRentalStation());
        }

        if($source){
            $order->setSource($source);
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('GET',array('api-rental-car-'.$rental_car->getId()."-order"));

        $mark=1;
        while($redis->executeCommand($redis_cmd)==0&&$mark<=6){

            sleep(1);
            $mark++;

        }

        if($mark==6){
            return new JsonResponse([
                'errorCode' =>  self::E_HAS_RENTAL_ORDER,
                'errorMessage' =>self::M_HAS_RENTAL_ORDER,
            ]);
        }


        $redis_cmd= $redis->createCommand('SET',array('api-rental-car-'.$rental_car->getId()."-order","0"));
        $redis->executeCommand($redis_cmd);


        //检查车是否已有订单
        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rental_car);

        if(!empty($car_order)){
            return new JsonResponse([
                'errorCode' =>  self::E_HAS_RENTAL_ORDER,
                'errorMessage' =>self::M_HAS_RENTAL_ORDER,
            ]);
        }

        $man = $this->getDoctrine()->getManager();

        $man->persist($order);
        $man->flush();


        $redis_cmd= $redis->createCommand('SET',array('api-rental-car-'.$rental_car->getId()."-order","1"));
        $redis->executeCommand($redis_cmd);

        $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
        $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::USER_RETURN_CAR_KIND);
        $dispatch->setRentalCar($order->getRentalCar());
        $dispatch->setRentalStation($backRentalStation);
        $dispatch->setRentalOrder($order);
        $dispatch->setStatus(0);

        $order->getRentalCar()->setRentalStation($backRentalStation);
        $man->persist($rental_car);
        $man->flush();

        $man->persist($dispatch);
        $man->flush();

        $this->get("auto_manager.sms_helper")->add(
            $order->getMember()->getMobile(),
            $this->renderView(
                'AutoManagerBundle:Order:create.sms.twig',
                [
                    'license'=>$order->getRentalCar()->getLicense(),
                    'address'=>$order->getPickUpStation()->getName()
                ]
            ));



        // 通知第三方车辆不可租用
        $carPartnerData = [
            'carId' => $cid,
            'orderId' => $order->getId(),
            'rentalStation' => $rsid->getId(),
            'backRentalStation' => $back_station_id,
        ];
        $this->get("auto_manager.partner_helper")->carUnRental($carPartnerData);

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'order' =>call_user_func($this->get("auto_manager.order_helper")->get_rental_order_data_normalizer(),$order),
        ]);

    }






}
