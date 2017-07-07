<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayRefund;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RefundRecord;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/refundRecord")
 */
class RefundRecordController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_refund_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $mobile = $req->query->get('mobile');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RefundRecord')
                ->createQueryBuilder('o')
        ;
        if($mobile){
            $qb
                ->join('o.member','m')
                ->andWhere($qb->expr()->eq('m.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);
        }
        $refundRecords =
            new Paginator(
                $qb
                    ->select('o')
                    ->orderBy('o.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );


        $total = ceil(count($refundRecords) / self::PER_PAGE);

//        $kinds = $this->get("auto_manager.global_helper")->get_payment_order_kind_arr();
        $refundAmounts = array();
        $refundStatus = array();
        foreach ($refundRecords as $v) {
            if(empty($v->getCheckTime())){
                $refundStatus[$v->getId()] = \Auto\Bundle\ManagerBundle\Entity\RefundRecord::REFUND_STATUS_FOR_CHECK;
            }else{
                if(!empty($v->getCheckFailedReason())){
                    $refundStatus[$v->getId()] = \Auto\Bundle\ManagerBundle\Entity\RefundRecord::REFUND_STATUS_CHECK_FAILED;
                }else{
                    $refundStatus[$v->getId()] = \Auto\Bundle\ManagerBundle\Entity\RefundRecord::REFUND_STATUS_REFUND_OK;
                    $rechargeOrders = $v->getRechargeOrders();
                    foreach($rechargeOrders as $rechargeOrder){
                        if(empty($rechargeOrder->getRefundTime())){
                            $refundStatus[$v->getId()] = \Auto\Bundle\ManagerBundle\Entity\RefundRecord::REFUND_STATUS_CHECK_OK;
                        }
                    }
                }
            }
            $refundAmounts[$v->getId()] = 0;

            $rechargeOrders = $v->getRechargeOrders();
            foreach ($rechargeOrders as $rechargeOrder) {
                $refundAmounts[$v->getId()] += $rechargeOrder->getActualRefundAmount();
            }

        }


        return ['lists'=>$refundRecords,'page'=>$page,'total'=>$total,
            'mobile'=>$mobile,'refundStatus'=>$refundStatus,'refundAmounts'=>$refundAmounts];
    }
    /**
     * @Route("/new", methods="GET", name="auto_admin_refund_record_new")
     * @Template()
     */
    public function newAction(Request $req)
    {
        $mobile = $req->query->get('mobile');
        $member = null;
        if($mobile){
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);
        }
        $isHasChecking = false;
        $isHasIllegalRecord = false;
        $isHasOrder = false;
        $isHasPaymentOrder = false;
        if(!empty($member)){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RefundRecord')
                    ->createQueryBuilder('o');

            $refundRecord = $qb->select('o')
                ->where($qb->expr()->isNull('o.checkTime'))
                ->andWhere($qb->expr()->eq('o.member', ':member'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult();

            if(!empty($refundRecord)){//用户有正在审核中的退款申请
                $isHasChecking = true;
            }else{
                //是否有未处理违章
                $illegalRecord = $this->get("auto_manager.member_helper")->get_undo_illegal_record($member);
                if(!empty($illegalRecord)){
                    $isHasIllegalRecord = true;
                }
                //是否有未付款订单
                $rentalOrder = $this->get("auto_manager.member_helper")->get_unpay_rental_order($member);
                if(!empty($rentalOrder)){
                    $isHasOrder = true;
                }
                //是否有未缴费记录
                $paymentOrder = $this->get("auto_manager.member_helper")->get_unpay_payment_order($member);
                if(!empty($paymentOrder)){
                    $isHasPaymentOrder = true;
                }
            }

        }

        return ['mobile'=>$mobile,'member'  => $member,
            'isHasChecking'=>$isHasChecking,'isHasIllegalRecord'=>$isHasIllegalRecord,'isHasOrder'=>$isHasOrder,'isHasPaymentOrder'=>$isHasPaymentOrder
        ];
    }

    /**
     * @Route("/create", methods="POST", name="auto_admin_refund_record_create")
     * @Template()
     */
    public function createAction(Request $req)
    {
        $refundRecord = new \Auto\Bundle\ManagerBundle\Entity\RefundRecord();

        $memberId = $req->request->getInt('memberId');
        $refundInstrustions = $req->request->get('refundInstrustions');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:member')
            ->find($memberId);

        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'非法操作']
            );
        }

        $refundRecord->setMember($member);
        $refundRecord->setRefundInstrustions($refundInstrustions);
//        dump($refundRecord);die;
        $man = $this->getDoctrine()->getManager();
        $man->persist($refundRecord);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_refund_record_list'));
    }

    /**
     * @Route("/check/{id}", methods="GET", name="auto_admin_refund_record_check",requirements={"id"="\d+"})
     * @Template()
     */
    public function checkAction($id)
    {
        $refundRecord = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RefundRecord')
            ->find($id);

        $isHasIllegalRecord = false;
        $isHasOrder = false;
        $isHasPaymentOrder = false;

        //是否有未处理违章
        $illegalRecord = $this->get("auto_manager.member_helper")->get_undo_illegal_record($refundRecord->getMember());
        if(!empty($illegalRecord)){
            $isHasIllegalRecord = true;
        }
        //是否有未付款订单
        $rentalOrder = $this->get("auto_manager.member_helper")->get_unpay_rental_order($refundRecord->getMember());
        if(!empty($rentalOrder)){
            $isHasOrder = true;
        }
        //是否有未缴费记录
        $paymentOrder = $this->get("auto_manager.member_helper")->get_unpay_payment_order($refundRecord->getMember());
        if(!empty($paymentOrder)){
            $isHasPaymentOrder = true;
        }

        return ['refundRecord'  => $refundRecord,'isHasIllegalRecord'=>$isHasIllegalRecord,'isHasOrder'=>$isHasOrder,'isHasPaymentOrder'=>$isHasPaymentOrder ];
    }

    /**
     * @Route("/checkFailed/{id}", methods="POST", name="auto_admin_refund_record_check_failed")
     * @Template()
     */
    public function checkFailedAction(Request $req,$id)
    {
        $refundRecord = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RefundRecord')
            ->find($id);

        $checkFailedReason = $req->request->get('checkFailedReason');
        if(empty($checkFailedReason)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'请输入审核失败原因']
            );
        }
        $refundRecord->setCheckTime(new \DateTime());
        $refundRecord->setCheckFailedReason($checkFailedReason);
        $man = $this->getDoctrine()->getManager();
        $man->persist($refundRecord);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_refund_record_list'));

    }

    /**
     * @Route("/checkOk/{id}", methods="POST", name="auto_admin_refund_record_check_ok")
     * @Template()
     */
    public function checkOkAction(Request $req,$id)
    {
        $refundRecord = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RefundRecord')
            ->find($id);
        $arrChannel = $req->request->get('channel');
        $arrTradeNo = $req->request->get('tradeNo');
        $arrActualRefundAmount = $req->request->get('actualRefundAmount');
        $arrRefundAmount = $req->request->get('refundAmount');

        foreach ($arrChannel as $key => $value) {
            if(empty($arrTradeNo[$key])){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请输入渠道流水号']
                );
            }
            if(empty($arrActualRefundAmount[$key])){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请输入退款金额']
                );
            }
            if(empty($arrRefundAmount[$key])){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请输入账户扣除金额']
                );
            }

            if($value == 'weixin'){
                $rechargeOrder = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOrder')
                    ->findOneBy(['member' => $refundRecord->getMember(),'wechatTransactionId' => $arrTradeNo[$key]]);
            }
            if($value == 'zhifubao'){
                $rechargeOrder = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOrder')
                    ->findOneBy(['member' => $refundRecord->getMember(),'alipayTradeNo' => $arrTradeNo[$key]]);
            }

            if(empty($rechargeOrder)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$arrTradeNo[$key].' 支付订单不存在！']
                );
            }

            if(!empty($rechargeOrder->getActualRefundAmount())|| !empty($rechargeOrder->getRefundAmount()) || !empty($rechargeOrder->getRefundTime())){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$arrTradeNo[$key].' 已产生退款，不可再次退款！']
                );
            }

            if($rechargeOrder->getAmount() < $arrRefundAmount[$key]){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$arrTradeNo[$key].' 账户扣除金额超额！']
                );
            }
            if($rechargeOrder->getactualAmount() < $arrActualRefundAmount[$key]){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$arrTradeNo[$key].' 退款金额超额！']
                );
            }



        }

        foreach ($arrChannel as $key => $value) {

            if($value == 'weixin'){
                $rechargeOrder = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOrder')
                    ->findOneBy(['member' => $refundRecord->getMember(),'wechatTransactionId' => $arrTradeNo[$key]]);
            }
            if($value == 'zhifubao'){
                $rechargeOrder = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOrder')
                    ->findOneBy(['member' => $refundRecord->getMember(),'alipayTradeNo' => $arrTradeNo[$key]]);
            }

            $rechargeOrder->setActualRefundAmount($arrActualRefundAmount[$key]);
            $rechargeOrder->setRefundAmount($arrRefundAmount[$key]);
            $man = $this->getDoctrine()->getManager();
            $man->persist($rechargeOrder);
            $man->flush();

            $refundRecord->addRechargeOrder($rechargeOrder);
        }
        $refundRecord->setCheckTime(new \DateTime());
        $man = $this->getDoctrine()->getManager();
        $man->persist($refundRecord);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_refund_record_list'));

    }

    /**
     * @Route("/refund/{id}", methods="GET", name="auto_admin_refund_record_refund",requirements={"id"="\d+"})
     * @Template()
     */
    public function refundAction($id)
    {
        $refundRecord = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RefundRecord')
            ->find($id);

        $rechargeOrders = $refundRecord->getRechargeOrders();

        $status = '审核成功';
        $all_count = count($rechargeOrders);
        $is_refund_count = 0;
        foreach ($rechargeOrders as $key => $value) {
            if(!empty($value->getRefundTime())){
                $is_refund_count = $is_refund_count+1;
            }
        }

        if($all_count == $is_refund_count){
            return $this->redirect($this->generateUrl('auto_admin_refund_record_show',['id'=>$id]));
        }

        $isHasIllegalRecord = false;
        $isHasOrder = false;
        $isHasPaymentOrder = false;

        //是否有未处理违章
        $illegalRecord = $this->get("auto_manager.member_helper")->get_undo_illegal_record($refundRecord->getMember());
        if(!empty($illegalRecord)){
            $isHasIllegalRecord = true;
        }
        //是否有未付款订单
        $rentalOrder = $this->get("auto_manager.member_helper")->get_unpay_rental_order($refundRecord->getMember());
        if(!empty($rentalOrder)){
            $isHasOrder = true;
        }
        //是否有未缴费记录
        $paymentOrder = $this->get("auto_manager.member_helper")->get_unpay_payment_order($refundRecord->getMember());
        if(!empty($paymentOrder)){
            $isHasPaymentOrder = true;
        }

        return [
            'refundRecord'  => $refundRecord,'isHasIllegalRecord'=>$isHasIllegalRecord,'isHasOrder'=>$isHasOrder,'isHasPaymentOrder'=>$isHasPaymentOrder,
            'rechargeOrders'=>$rechargeOrders,'status'=>$status
        ];
    }

    /**
     * @Route("/refundDo/{id}", methods="GET", name="auto_admin_refund_record_refund_do",requirements={"id"="\d+"})
     * @Template()
     */
    public function refundDoAction(Request $req,$id)
    {
        $refundRecord = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RefundRecord')
            ->find($id);

        $rechargeOrderId = $req->query->get('oId');
        $rechargeOrders = $refundRecord->getRechargeOrders();

        $rechargeOrder = null;
        foreach ($rechargeOrders as $value) {
            if($value->getId() == $rechargeOrderId){
                $rechargeOrder = $value;
            }
        }

        if(!$rechargeOrder){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'非法操作！']
            );
        }

        if(!empty($rechargeOrder->getRefundTime())){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'此订单已经退款！']
            );
        }

        $member = $rechargeOrder->getMember();
        if($member->getWallet() < $rechargeOrder->getRefundAmount()){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'用户余额不足，无法退款']
            );
        }


//        dump($rechargeOrder);die;
        if($rechargeOrder->getWechatTransactionId()){//微信退款
            $input = new WxPayRefund();

            $input->SetTransaction_id($rechargeOrder->getWechatTransactionId());
            $input->SetOut_refund_no(time().$rechargeOrder->getId());
            $input->SetTotal_fee(intval($rechargeOrder->getActualAmount()*100));
            $input->SetRefund_fee(intval($rechargeOrder->getActualRefundAmount()*100));
            $input->SetOp_user_id(WxPayConfig::MCHID);

            $result = WxPayApi::refund($input);
            if($result['return_code'] != 'SUCCESS'){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$result['return_code'].$result['return_msg']]
                );
            }

            if($result['result_code'] != 'SUCCESS'){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$result['err_code'].$result['err_code_des']]
                );
            }
            $wechatRefundId = $result['refund_id'];

            $rechargeOrder->setWechatRefundId($wechatRefundId);
            $rechargeOrder->setRefundTime(new \DateTime());


            $member->setWallet($member->getWallet() - $rechargeOrder->getRefundAmount());

            $man = $this->getDoctrine()->getManager();
            $man->persist($rechargeOrder);
            $man->persist($member);
            $man->flush();

        }elseif($rechargeOrder->getAlipayTradeNo()){//支付宝退款
            $aliPaySdkHome = dirname(dirname(dirname(__FILE__)))
                . DIRECTORY_SEPARATOR . "ManagerBundle"
                . DIRECTORY_SEPARATOR . "Payment"
                . DIRECTORY_SEPARATOR . "AliPaySdk" . DIRECTORY_SEPARATOR;
//            /www/lecar/src/Auto/Bundle/ManagerBundle/Payment/AliPaySdk/AopSdk.php
//            echo $aliPaySdkHome;die;
            include($aliPaySdkHome . "AopSdk.php");
            $aop = new \AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = $this->getParameter("alipay_appid");
            $aop->rsaPrivateKeyFilePath = $this->getParameter("rsa_private_key");
            $aop->alipayPublicKey=$this->getParameter("alipay_public_key");
            $aop->apiVersion = '1.0';
            $aop->postCharset='UTF-8';
            $aop->format='json';
            $request = new \AlipayTradeRefundRequest ();
//            dump();die;
            $request->setBizContent("{" .
//                "    \"out_trade_no\":\"20150320010101001\"," .
                "    \"trade_no\":\"".$rechargeOrder->getAlipayTradeNo()."\"," .
                "    \"refund_amount\":\"".$rechargeOrder->getActualRefundAmount() ."\",".
                "    \"refund_reason\":\"正常退款\"," .
                "    \"out_request_no\":\"HZ01RF".$rechargeOrder->getId()."\"," .
//                "    \"operator_id\":\"OP001\"," .
//                "    \"store_id\":\"NJ_S_001\"," .
                "    \"terminal_id\":\"ADMIN\"" .
                "  }");
            $result = $aop->execute ( $request);
            //返回示例
//            "{
//                "alipay_trade_refund_response":
//                    {
//                        "code":"10000","msg":"Success",
//                        "buyer_logon_id":"awe***@163.com",
//                        "buyer_user_id":"2088802881469460",
//                        "fund_change":"Y",
//                        "gmt_refund_pay":"2016-10-13 15:04:24",
//                        "open_id":"20880010300238619641162781417046",
//                        "out_trade_no":"14761760283074",
//                        "refund_fee":"100.00",
//                        "send_back_fee":"0.00",
//                        "trade_no":"2016101121001004460213124311"
//                    },
//                "sign":"P9CAhI18EV9Z1cHiAdFyy5zDn/+AJTZtAyVHRg6HQMFzfE3M77HLF7czqmHywtSgmQCm8uI2yrTvfxLtzvEO8tfsuFf/iYBnLn3xYZhdunKHqker14zc6aKOc1qZujg33GWk1As/uUy68UXTv7JuCnGT73IBqNQfMhKjb32GABs="
//           }"

            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
//                echo "成功";
                $alipayRefundNo = $result->$responseNode->out_trade_no;

                $rechargeOrder->setAlipayRefundNo($alipayRefundNo);
                $rechargeOrder->setRefundTime(new \DateTime());

                $member->setWallet($member->getWallet() - $rechargeOrder->getRefundAmount());

                $man = $this->getDoctrine()->getManager();
                $man->persist($rechargeOrder);
                $man->persist($member);
                $man->flush();
            } else {
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>$result->$responseNode->msg.$result->$responseNode->sub_msg]
                );
            }

        }


        return $this->redirect($this->generateUrl('auto_admin_refund_record_refund',['id'=>$id]));
    }



    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_refund_record_show",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {
        $refundRecord = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RefundRecord')
            ->find($id);

        $rechargeOrders = $refundRecord->getRechargeOrders();


        $status = '';
        if($refundRecord->getCheckFailedReason()){
            $status = '审核失败';
        }else{
            $status = '退款成功';
        }

        return [
            'refundRecord'  => $refundRecord,
            'rechargeOrders' => $rechargeOrders, 'rechargeOrderCount'=>count($rechargeOrders),'status'=>$status
        ];
    }


}