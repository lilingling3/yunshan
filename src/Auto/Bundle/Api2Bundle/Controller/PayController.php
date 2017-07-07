<?php
namespace Auto\Bundle\Api2Bundle\Controller;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipayCore;
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipayRSA;
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipayConfig;
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipayNotify;
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipaySignData;

use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayConfig;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayUnifiedOrder;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\PayNotifyCallBack;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayResults;

/**
 * @Route("/pay")
 */

class PayController extends BaseController {


    /**
     * @Route("/alipay/order", methods="POST")
     */
    public function alipayOrderAction(Request $req)
    {

        $order_id = $req->request->getInt('orderID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){


            return new JsonResponse($check_member);


        }

        $member = $this->getUser();

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        if($order->getPayTime()) {

            return new JsonResponse([
                'errorCode'  =>  self::E_ORDER_PAYED,
                'errorMessage'       =>  self::M_ORDER_PAYED
            ]);

        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $order);

        $coupon_amount = empty($rentalOrder['coupon'])?0:$rentalOrder['coupon']['amount'];

        $pay_amount = $rentalOrder['costDetail']['cost'] - $coupon_amount -$rentalOrder['walletAmount'];

        $pay_amount = $pay_amount >0?round($pay_amount, 2):0;

        $sign_data = new AlipaySignData();
        $sign_data->SetPartner(AlipayConfig::PARTNER);
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");
        $sign_data->SetOutTradeNo(date("YmdHis").$order_id);
        $sign_data->SetSubject('云杉智行租车费用');
        $sign_data->SetBody("车辆分时租赁服务");
        $sign_data->SetTotalFee($pay_amount);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_2_order_alipay_pay_notify"));
        $sign_data->SetService("mobile.securitypay.pay");
        $sign_data->SetPaymentType("1");
        $sign_data->SetInputCharset("utf-8");



        $alipay_core = new AlipayCore();

        $parameters = $alipay_core->createLinkstring($sign_data->GetValues());

        $alipayRSA = new AlipayRSA();
        $sign = $alipayRSA->rsaSign($parameters,$this->container->getParameter("rsa_private_key"));
        $sign = urlencode($sign);

//        $sign_data->setSign($sign);
//        $sign_data->SetSignType("RSA");

//       $a = $alipayRSA->rsaVerify($parameters,$this->container->getParameter("alipay_public_key"),$sign);

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'sign'       =>  $sign,
            'signType'   => "RSA",
            'alipayString' =>$parameters
        ]);

    }

    /**
     * @Route("/wechat/order", methods="POST")
     */
    public function wechatOrderAction(Request $req){

        $order_id = $req->request->get('orderID');

        $member = $this->getUser();

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);



        if($order->getPayTime()) {

            return new JsonResponse([
                'errorCode'  =>  self::E_ORDER_PAYED,
                'errorMessage'       =>  self::M_ORDER_PAYED
            ]);

        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $order);

        $coupon_amount = empty($rentalOrder['coupon'])?0:$rentalOrder['coupon']['amount'];

        $pay_amount = $rentalOrder['costDetail']['cost'] - $coupon_amount -$rentalOrder['walletAmount'];

        $pay_amount = $pay_amount >0?round($pay_amount, 2):0;

        $tools = new JsApiPay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智行租车费用");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        $input->SetTotal_fee($pay_amount*100);

        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_2_order_wechat_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'payParameters'=>$payParameters,
        ]);

    }


    /**
     * @Route("/free/order", methods="POST")
     */
    public function freeOrderAction(Request $req)
    {

        $order_id = $req->request->get('orderID');


        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$this->getUser()]);


        $check_rental_order = $this->checkRentalOrder($order);

        if(!empty($check_rental_order)){

            return new JsonResponse($check_rental_order);

        }

        if ($order->getPayTime()){
            return new JsonResponse([
                    'errorCode'    =>  self::E_ORDER_PAYED,
                    'errorMessage'    =>  self::M_ORDER_PAYED,
                ]
            );

        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_data_normalizer(),
            $order);

        $coupon_amount = empty($rentalOrder['coupon']) ? 0 : $rentalOrder['coupon']['amount'];

        // $pay_amount = (intval($rentalOrder['costDetail']['cost']*100) - $coupon_amount*100 - intval($order->getWalletAmount()*100))/100;
        $pay_amount = round($rentalOrder['costDetail']['cost'] - $order->getWalletAmount() - $coupon_amount, 2);

        // $this->get("auto_manager.logs_helper")->addWeChatPayLog("==1|".round($rentalOrder['costDetail']['cost'] - $order->getWalletAmount() - $coupon_amount, 2));
        $this->get("auto_manager.logs_helper")->addWeChatPayLog("==2|".$pay_amount);
        
        // if($pay_amount <= 0) {
        if($pay_amount <= 0.01) {

            $order->setPayTime(new \DateTime());
            $order->setAmount(0);
            $coupon = $order->getCoupon();
            if(!empty($coupon)){
                $order->getCoupon()->setUseTime(new \DateTime());
            }

            if($order->getWalletAmount()>0){
                $order->getMember()->setWallet($order->getMember()->getWallet()-$order->getWalletAmount());
                $wallet = new \Auto\Bundle\ManagerBundle\Entity\WalletRecord();
                $wallet->setAmount($order->getWalletAmount())
                    ->setRentalOrder($order);

                $man = $this->getDoctrine()->getManager();
                $man->persist($wallet);
                $man->flush();
                
                // 余额消耗记录
                $operate = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOperate')
                    ->find(3);

                $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
                $record->setCreateTime(new \DateTime());
                $record->setMember($order->getMember());
                $record->setOperate($operate);
                $record->setWalletAmount($order->getMember()->getWallet());
                $record->setAmount($order->getWalletAmount());
                $record->setRemark($operate->getName());
                
                $man->persist($record);
                $man->flush();

            }

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
            
            // 清明优惠券
            // $this->get("auto_manager.coupon_helper")->coupon_activity_entrance($order);
            $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                ('auto_api_coupon_activity'), ['OrderID'=>$order->getId()]);
            
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
            ]);
        }else{

            return new JsonResponse([
                'errorCode'    =>  self::E_FREE_ORDER_PAY_AMOUNT,
                'errorMessage'    =>  self::M_FREE_ORDER_PAY_AMOUNT,
            ]);
        }
    }


    /**
     * @Route("/wechat/deposit", methods="POST")
     */
    public function wechatDepositAction(Request $req) {

        $order_id = $req->request->get('orderID');

        if(!empty($check_member)){

            return new JsonResponse($check_member);
        }

        $member = $this->getUser();

        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
        ;

        $order = 
            $qb
                ->select('d')
                ->orderby('d.createTime','DESC')
                ->where($qb->expr()->eq('d.member',':member'))
                ->setParameter('member', $this->getUser())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (empty($order)) {
            
            return new JsonResponse([
                'errorCode'     =>  self::E_NO_ORDER,
                'errorMessage'  =>  self::M_NO_ORDER
            ]);

        }

        
        if ($order->getPayTime()) {
            return new JsonResponse([
                'errorCode'     =>  self::E_ORDER_PAYED,
                'errorMessage'  =>  self::M_ORDER_PAYED
            ]);
        }

        // $coupon_amount = empty($rentalOrder['coupon'])?0:$rentalOrder['coupon']['amount'];

        // $pay_amount = $rentalOrder['costDetail']['cost'] - $coupon_amount -$rentalOrder['walletAmount'];

        // $pay_amount = $pay_amount >0?$pay_amount:0;

        $tools = new JsApiPay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智行租车费用");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        // $input->SetTotal_fee($pay_amount*100);
        $input->SetTotal_fee(500*100);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_2_deposit_wechat_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'     => self::E_OK,
            'payParameters' => $payParameters,
        ]);
    }



    /**
     * @Route("/alipay/deposit", methods="POST")
     */
    public function alipayDepositAction(Request $req)
    {

        $order_id = $req->request->getInt('orderID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);
        }

        $member = $this->getUser();

        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
        ;

        $order = 
            $qb
                ->select('d')
                ->orderby('d.createTime','DESC')
                ->where($qb->expr()->eq('d.member',':member'))
                ->setParameter('member', $this->getUser())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (empty($order)) {
            
            return new JsonResponse([
                'errorCode'     =>  self::E_NO_ORDER,
                'errorMessage'  =>  self::M_NO_ORDER
            ]);

        }

        
        if ($order->getPayTime()) {
            return new JsonResponse([
                'errorCode'     =>  self::E_ORDER_PAYED,
                'errorMessage'  =>  self::M_ORDER_PAYED
            ]);
        }

        $sign_data = new AlipaySignData();
        $sign_data->SetPartner(AlipayConfig::PARTNER);
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");
        $sign_data->SetOutTradeNo(date("YmdHis").$order_id);
        $sign_data->SetSubject('云杉智行租车费用');
        $sign_data->SetBody("车辆分时租赁服务");
        // $sign_data->SetTotalFee($pay_amount);
        $sign_data->SetTotalFee(500);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_2_deposit_alipay_pay_notify"));
        $sign_data->SetService("mobile.securitypay.pay");
        $sign_data->SetPaymentType("1");
        $sign_data->SetInputCharset("utf-8");



        $alipay_core = new AlipayCore();

        $parameters = $alipay_core->createLinkstring($sign_data->GetValues());

        $alipayRSA = new AlipayRSA();
        $sign = $alipayRSA->rsaSign($parameters,$this->container->getParameter("rsa_private_key"));
        $sign = urlencode($sign);

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'sign'       =>  $sign,
            'signType'   => "RSA",
            'alipayString' =>$parameters
        ]);

    }

}
