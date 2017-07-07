<?php
/**
 * Created by PhpStorm.
 * User: luyao
 * Date: 16/12/17
 * Time: 下午1:18
 */

namespace Auto\Bundle\ApiBundle\Controller;
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
     * @Route("/list", methods="POST",name="auto_api_deposit_list")
     */

    public function listAction(Request $req){

        $uid  = $req->request->get('userID');
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
                ->createQueryBuilder('d');

        $deposit =
            new Paginator(
                $qb
                    ->select('d')
                    ->orderBy('d.id', 'DESC')
                    ->where($qb->expr()->eq('d.member', ':member'))
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

        $total = call_user_func($this->get('auto_manager.deposit_helper')->get_deposit_info_normalizer(), 
                    $order);

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
     * @Route("/order", methods="POST",name="auto_api_deposit_order")
     */
    public function orderAction(Request $req){

        $uid  = $req->request->get('userID');
        
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
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
        ]);
    }


    /**
     * @Route("/wechat/notify.html", methods="POST",name="auto_api_deposit_wechat_pay_notify",)
     */
    public function wechatNotifyAction()
    {

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("h5 pay begin:".date('Ymdhis'));

        $notify = new PayNotifyCallBack();
        $notify->Handle(false);

        if($notify->GetReturn_code()){

            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            //如果返回成功则验证签名
            $result = WxPayResults::Init($xml);

            $orderId = substr($result['out_trade_no'],14);

            $this->get("auto_manager.logs_helper")->addWeChatPayLog($result['out_trade_no']);
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->find($orderId);

            $this->get("auto_manager.logs_helper")->addWeChatPayLog("订单号:".$orderId);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }

            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $order->setAmount($result['total_fee']/100);
            
            $this->get("auto_manager.logs_helper")->addWeChatPayLog("5");


            $deposit = 
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Deposit')
                    ->findOneBy(["member" => $order->getMember()->getId()]);
                    // ->find($order->getMember()->getId());

            $this->get("auto_manager.logs_helper")->addWeChatPayLog($order->getMember()->getId());

            if (empty($deposit)) {
                echo "deposit fail";exit;
            }

            $this->get("auto_manager.logs_helper")->addWeChatPayLog("6");
            $deposit->setStatus(399);

            $amount = $deposit->getTotal() ? $deposit->getTotal() + $result['total_fee']/100 : $result['total_fee']/100;
            $deposit->setTotal($amount);

            $this->get("auto_manager.logs_helper")->addWeChatPayLog("7");
            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $man->persist($deposit);
            $man->flush();
            $this->get("auto_manager.logs_helper")->addWeChatPayLog("8");
            $this->get("auto_manager.logs_helper")->addWeChatPayLog($result['out_trade_no']."--"
                .($result['total_fee']/100)."--".$result['trade_type'])."--".$result['transaction_id'];
        }

        exit;

    }

    /**
     * @Route("/refund", methods="POST",name="auto_api_deposit_refund")
     */
    public function refundAction(Request $req){

        //订单中 + refundtime

        $uid  = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
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