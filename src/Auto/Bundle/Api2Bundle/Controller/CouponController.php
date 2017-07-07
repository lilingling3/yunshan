<?php
namespace Auto\Bundle\Api2Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/coupon")
 */
class CouponController extends BaseController
{
    const PER_PAGE = 20;

    /**
     * @Route("/list", methods="POST")
     */
    public function listAction(Request $req)
    {

        $order_id = $req->request->get('orderID');

        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        if($order_id){

            /**
             * @var $member \Auto\Bundle\ManagerBundle\Entity\RentalOrder
             */

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->find($order_id);

            $check_order = $this->checkRentalOrder($order);

            if(!empty($check_order)){

                return new JsonResponse($check_order);

            }

            if(!$order->getEndTime()){

                return new JsonResponse([
                    'errorCode' =>  self::E_NOT_END_ORDER,
                    'errorMessage' =>  self::M_NOT_END_ORDER,
                ]);

            }
            $offsetHour = floor(($order->getEndTime()->getTimeStamp()-$order->getCreateTime()->getTimeStamp())/3600);
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
                    ->andWhere($qb->expr()->isNull('c.useTime'))
                    ->setParameter('member', $this->getUser())
                    ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
                    ->getQuery()
                    ->getResult();
            ;



//            $coupons =
//
//                $qb
//                    ->select('c')
//                    ->join('c.kind','k')
//                    ->orderBy('k.amount','DESC')
//                    ->addOrderBy('c.endTime', 'ASC')
//                    ->where($qb->expr()->eq('c.member', ':member'))
//                    ->orWhere($qb->expr()->eq('c.mobile', ':mobile'))
//                    ->andWhere($qb->expr()->gte('c.endTime',':endTime'))
//                    ->andWhere($qb->expr()->isNull('c.useTime'))
////                    ->setParameter('member', $this->getUser())
//                    ->setParameter('mobile', $this->getUser()->getMobile())
//                    ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
//                    ->getQuery()
//                    ->getResult();
//            ;


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
                'coupons'    =>  $coupons
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
                ->setParameter('member', $this->getUser())
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
     * @Route("/usable/list", methods="POST",name="auto_api_2_coupon_usable_list")
     */
    public function usableListAction(Request $req)
    {

        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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
            ->setParameter('member', $this->getUser())
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
     * @Route("/unusable/list", methods="POST",name="auto_api_2_coupon_unusable_list")
     */
    public function unusableListAction(Request $req)
    {

        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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
            ->setParameter('member', $this->getUser())
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
     * @Route("/appShare", methods="POST",name="auto_api_2_coupon_app_share")
     */
    public function appShareAction(Request $req)
    {

        $uid = $req->request->get('userID');

        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'title' =>'云杉智行，邀您加入绿色出行！',
            'content'=>'新用户可领取30元活动优惠券！',
            'link' => $base_url.$this->generateUrl('auto_wap_coupon_share_app',['aid'=>3290,'uid'=>$uid]),
            'logo' =>'http://lecarx.com/bundles/autowap/images/tou1.png'
        ]);

    }


    /**
     * @Route("/orderShare", methods="POST",name="auto_api_2_coupon_order_share")
     */
    public function orderShareAction(Request $req)
    {
        $order_id = $req->request->get('orderID');

        $member=$this->getUser();

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

        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        if(!empty($activity)){
            return new JsonResponse([
                'errorCode'  =>  self::E_OK,
                'title' =>'云杉智行，邀您加入绿色出行！',
                'content'=>'使用云杉智行得出行优惠券！',
                'link' => $base_url.$this->generateUrl('auto_wap_coupon_share_order',['aid'=>$activity->getId(),'oid'=>$order_id]),
                'logo' =>'http://lecarx.com/bundles/autowap/images/tou1.png'
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
            'link' =>  $base_url = $this->get("auto_manager.curl_helper")->base_url().$this->generateUrl('auto_wap_coupon_share_order',['aid'=>$couponActivity->getId(),'oid'=>$order_id]),
            'logo' =>'http://lecarx.com/bundles/autowap/images/tou1.png'

        ]);

    }




    /**
     * @Route("/draw", methods="POST",name="auto_api_2_coupon_draw")
     */
    public function drawAction(Request $req)
    {
        $code = $req->request->get('code');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }


        if(strtolower($code)=='411314'){

            $activity = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:CouponActivity')
                ->find(1207);

            if(empty($activity)){
                return new JsonResponse([
                    'errorCode'    =>  self::E_NO_COUPON_CODE,
                    'errorMessage' =>  self::M_NO_COUPON_CODE
                ]);
            }

            $coupon = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->findOneBy(['activity'=>$activity,'member'=>$this->getUser()]);

            if(!empty($coupon)){

                return new JsonResponse([
                    'errorCode'    =>  self::E_USED_COUPON_CODE,
                    'errorMessage' =>  self::M_USED_COUPON_CODE
                ]);
            }

            $this->get("auto_manager.coupon_helper")->send_activity_coupon($activity,$this->getUser());
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
            ]);
        }

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


        $coupon->setMember($this->getUser())
            ->setEndTime($date)
            ->setCreateTime(new \DateTime());

        $man = $this->getDoctrine()->getManager();
        $man->persist($coupon);
        $man->flush();

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'coupon'       =>  call_user_func($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(), $coupon),
        ]);


    }

}
