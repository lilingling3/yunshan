<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

header('Access-Control-Allow-Origin:http://www.html5case.com.cn');
/**
 * @Route("/coupon")
 */
class CouponController extends BaseController
{
    const PER_PAGE = 5;

    /**
     * @Route("/list", methods="POST")
     */
    public function listAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }


        if($order_id){

            /**
             * @var $member \Auto\Bundle\ManagerBundle\Entity\RentalOrder
             */

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->find($order_id);

            if(empty($order)){

                return new JsonResponse([
                    'errorCode' =>  self::E_NO_ORDER,
                    'errorMessage' =>  self::M_NO_ORDER,
                ]);

            }

            if (!$order->getRentalCar()->getCar()) {

                return new JsonResponse([
                    'errorCode' =>  self::E_NO_RENTAL_CAR,
                    'errorMessage' =>  self::M_NO_RENTAL_CAR,
                ]);
            }
            $carLevel = $order->getRentalCar()->getCar()->getLevel();

            if(!$order->getEndTime()){

                return new JsonResponse([
                    'errorCode' =>  self::E_NOT_END_ORDER,
                    'errorMessage' =>  self::M_NOT_END_ORDER,
                ]);

            }
            $offsetHour = floor(($order->getEndTime()->getTimeStamp()-$order->getCreateTime()->getTimeStamp())/3600);

            $order_normalizer = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                $order);

            $amount = $order_normalizer['costDetail']['cost'];

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');

            $coupons =

                $qb
                ->select('c')
                ->join('c.kind','k')
                ->orderBy('k.amount','DESC')
                ->addOrderBy('c.endTime', 'ASC')
                ->where($qb->expr()->eq('c.member', ':member'))
                ->andWhere($qb->expr()->gte('c.endTime',':endTime'))
                ->andWhere($qb->expr()->lte('k.needAmount',':amount'))
                ->andWhere($qb->expr()->isNull('c.useTime'))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('k.carLevel', ':carlevel'),
                    $qb->expr()->isNull('k.carLevel')
                ))
                ->setParameter('member', $member)
                ->setParameter('amount', $amount)
                ->setParameter('carlevel', $carLevel)
                ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
                ->getQuery()
                ->getResult();
            ;


            $usable_coupon_list = [];
            $unusable_coupon_list = [];

            foreach($coupons as $coupon){

                if($coupon->getKind()->getNeedHour()<=$offsetHour){
                    $usable_coupon_list[] = call_user_func($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(),
                        $coupon,\Auto\Bundle\ManagerBundle\Entity\Coupon::ORDER_COUPON_USABLE);
                }else{
                    $unusable_coupon_list[] = call_user_func($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(),
                        $coupon,\Auto\Bundle\ManagerBundle\Entity\Coupon::ORDER_COUPON_UNUSABLE);
                }

            }

            $coupons = $usable_coupon_list;



            return new JsonResponse([
                'errorCode'  =>  self::E_OK,
                'pageCount'  =>  1,
                'page'       =>  1,
                'coupons'    =>  $coupons,
                'amount'     =>  $amount
            ]);


        }else{

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c')
            ;
            $qb
                ->select('c')
                ->join('c.kind','k')
                ->orderBy('c.useTime', 'DESC')
                ->addOrderBy('c.createTime', 'DESC')
                ->addOrderBy('k.amount', 'DESC')
                ->where($qb->expr()->eq('c.member', ':member'))
                ->setParameter('member', $member)
            ;


        $coupons =
            new Paginator(

                $qb->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        $valid_coupon = [];
        $unusable_coupon = [];



            $coupons_normalizer = array_map(
                function($coupon) use($order_id){

                    return call_user_func($this->get('auto_manager.coupon_helper')
                        ->get_coupon_normalizer(),
                        $coupon);

                },
                $coupons->getIterator()->getArrayCopy()
            );




            foreach($coupons_normalizer as $coupon){
                if($coupon['valid'] == \Auto\Bundle\ManagerBundle\Entity\Coupon::COUPON_USABLE){
                    $valid_coupon[] = $coupon;
                }else{
                    $unusable_coupon[] = $coupon;
                }
            }

            uasort($valid_coupon,function($a,$b){
                if ($a['couponID'] == $b['couponID']) return 0;
                return ($a['couponID'] < $b['couponID']) ? 1 : -1;
            });
            return new JsonResponse([
                'errorCode'  =>  self::E_OK,
                'pageCount'  =>  ceil($coupons->count() / self::PER_PAGE),
                'page'       =>  $page,
                'coupons'    =>  array_values(array_merge($valid_coupon,$unusable_coupon))
            ]);

        }




    }


    /**
     * @Route("/coupon/activity", methods="POST",name="auto_api_coupon_activity")
     */
    public function couponActivityAction(Request $req) {


        $order_id = $req->request->get('OrderID');

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id]);


        if (!empty($order)) {
            $this->get('auto_manager.coupon_helper')->coupon_activity_entrance($order);
        }

        return new JsonResponse([
                'errorCode'  =>  self::E_OK,
        ]);
    }



    /**
     * @Route("/usable/list", methods="POST",name="auto_api_coupon_usable_list")
     */
    public function usableListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c')
        ;
        $qb
            ->select('c')
            ->join('c.kind','k')
            ->orderBy('c.createTime', 'DESC')
            ->addOrderBy('k.amount', 'DESC')
            ->where($qb->expr()->eq('c.member', ':member'))
            ->andWhere($qb->expr()->gte('c.endTime', ':time'))
            ->andWhere($qb->expr()->isNull('c.useTime'))
            ->setParameter('member', $member)
            ->setParameter('time', (new \DateTime())->format('Y-m-d'))
        ;

        $coupons =
            new Paginator(
                $qb->setMaxResults(self::PER_PAGE)
                   ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        return new JsonResponse([
            'errorCode' =>self::E_OK,
            'pageCount' =>ceil($coupons->count() / self::PER_PAGE),
            'page'      =>$page,
            'coupons'    =>array_map($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(),
                $coupons->getIterator()->getArrayCopy()),
        ]);
    }

    /**
     * @Route("/unusable/list", methods="POST",name="auto_api_coupon_unusable_list")
     */
    public function unusableListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c')
        ;
        $qb
            ->select('c')
            ->join('c.kind','k')
            ->orderBy('c.endTime', 'desc')
            ->where($qb->expr()->eq('c.member', ':member'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->lt('c.endTime', ':time'),
                $qb->expr()->isNotNull('c.useTime')
            ))
            ->setParameter('member', $member)
            ->setParameter('time', (new \DateTime('today')))

        ;

        $coupons =
            new Paginator(

                $qb->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        return new JsonResponse([
            'errorCode' =>self::E_OK,
            'pageCount' =>ceil($coupons->count() / self::PER_PAGE),
            'page'      =>$page,
            'coupons'    =>array_map($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(),
                $coupons->getIterator()->getArrayCopy()),
        ]);
    }


    /**
     * @Route("/appShare", methods="POST",name="auto_api_coupon_app_share")
     */
    public function appShareAction(Request $req)
    {
        $uid = $req->request->get('userID');

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'title' =>'云杉智行，邀您加入绿色出行！',
            'content'=>'新用户可领取30元活动优惠券！',
            'link' => $this->generateUrl('auto_wap_coupon_share_app',['aid'=>3290,'uid'=>$uid]),
            'logo' =>'http://bkcar.cn/bundles/autowap/images/tou1.png'
        ]);

    }

    /**
     * @Route("/get/appShare", methods="POST",name="auto_api_coupon_get_app_share")
     */
    public function appShare(Request $req){

        $activity_id = $req->request->getInt('couponActivityID');
        $mobile = $req->request->get('mobile');

        if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){
            return new JsonResponse([
                'errorCode'    =>  self::E_WRONG_MOBILE,
                'errorMessage' =>  self::M_WRONG_MOBILE
            ]);
        }

        if($activity_id!=3290){
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_COUPON_CODE,
                'errorMessage' =>  self::M_NO_COUPON_CODE
            ]);

        }

        $couponActivity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->findOneBy(['id'=>$activity_id]);

        $check = $this->checkCouponActivity($couponActivity);

        if(!empty($check)){
            return new JsonResponse($check);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);


        if(!empty($member)){

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');
            $coupon =
                $qb->select('c')
                    ->andWhere($qb->expr()->eq('c.member', ':member'))
                    ->setParameter('member', $member)
                    ->getQuery()
                    ->getResult()
            ;

        }else{

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');
            $coupon =
                $qb->select('c')
                    ->andWhere($qb->expr()->eq('c.mobile', ':mobile'))
                    ->setParameter('mobile', $mobile)
                    ->getQuery()
                    ->getResult()
            ;

        }

        if(!empty($coupon)){
            return new JsonResponse([
                'errorCode'    =>  self::E_HAS_TAKEN_COUPON,
                'errorMessage' =>  self::M_HAS_TAKEN_COUPON
            ]);
        }

        $this->get('auto_manager.coupon_helper')->send_activity_coupon($couponActivity,$member,$mobile);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK
        ]);





////        $uid = $req->request->get('userID');
//        $activity_id = $req->request->getInt('couponActivityID');
//        $mobile = $req->request->get('mobile');
//
//        if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){
//            return new JsonResponse([
//                'errorCode'    =>  self::E_WRONG_MOBILE,
//                'errorMessage' =>  self::M_WRONG_MOBILE
//            ]);
//        }
//
//        if($activity_id!=3290){
//            return new JsonResponse([
//                'errorCode'    =>  self::E_NO_COUPON_CODE,
//                'errorMessage' =>  self::M_NO_COUPON_CODE
//            ]);
//
//        }
//
//        $couponActivity = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:CouponActivity')
//            ->findOneBy(['id'=>3290]);
//
//        $check = $this->checkCouponActivity($couponActivity);
//
//        if(!empty($check)){
//            return new JsonResponse($check);
//        }
//
//        $member = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:Member')
//            ->findOneBy(['mobile'=>$mobile]);
//
//        $qb =
//            $this
//                ->getDoctrine()
//                ->getRepository('AutoManagerBundle:Coupon')
//                ->createQueryBuilder('c');
//        $coupon =
//            $qb->select('c')
//                ->where($qb->expr()->eq('c.mobile', ':mobile'))
//                ->andWhere($qb->expr()->eq('c.member', ':member'))
//                ->setParameter('mobile', $mobile)
//                ->setParameter('member', $member)
//                ->getQuery()
//                ->getResult()
//        ;
//
//        if(!empty($coupon)){
//            return new JsonResponse([
//                'errorCode'    =>  self::E_HAS_TAKEN_COUPON,
//                'errorMessage' =>  self::M_HAS_TAKEN_COUPON
//            ]);
//        }
//
//        $this->get('auto_manager.coupon_helper')->send_activity_coupon($couponActivity,$member,$mobile);
//
//        return new JsonResponse([
//            'errorCode'    =>  self::E_OK
//        ]);

    }


    /**
     * @Route("/orderShare", methods="POST",name="auto_api_coupon_order_share")
     */
    public function orderShareAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($order_id);

        $check = $this->checkMemberRentalOrder($member,$order);
        if(!empty($check)){
            return new JsonResponse($check);
        }

        $activity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->findOneBy(['order'=>$order_id]);

        if(!empty($activity)){
            return new JsonResponse([
                'errorCode'  =>  self::E_OK,
                'title' =>'云杉智行，邀您加入绿色出行！',
                'content'=>'使用云杉智行得出行优惠券！',
                'link' => $this->generateUrl('auto_wap_coupon_share_order',['aid'=>$activity->getId(),'oid'=>$order_id]),
                'logo' =>'http://bkcar.cn/bundles/autowap/images/tou1.png'
            ]);
        }

        $couponActivity = new \Auto\Bundle\ManagerBundle\Entity\CouponActivity();
        $couponActivity->setOnline(1);
        $couponActivity->setCount(1);
        $couponActivity->setName($member->getName().'分享订单赢优惠券');
        $couponActivity->setOrder($order);
        $couponActivity->setTotal(20);

        foreach([14] as $kind_id){

            $kind = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:CouponKind')
                ->find($kind_id);

            if(!empty($kind)) $couponActivity->addKind($kind);

        }


        $man = $this->getDoctrine()->getManager();
        $man->persist($couponActivity);
        $man->flush();

        return new JsonResponse([

            'errorCode'  =>  self::E_OK,
            'title' =>'云杉智行，邀您加入绿色出行！',
            'content'=>'使用云杉智行得出行优惠券！',
            'link' => $this->generateUrl('auto_wap_coupon_share_order',['aid'=>$couponActivity->getId(),'oid'=>$order_id]),
            'logo' =>'http://bkcar.cn/bundles/autowap/images/tou1.png'

        ]);

    }

    /**
     * @Route("/get/orderShare", methods="POST",name="auto_api_coupon_get_order_share")
     */
    public function getOrderShareAction(Request $req){

        $mobile = $req->request->get('mobile');
        $activity_id = $req->request->getInt('couponActivityID');
        $order_id = $req->request->getInt('orderID');

        $couponActivity= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->findOneBy(['id'=>$activity_id,'order'=>$order_id]);

        $this->checkCouponActivity($couponActivity);

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');
        $coupon =
            $qb->select('c')
                ->join('c.activity','a')
                ->leftJoin('c.member','m')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('c.mobile', ':mobile'),
                    $qb->expr()->eq('m.mobile', ':mobile')
                ))
                ->andWhere($qb->expr()->eq('c.activity', ':activity'))
                ->setParameter('mobile', $mobile)
                ->setParameter('activity', $couponActivity)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if(!empty($coupon)){
            return new JsonResponse([
                'errorCode'    =>  self::E_HAS_TAKEN_COUPON,
                'errorMessage' =>  self::M_HAS_TAKEN_COUPON,
                'couponAmount' => $coupon->getKind()->getAmount()

            ]);
        }

        //是否已领完
        if($couponActivity->getTotal()>0){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');

            $activity_coupon_count =
                $qb
                    ->select($qb->expr()->count('c'))
                    ->where($qb->expr()->eq('c.activity', ':activity'))
                    ->setParameter('activity', $couponActivity)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;


            if($activity_coupon_count>=$couponActivity->getTotal()){

                return new JsonResponse([
                    'errorCode'    =>  self::E_NO_ACTIVITY_COUPON,
                    'errorMessage' =>  self::M_NO_ACTIVITY_COUPON
                ]);
            }

        }



        $amount = $this->get('auto_manager.coupon_helper')->send_activity_coupon($couponActivity,$member,$mobile);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'couponAmount' => $amount
        ]);

    }

    /**
     * @Route("/get", methods="POST",name="auto_api_coupon_get")
     */

    public function getAction(Request $req){

        $mobile = $req->request->get('mobile');
        $activity_id = $req->request->getInt('couponActivityID');
        $today = $req->request->getInt('today');

        $couponActivity= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->findOneBy(['id'=>$activity_id]);

        if(empty($couponActivity))
        {
            return new JsonResponse([
                'errorCode' =>  self::E_NO_COUPON_ACTIVITY,
                'errorMessage' =>self::M_NO_COUPON_ACTIVITY,
            ]);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        $qb->select('c')
            ->join('c.activity','a')
            ->leftJoin('c.member','m')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('c.mobile', ':mobile'),
                $qb->expr()->eq('m.mobile', ':mobile')
            ));

        if($today){
            $qb->andWhere($qb->expr()->gte('c.createTime', ':min_time'))
                ->setParameter('min_time', (new \DateTime())->format('Y-m-d'));
            $qb->andWhere($qb->expr()->lte('c.createTime', ':max_time'))
                ->setParameter('max_time', (new \DateTime())->modify('+1 days')->format('Y-m-d'));
        }

        $coupon = $qb->andWhere($qb->expr()->eq('c.activity', ':activity'))
            ->setParameter('mobile', $mobile)
            ->setParameter('activity', $couponActivity)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if(!empty($coupon)){
            return new JsonResponse([
                'errorCode'    =>  self::E_HAS_TAKEN_COUPON,
                'errorMessage' =>  self::M_HAS_TAKEN_COUPON,
                'couponAmount' => $coupon->getKind()->getAmount()

            ]);
        }

        //是否已领完
        if($couponActivity->getTotal()>0){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');

            $activity_coupon_count =
                $qb
                    ->select($qb->expr()->count('c'))
                    ->where($qb->expr()->eq('c.activity', ':activity'))
                    ->setParameter('activity', $couponActivity)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;


            if($activity_coupon_count>=$couponActivity->getTotal()){

                return new JsonResponse([
                    'errorCode'    =>  self::E_NO_ACTIVITY_COUPON,
                    'errorMessage' =>  self::M_NO_ACTIVITY_COUPON
                ]);
            }

        }



        $coupon_amount = $this->get('auto_manager.coupon_helper')->send_activity_coupon($couponActivity,$member,
            $mobile);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'couponAmount' => $coupon_amount
        ]);



    }

    /**
     * 捉宠物游戏获得优惠劵接口
     * @Route("/getCoupon", methods="POST",name="auto_api_coupon_getCoupon")
     */

    public function getCouponAction(Request $req){


//        $forbid_ip_array=[
//            '60.205.90.202',
//        ];
//
//        $user_ip = $this->get_client_ip();
//
//        if(!in_array($user_ip,$forbid_ip_array)){
//
//            return new JsonResponse([
//                'errorCode'    =>  false,
//                'couponAmount' => '该ip无访问接口权限！'
//            ]);
//
//        }

        $mobile = $req->request->get('mobile');

        if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){
            return new JsonResponse([
                'errorCode'    =>  self::E_WRONG_MOBILE,
                'errorMessage' =>  self::M_WRONG_MOBILE
            ]);
        }

        $couponAmount = $req->request->get('couponAmount');

        //正式环境使用
        switch ($couponAmount) {
            case 99:
                $activity_id = 32959;
                break;
            case 59:
                $activity_id = 32958;
                break;
            case 35:
                $activity_id = 32955;
                break;
            case 19:
                $activity_id = 32952;
                break;
            case 9:
                $activity_id = 32949;
                break;
            case 5:
                $activity_id = 32946;
                break;
        }

//        switch ($couponAmount) {
//            case 99:
//                $activity_id = 16028;
//                break;
//            case 59:
//                $activity_id = 16025;
//                break;
//            case 35:
//                $activity_id = 16024;
//                break;
//            case 19:
//                $activity_id = 16023;
//                break;
//            case 9:
//                $activity_id = 16020;
//                break;
//            case 5:
//                $activity_id = 16018;
//                break;
//        }

        $zhua_activity_id = array(

//            16028,
//            16025,
//            16024,
//            16023,
//            16020,
//            16018,


//        正式环境活动id
            32959,
            32958,
            32955,
            32952,
            32949,
            32946,



        );

        $today = $req->request->getInt('today');

        $couponActivity= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->findOneBy(['id'=>$activity_id]);

        if(empty($couponActivity))
        {
            return new JsonResponse([
                'errorCode' =>  self::E_NO_COUPON_ACTIVITY,
                'errorMessage' =>self::M_NO_COUPON_ACTIVITY,
            ]);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        $qb->select('c')
            ->join('c.activity','a')
            ->leftJoin('c.member','m')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('c.mobile', ':mobile'),
                $qb->expr()->eq('m.mobile', ':mobile')
            ));

        if($today){
            $qb->andWhere($qb->expr()->gte('c.createTime', ':min_time'))
                ->setParameter('min_time', (new \DateTime())->format('Y-m-d'));
            $qb->andWhere($qb->expr()->lte('c.createTime', ':max_time'))
                ->setParameter('max_time', (new \DateTime())->modify('+1 days')->format('Y-m-d'));
        }


        $coupon_qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        if($member){

            $activity_data = $coupon_qb->select('c')
                ->join('c.activity','a')
                ->leftJoin('c.member','m')
                ->where($qb->expr()->eq('m.mobile', ':mbi'))
                ->setParameter('mbi', $mobile)
                ->getQuery()
                ->getResult();
            ;


        }else{


            $activity_data = $coupon_qb->select('c')
                ->join('c.activity','a')
                ->where($qb->expr()->eq('c.mobile', ':mbi'))
                ->setParameter('mbi', $mobile)
                ->getQuery()
                ->getResult();
            ;


        }

        $e = 0;

        if(!empty($activity_data)){

            foreach($activity_data as $ac){

                $ac_id = $ac->getActivity()->getId();

                if(in_array($ac_id,$zhua_activity_id)){

                    $e++;

                }


            }

        }

        if($e>0){
            return new JsonResponse([
                'errorCode'    =>  self::E_HAS_TAKEN_COUPON,
                'errorMessage' =>  self::M_HAS_TAKEN_COUPON,

            ]);
        }



        //是否已领完
        if($couponActivity->getTotal()>0){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');

            $activity_coupon_count =
                $qb
                    ->select($qb->expr()->count('c'))
                    ->where($qb->expr()->eq('c.activity', ':activity'))
                    ->setParameter('activity', $couponActivity)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;


            if($activity_coupon_count>=$couponActivity->getTotal()){

                return new JsonResponse([
                    'errorCode'    =>  self::E_NO_ACTIVITY_COUPON,
                    'errorMessage' =>  self::M_NO_ACTIVITY_COUPON
                ]);
            }

        }



       $this->get('auto_manager.coupon_helper')->send_activity_coupon($couponActivity,$member,
            $mobile);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'errorMessage' => '您已成功领取！'
        ]);



    }

    //获得客户端的ip
    public function get_client_ip()
    {
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }







    /**
     * 七夕情人节活动获得匹配分数和酒店相关信息
     * @Route("/getValentineData", methods="POST",name="auto_api_coupon_getValentineData")
     */

    public function getValentineData(Request $req){

            $man_name = $req->request->get('man_name');

            $woman_name = $req->request->get('woman_name');

            $redis = $this->container->get('snc_redis.default');

            $redis_cmd_llen = $redis->createCommand('llen',array('valentine-fullmark-mobile'));

            $llen = $redis->executeCommand($redis_cmd_llen);


             if($llen<3){

                $full_mark = rand(1,1000);

            }else{

                $full_mark = 0;

            }

//            $str = $man_name.$woman_name;
//
//            $mdStr = md5($str);
//
//        $e = 0;
//
//            for($i=0;$i<31;$i++){
//
//                if(is_numeric($mdStr[$i])){
//
//                    $e += $mdStr[$i];
//
//                }
//
//            }

            $random_number = rand(0,15);

            if($full_mark == 100){

                $date_data = array(

                    'site-name'=>'桔子水晶酒店（北京安贞店）',
                    'site-img'=>'/bundles/autowap/images/77-17.jpg',
                    'site-address'=>'北京市朝阳区小黄庄北街2号2幢 ，近地铁5号线和平西桥站。',
                    'site-phone'=>'010-84273030',
                    'site-reason'=>'情侣必去的酒店啊~今晚创造二人的独处时间吧~',
                    'site-vegetable'=>'',
                    'sweet-index'=>'100',
                    'sweet100'=>'恭喜您获得满分七夕大礼：我们将为您提供北京桔子酒店豪华大床房七夕（2016年8月9日）当晚居住权。惊喜大奖领取时间为：2016年8月8号12点至2016年8月9号18点。请填写正确联系方式并保持电话畅通，稍后云杉智行工作人员会与您联系领奖事宜。(押金自理，除房费以外费用自行承担！！！)',
                    'sweet-text'=>'原来传说中的百分百爱人就是你们啦！恭喜您获得云杉智行亲情赞助撩约神器+桔子水晶房卡大礼包~快快行动吧~',
                    'cactivity-id'=>'23203',

                );
            }else if($random_number == 0){

                $date_data = array(

                    "site-name"=>"板桥7号米其林主厨餐厅 ",
                    "site-img"=>"/bundles/autowap/images/77-01.jpg",
                    "site-address"=>"东城区东四北大街板桥南巷7号人民美术印刷厂17号",
                    "site-phone"=>"010-84037177",
                    'sweet100'=>'',
                    'sweet-index'=>'50',
                    "site-reason"=>"中西创意菜品。印刷厂房改建的LOFT餐馆，进门是画廊的风格很有艺术感;格局分明，屋子内还有小桥流水，帷幔拉起来很私密。适合街拍和爱创意的盆友们!",
                    "site-vegetable"=>"餐前酸奶、牛排、樟茶鸭。",
                    "sweet-text"=>"嗯~今天的甜蜜指数适中，要加把劲哟~嘿咻嘿咻",
                    "cactivity-id"=>"23188"


                );

            }else if($random_number == 1){

                $date_data = array(

                    "site-name"=>"彼德西餐厅(将台西路店)",
                    "site-img"=>"/bundles/autowap/images/77-02.jpg",
                    "site-address"=>"朝阳区 将台西路9-2号(日本人学校对面，近柏丽酒店，四得公园)",
                    "site-phone"=>"010-64353509",
                    "site-reason"=>"原木桌椅，大块桌布，柔和灯光，浪漫而温馨的墨西哥餐厅。Fajita是最具代表性的料理，肉类在松软卷饼的烘托下，口感达到了最佳状态。",
                    "site-vegetable"=>"烤薯皮、法嘿塔、主厨沙拉、牛油果酿鸡。",
                    "sweet-index"=>"30",
                    'sweet100'=>'',
                    "sweet-text"=>'啊~今天的甜蜜指数有点不满意，赶紧大餐一顿补救一下吧~ 俗话说的好：只要吃的饱，什么都说“好”！',
                    "cactivity-id"=>"23194"

                );

            }else if($random_number == 2){

                $date_data = array(

                    "site-name"=>"唐廊(工体店)",
                    "site-img"=>"/bundles/autowap/images/77-03.jpg",
                    "site-address"=>"朝阳区 三里屯北街81号 那里花园4层",
                    "site-phone"=>"010-52086188",
                    "site-reason"=>"踏进闹中取静的庭院，亦优雅亦温馨;走入晶莹的玻璃幕墙内，丝丝唐韵中透出缕缕欧陆风情;就餐区域挑空很高，桌椅间隔宽敞，服务也特别周到。菜品中西合璧，做得很精致。",
                    "site-vegetable"=>"宫保虾球、鹅肝酱茄子、杨汁金露、金丝奶酪虾。",
                    "sweet-index"=>"10",
                    'sweet100'=>'',
                    "sweet-text"=>"今天的甜蜜指数Low到极点？要不试试用撩约神器带ta去吃好吃的吧~",
                    "cactivity-id"=>"23200"

                );

            }else if($random_number == 3){

                $date_data = array(

                    "site-name"=>"agua 西班牙餐厅",
                    "site-img"=>"/bundles/autowap/images/77-04.jpg",
                    "site-address"=>"朝阳区 三里屯北街81号 那里花园4层",
                    "site-phone"=>"010-52086188",
                    "site-reason"=>"在北京算得上是不错的西班牙餐厅了!位于异域风情的小楼里，桌上可爱的,烛台透出暖暖的粉红色灯光，水杯造型也不失可爱。",
                    "site-vegetable"=>"烤乳猪、海鲜饭、Tapas。",
                    "sweet-index"=>"40",
                    'sweet100'=>'',
                    "sweet-text"=>"今天的甜蜜指数一般哟，没关系有撩约神器在手，撩谁有谁~",
                    "cactivity-id"=>"23191"

                );

            }else if($random_number == 4){

                $date_data = array(

                    "site-name"=>"丽江庭院之爱在路上(西单店)",
                    "site-img"=>"/bundles/autowap/images/77-05.jpg",
                    "site-address"=>"西城区 大酱坊胡同7号",
                    "site-phone"=>"010-66034663",
                    "site-reason"=>"老北京院门里的火锅店。冬天待在玻璃房子里吃火锅晒太阳很舒服。一人一个小锅，有鸡汤和菌汤两种锅底可选，涮菜前先要品尝原汤，味道很香!推荐赠送的玫瑰饮料哦!",
                    "site-vegetable"=>"斑鱼片、各种丸子、玫瑰饮料。",
                    "sweet-index"=>"60",
                    'sweet100'=>'',
                    "sweet-text"=>"匹配指数终于及格啦~要加油表现才能更上一层楼啊~",
                    "cactivity-id"=>"23185"

                );

            }else if($random_number == 5){

                $date_data = array(

                    "site-name"=>"Capital M(前门M餐厅)",
                    "site-img"=>"/bundles/autowap/images/77-06.jpg",
                    "site-address"=>"东城区 前门步行街2号3楼(大北照相馆后面3楼；前门东路刘老跟大舞台对面有地下停车场)",
                    "site-phone"=>"010-67022727",
                    "site-reason"=>"前门M餐厅亮相于历史浓厚的北京前门步行街。这将是继香港Mat the Fringe、上海外滩M餐厅(Mon the Bund)及魅力酒吧(Glamour Bar)之后，米氏餐饮的全新力作。一脉传承的臻品佳肴、无与伦比。",
                    "site-vegetable"=>"蛋白饼、烟熏三文鱼、Brunch、牛舌、烤乳猪、鹅肝、肉眼牛排",
                    "sweet-index"=>"20",
                    'sweet100'=>'',
                    "sweet-text"=>"今日约会注意事项：吃大餐，少说话，多卖力！",
                    "cactivity-id"=>"23197"

                );

            }else if($random_number == 6){

                $date_data = array(

                    "site-name"=>"皇家驿栈酒店饮酒吧",
                    "site-img"=>"/bundles/autowap/images/77-07.jpg",
                    "site-address"=>"东城区 骑河楼街33号皇家驿栈酒店4-5楼(故宫东北池子大街)",
                    "site-phone"=>"010-67022727",
                    "site-reason"=>'山，原本是明成祖朱棣兴建紫禁城时，取风水说法“靠山”之意而修；然此靠山，终不可靠。想当年崇祯帝残步上山，俯瞰祖先一手建立的紫禁城被清军蚕食鲸吞， 不知是否曾有"前不见古人，后不见来者，念天地之悠悠，独怆然而涕下"的孤独和伤感。往事往矣，如今登上食餐厅顶层露台，景山、白塔、故宫尽收眼底，沐浴 着夕阳的余辉，无尽遐思，神思渺渺。这里出品新派创意菜，菜品精致小巧。',
                    "site-vegetable"=>"御品香鳕鱼、香芒咖喱醉甜虾",
                    "sweet-index"=>"70",
                    'sweet100'=>'',
                    "sweet-text"=>"哇哦，今天肯定会有一个让你意想不到的约会的~记得保留体力，抓住重点哟~",
                    "cactivity-id"=>"23182"

                );

            }else if($random_number == 7){

                $date_data = array(

                    "site-name"=>"在神秘的车里",
                    "site-img"=>"/bundles/autowap/images/77-16.jpg",
                    "site-address"=>"寂静停车地点——宽敞车内",
                    "site-phone"=>"",
                    "site-reason"=>"私密指数五颗星，刺激指数十颗星，有什么理由不尝试一下呢？不过要注意时间、地点以及力度哦~",
                    "site-vegetable"=>"",
                    "sweet-index"=>"99",
                    'sweet100'=>'',
                    "sweet-text"=>"二人驱车到达一个僻静的地段，属于你们的晚上，属于你们二人的私密空间。（PS：记得车上的小礼物，没准会用的到哟）",
                    "cactivity-id"=>"23178"

                );

            }else if($random_number == 8){

                $date_data = array(

                    "site-name"=>"直樹怀石料理 Naoki",
                    "site-img"=>"/bundles/autowap/images/77-09.jpg",
                    "site-address"=>"海淀区 颐和园宫门前街1号 颐和安缦酒店(近颐和园东宫门)",
                    "site-phone"=>"59879999-7456",
                    "site-reason"=>"清 代皇帝每年要在颐和园呆6-10个月，从某种意义上来说，颐和园才是皇帝真正的家，紫禁城只是他的OFFICE。来自日本的主 厨 Naoki Okumura 在颐和园旁开创了法式日料餐厅，在原颐和园建筑的基础上修建，将整座餐厅融入到颐和园的景色中。古香古韵的环境无需过多 的装饰，金碧辉煌在这里略显几分庸俗。你可以坐在室内，推开整扇落地窗，将园中的春色收入眼帘。也可以坐在园中的水池旁，品尝佳肴的同时感受这里透气的环 境。春风拂过水面，一层层涟漪只能扰了水中的倒影，安静的环境足以满足你对安逸时光的渴望。",
                    "site-vegetable"=>"照烧鸡、龙虾羹、火叶牛",
                    "sweet-index"=>"90",
                    'sweet100'=>'',
                    "sweet-text"=>"天啊，简直就是天作之合，什么也阻挡不了你们的爱爱了。准备好来一个甜蜜的约会吧~",
                    "cactivity-id"=>"23178"

                );

            }else if($random_number == 9){

                $date_data = array(

                    "site-name"=>"北京亮餐厅",
                    "site-img"=>"/bundles/autowap/images/77-10.jpg",
                    "site-address"=>"朝阳区建国门外大街2号柏悦酒店66楼",
                    "site-phone"=>"010-85671838",
                    "site-reason"=>"待 我君临天下，许你一世繁华。”曾经的北京最高餐厅，如今仍是国贸最佳的视点。长长的落地窗，使每个座位都可以饱览美景。坐在这里，俯瞰长安街，横贯东西， 车流如注；眺望CBD，老国贸、国贸三期、央视大裤衩挺拔耸立，气势雄浑。上夜赴宴，星罗棋布的光结成斑斓奇幻的网，映着你的脸红，映着他的眼醉。这巨大 的太空舱漂浮在国贸的上空，无论你看不看它，它其实都在看你。",
                    "site-vegetable"=>"海鲜拼盘、餐前面包、土豆泥",
                    "sweet-index"=>"95",
                    'sweet100'=>'',
                    "sweet-text"=>"天啊，简直就是天作之合，什么也阻挡不了你们的爱爱了。准备好来一个甜蜜的约会吧~",
                    "cactivity-id"=>"23178"

                );

            }else if($random_number == 10){

                $date_data = array(

                    "site-name"=>"北京蜜糖酒店",
                    "site-img"=>"/bundles/autowap/images/77-11.jpg",
                    "site-address"=>"北京海淀区北土城西路146号 ( 五道口、学院路|北太平庄区域)",
                    "site-phone"=>"010-82081077",
                    "site-reason"=>"酒店“平价奢华，贴心细致 ，温馨浪漫、简爱细微，干净、舒服、浪漫的艺术酒店” 无论您走到哪里，都将体验到的最佳人文住宿环境。 酒店用心做到尽善尽美，舒适的浪漫情怀体验，为相爱的情侣提供尊贵、奢华、独特、艺术文化，高品质、个性化的服务。酒店面对牡丹公园，各大名校，周围环绕各类餐饮、购物小店，距离5米十号线A地铁口，出行方便。18间每房风格设计，速速与激情、我行我素、光幻影侣、铁达尼号、玫瑰时代、浓情时光、午夜旋律盗梦出蜜糖之夜。",
                    "site-vegetable"=>"",
                    "sweet-index"=>"55",
                    'sweet100'=>'',
                    "sweet-text"=>"其实匹配指数并不重要，一间浪漫的房间，两个人的独处，你的ta绝对秒变小绵羊啦~",
                    "cactivity-id"=>"23188"

                );

            }else if($random_number == 11){

                $date_data = array(

                    "site-name"=>"北京威尔曼主题酒店",
                    "site-img"=>"/bundles/autowap/images/77-12.jpg",
                    "site-address"=>"北京 大兴区 大兴区兴华大街三段25号 ，近黄村西大街。",
                    "site-phone"=>"010-69262556",
                    "site-reason"=>"北京威尔曼主题酒店有特色风格主题房间14间，融入欧式、中式、浪漫、梦幻、田园等特色主题设计，为住客提供了更多的选择，同时酒店配带营养早餐服务。是情侣休闲，约会和旅游中的良选。",
                    "site-vegetable"=>"",
                    "sweet-index"=>"65",
                    'sweet100'=>'',
                    "sweet-text"=>"传说中，七夕是异地恋相聚的节日，异地恋那么久积攒的想念全都在今天大声说出来吧~（PS：记得关灯！）",
                    "cactivity-id"=>"23185"

                );

            }else if($random_number == 12){

                $date_data = array(

                    "site-name"=>"北京品爱主题酒店",
                    "site-img"=>"/bundles/autowap/images/77-13.jpg",
                    "site-address"=>"北京朝阳区百子湾西里104号 ( 百子湾地区|四惠区域)",
                    "site-phone"=>"010-87725022",
                    "site-reason"=>"一家极具特色的情侣主题酒店，酒店聘请知名设计师团队精心打造33个不同情景主题，每一个主题都演绎着一个浪漫梦幻的场景，每一个房间都带给您不同的视觉感受及浪漫温馨的氛围。音乐之声，窈窕淑女，梦幻KITTY，丛林密语，阿拉伯之夜，雪花物语，荷塘月色。。。。。想象中的意境，行走中的美景均以绚丽的色彩，温馨浪漫的灯光，精致的布局唯美展现，为您营造浪漫温馨时刻，打造属于您的幸福感受！ 酒店在配置上启用了全新的智能管理系统，豪华圆床水床，高清液晶电视，豪华按摩冲浪浴缸，精品个人的卫浴，都将带给您精致卓越的品质，满足您便捷舒适的需求。酒店独特而有个性的设计和细节将带给您不一样的感受，专享的细节服务更会给您温馨甜蜜的体验",
                    "site-vegetable"=>"",
                    "sweet-index"=>"75",
                    'sweet100'=>'',
                    "sweet-text"=>"记得买上一瓶红酒，两人关灯夜话，真的是没有比这样更让人心动的约会啦~",
                    "cactivity-id"=>"23182"


                );

            }else if($random_number == 13){

                $date_data = array(

                    "site-name"=>"北京F6时尚主题酒店",
                    "site-img"=>"/bundles/autowap/images/77-14.jpg",
                    "site-address"=>"北京市东城区国瑞北路82号，近国瑞购物中心 (崇文门商圈)",
                    "site-phone"=>"010-87189973",
                    "site-reason"=>"酒店精心制作了五大系列主题：大自然、动漫、汽车、梦幻、都市，合共二十多种主题房间，每个房间都独一无二，精心制作，房间的墙身画全是3D视觉后果，舒适软和的梦幻水床，加上各种装饰品，目标是360度拍摄无死角，不要吝啬你的表情，牵着她的手，赶紧过来吧！",
                    "site-vegetable"=>"",
                    "sweet-index"=>"45",
                    'sweet100'=>'',
                    "sweet-text"=>"今晚属于你们的二人时间里，切记不要发生冲突和争吵哟~",
                    "cactivity-id"=>"23191"

                );

            }else if($random_number == 14){

                $date_data = array(

                    "site-name"=>"蓝色港湾",
                    "site-img"=>"/bundles/autowap/images/77-15.jpg",
                    "site-address"=>"朝阳公园路6号蓝色港湾",
                    "site-phone"=>"",
                    "site-reason"=>"蓝色港湾国际商区将丰富业态与优美环境融合，在朝阳公园的广阔湖面和亮马河碧澈河水围绕之中，绽放出美丽丰姿。拥有精雕细琢的水景、花团锦簇的空中花园以及由300多棵树木构成SOLANA品牌森林，是都市水泥森林中的一处稀有风景。夜晚浪漫的灯光街牵着TA的手走过	~很难想象这种浪漫气氛的交汇下会促生多少荷尔蒙分泌。",
                    "site-vegetable"=>"",
                    "sweet-index"=>"85",
                    'sweet100'=>'',
                    "sweet-text"=>"约会小贴士：浪漫的约会地点会让二人的荷尔蒙分泌旺盛哟~（PS：让后面的事情变顺理成章啦~）",
                    "cactivity-id"=>"23181"

                );

            }


        $date_data['man_name'] = $man_name;

        $date_data['woman_name'] = $woman_name;


        return new JsonResponse(

            $date_data

        );


    }




    /**
     * 七夕情人节活动
     * @Route("/get2", methods="POST",name="auto_api_coupon_get2")
     */

    public function get2Action(Request $req){

        $mobile = $req->request->get('mobile');
        $activity_id = $req->request->getInt('couponActivityID');
        $today = $req->request->getInt('today');
        $couponActivity= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->findOneBy(['id'=>$activity_id]);

        if(empty($couponActivity))
        {
            return new JsonResponse([
                'errorCode' =>  self::E_NO_COUPON_ACTIVITY,
                'errorMessage' =>self::M_NO_COUPON_ACTIVITY,
            ]);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        $qb->select('c')
            ->join('c.activity','a')
            ->leftJoin('c.member','m')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('c.mobile', ':mobile'),
                $qb->expr()->eq('m.mobile', ':mobile')
            ));

        if($today){
            $qb->andWhere($qb->expr()->gte('c.createTime', ':min_time'))
                ->setParameter('min_time', (new \DateTime())->format('Y-m-d'));
            $qb->andWhere($qb->expr()->lte('c.createTime', ':max_time'))
                ->setParameter('max_time', (new \DateTime())->modify('+1 days')->format('Y-m-d'));
        }

//        $coupon = $qb->andWhere($qb->expr()->eq('c.activity', ':activity'))
//            ->setParameter('mobile', $mobile)
//            ->setParameter('activity', $couponActivity)
//            ->setMaxResults(1)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;

        //七夕活动优惠劵领取判断开始

        $valentine_activity_id = array(
            23203,
            23200,
            23197,
            23194,
            23191,
            23188,
            23185,
            23182,
            23181,
            23178,
//              15989 ,
//              15987 ,
//              15985 ,
//              15984 ,
//              15982 ,
//              15979 ,
//              15976 ,
//              15975 ,
//              15974 ,
//              15973 ,
//              15992

        );

        $qb =
            $this
               ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');


        if($member){

            $activity_data = $qb->select('c')
                ->join('c.activity','a')
                ->leftJoin('c.member','m')
                ->where($qb->expr()->eq('m.mobile', ':mbi'))
                ->setParameter('mbi', $mobile)
                ->getQuery()
                ->getResult();
            ;


        }else{


            $activity_data = $qb->select('c')
                ->join('c.activity','a')
                ->where($qb->expr()->eq('c.mobile', ':mbi'))
                ->setParameter('mbi', $mobile)
                ->getQuery()
                ->getResult();
            ;


        }


        $e = 0;

        if(!empty($activity_data)){

            foreach($activity_data as $ac){

                $ac_id = $ac->getActivity()->getId();

                if(in_array($ac_id,$valentine_activity_id)){

                    $e++;

                }


            }

        }



        if($e>0){
            return new JsonResponse([
                'errorCode'    =>  self::E_HAS_TAKEN_COUPON,
                'errorMessage' =>  self::M_HAS_TAKEN_COUPON,

            ]);
        }


        //是否已领完
        if($couponActivity->getTotal()>0){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Coupon')
                    ->createQueryBuilder('c');

            $activity_coupon_count =
                $qb
                    ->select($qb->expr()->count('c'))
                    ->where($qb->expr()->eq('c.activity', ':activity'))
                    ->setParameter('activity', $couponActivity)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;

            if($activity_coupon_count>=$couponActivity->getTotal()){

                return new JsonResponse([
                    'errorCode'    =>  self::E_NO_ACTIVITY_COUPON,
                    'errorMessage' =>  self::M_NO_ACTIVITY_COUPON
                ]);
            }



        }

        //优惠劵领取判断结束

        if($activity_id == 23203){

            $redis = $this->container->get('snc_redis.default');

            $redis_cmd= $redis->createCommand('lpush', array("valentine-fullmark-mobile", json_encode(array('mobile'=>$mobile,'time'=>time()))));

            $redis->executeCommand($redis_cmd);


        }

        $coupon_amount = $this->get('auto_manager.coupon_helper')->send_activity_coupon($couponActivity,$member,
            $mobile);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'couponAmount' => $coupon_amount
        ]);



    }






    /**
     * @Route("/draw", methods="POST",name="auto_api_coupon_draw")
     */
    public function drawAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $code = $req->request->get('code');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }

//
//        if(strtolower($code)=='411314'){
//
//            $activity = $this->getDoctrine()
//                ->getRepository('AutoManagerBundle:CouponActivity')
//                ->find(1207);
//
//            if(empty($activity)){
//                return new JsonResponse([
//                    'errorCode'    =>  self::E_NO_COUPON_CODE,
//                    'errorMessage' =>  self::M_NO_COUPON_CODE
//                ]);
//            }
//
//            $coupon = $this->getDoctrine()
//                ->getRepository('AutoManagerBundle:Coupon')
//                ->findOneBy(['activity'=>$activity,'member'=>$member]);
//
//            if(!empty($coupon)){
//
//                return new JsonResponse([
//                    'errorCode'    =>  self::E_USED_COUPON_CODE,
//                    'errorMessage' =>  self::M_USED_COUPON_CODE
//                ]);
//            }
//
//            $this->get("auto_manager.coupon_helper")->send_activity_coupon($activity,$member);
//            return new JsonResponse([
//                'errorCode'    =>  self::E_OK,
//            ]);
//        }

        $coupon = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Coupon')
            ->findOneBy(['code'=>strtoupper($code)]);

        if(empty($coupon)){
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_COUPON_CODE,
                'errorMessage' =>  self::M_NO_COUPON_CODE
            ]);
        }

        if(!empty($coupon->getMember())){
            return new JsonResponse([
                'errorCode'    =>  self::E_USED_COUPON_CODE,
                'errorMessage' =>  self::M_USED_COUPON_CODE
            ]);
        }



        $date = (new \DateTime((new \DateTime('+'.$coupon->getKind()->getValidDay().' days'))->format('Y-m-d')));


        $coupon->setMember($member)
            ->setEndTime($date)
            ->setCreateTime(new \DateTime());

        $man = $this->getDoctrine()->getManager();
        $man->persist($coupon);
        $man->flush();

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
        ]);


    }

}
