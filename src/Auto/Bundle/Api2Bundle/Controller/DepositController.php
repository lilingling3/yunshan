<?php
/**
 * Created by PhpStorm.
 * User: luyao
 * Date: 16/12/17
 * Time: 下午1:18
 */

namespace Auto\Bundle\Api2Bundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Message;

use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayConfig;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayUnifiedOrder;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\PayNotifyCallBack;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayResults;

/**
 * @Route("/deposit")
 */
class DepositController extends BaseController {

    const PER_PAGE = 5;

    /**
     * @Route("/list", methods="POST",name="auto_api2_deposit_list")
     */

    public function listAction(Request $req){

        $uid  = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        // 用户注册

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d');

        $deposit =
            new Paginator(
                $qb
                    ->select('d')
                    ->orderBy('d.payTime', 'DESC')
                    ->where($qb->expr()->eq('d.member', ':member'))
                    ->andwhere($qb->expr()->isNotNull('d.payTime'))
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $deposit_order = array_map($this->get('auto_manager.deposit_helper')->get_deposit_normalizer(), 
                            $deposit->getIterator()->getArrayCopy());

        $order = $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Deposit')
                    ->findOneBy(["member" => $member->getId()]);

        if (empty($order)) {
            $order = new \Auto\Bundle\ManagerBundle\Entity\Deposit;
            $order->setMember($member);
            $order->setTotal(0);
            $order->setStatus(301);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
        }

        $total = call_user_func($this->get('auto_manager.deposit_helper')->get_deposit_info_normalizer(), $order);

        return new JsonResponse([
            'errorCode'   => self::E_OK,
            'pageCount'   => ceil($deposit->count() / self::PER_PAGE),
            'page'        => $page,
            'orderCount'  => $deposit->count(),
            'deposit'     => $deposit_order,
            'total'       => $total
        ]);

    }




    /**
     * @Route("/order", methods="POST",name="auto_api2_deposit_order")
     */
    public function orderAction(Request $req){

        $uid  = $req->request->get('userID');
        
        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

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

        // 创建订单
        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Deposit')
                ->createQueryBuilder('d')
        ;

        $deposit_info = 
            $qb
                ->select('d')
                ->where($qb->expr()->eq('d.member',':member'))
                ->setParameter('member', $member)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (empty($deposit_info)) {
            

            $deposit = new \Auto\Bundle\ManagerBundle\Entity\Deposit;
            $deposit->setMember($member);
            $deposit->setTotal(0);
            $deposit->setStatus(301);

            $man = $this->getDoctrine()->getManager();
            $man->persist($deposit);
            $man->flush();
        } else {

            $detail = call_user_func($this->get('auto_manager.deposit_helper')->get_deposit_info_normalizer()
                ,$deposit_info);

            if ($detail['status'] == 399) {

                // 已经付款了，不需要付款了
                return new JsonResponse([

                    'errorCode'   => self::E_HAS_PAY_DEPOSIT,
                    'message'     => self::M_HAS_PAY_DEPOSIT,

                ]);
            }
        }

        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
        ;

        $deposit_order = 
            $qb
                ->select('d')
                ->orderby('d.createTime','DESC')
                ->where($qb->expr()->eq('d.member',':member'))
                ->andwhere($qb->expr()->isNull('d.payTime'))
                ->setParameter('member', $member)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (empty($deposit_order)) {
            $order = new \Auto\Bundle\ManagerBundle\Entity\DepositOrder;
            $order->setMember($member);
            $order->setAmount(1);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
        }
        

        return new JsonResponse([
            'errorCode'   => self::E_OK,
            'order'       => call_user_func($this->get('auto_manager.deposit_helper')->get_deposit_normalizer()
                , empty($deposit_order) ? $order: $deposit_order),
        ]);
    }

    /**
     * @Route("/refund", methods="POST",name="auto_api2_deposit_refund")
     */
    public function refundAction(Request $req){

        //订单中 + refundtime

        $uid  = $req->request->get('userID');

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

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


        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
        ;

        $depositOrder = 
            $qb
                ->select('d')
                ->orderby('d.createTime','DESC')
                ->where($qb->expr()->eq('d.member',':member'))
                ->andwhere($qb->expr()->isNotNull('d.payTime'))
                ->andwhere($qb->expr()->isNull('d.refundTime'))
                ->setParameter('member', $member)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (empty($depositOrder)) {

            return new JsonResponse([
                'errorCode' =>  self::E_NO_DEPOSIT_ORDER,
                'errorMessage' =>self::M_NO_DEPOSIT_ORDER
            ]);
        }

        $depositOrder->setRefundTime( new \DateTime());
        $depositOrder->setRefundAmount($depositOrder->getAmount());


        $man = $this->getDoctrine()->getManager();
        $man->persist($depositOrder);
        $man->flush();


        $deposit = 
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Deposit')
                    ->findOneBy(["member" => $depositOrder->getMember()->getId()]);

        if (empty($deposit)) {

            return new JsonResponse([
                'errorCode' =>  self::E_NO_DEPOSIT_ORDER,
                'errorMessage' =>self::M_NO_DEPOSIT_ORDER
            ]);
        }

        $deposit->setTotal( $deposit->getTotal() - $depositOrder->getAmount() );
        $deposit->setStatus(302);

        $man->persist($deposit);
        $man->flush();

        return new JsonResponse([
            'errorCode'   => self::E_OK,
            // 'userID'      => $uid
        ]);

    }
}