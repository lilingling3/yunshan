<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/9/9
 * Time: 下午12:48
 */
namespace Auto\Bundle\Api2Bundle\Controller;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
 * @Route("/payment")
 */
class PaymentController extends BaseController {
    const PER_PAGE = 20;
    /**
     * @Route("/list", methods="POST")
     */
    public function PaymentListAction(Request $req)
    {
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:PaymentOrder')
                ->createQueryBuilder('p');

        $payment =
            new Paginator(
                $qb
                    ->select('p')
                    ->orderBy('p.id', 'DESC')
                    ->where($qb->expr()->eq('p.member', ':member'))
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        $payment_list =
            $qb
                ->select('p')
                ->orderBy('p.id', 'DESC')
                ->where($qb->expr()->eq('p.member', ':member'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult();
        ;

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'pageCount'   =>ceil($payment->count() / self::PER_PAGE),
            'page'        =>$page,
            'payments' => array_map($this->get('auto_manager.payment_helper')->get_payment_normalizer(),
                $payment_list),

        ]);


    }

    /**
     * @Route("/alipay/order", methods="POST")
     */
    public function alipayOrderAction(Request $req)
    {


        $check_member = $this->checkMember($this->getUser());

        if(!empty($member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();


        $payment_order_id = $req->request->getInt('orderID');

        $paymentOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:PaymentOrder')
            ->findOneBy(['id'=>$payment_order_id,'member'=>$member]);

        if(empty($paymentOrder)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        $pay_amount = $paymentOrder->getAmount();

        $sign_data = new AlipaySignData();
        $sign_data->SetPartner(AlipayConfig::PARTNER);
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");
        $sign_data->SetOutTradeNo(time().$payment_order_id);
        $sign_data->SetSubject('云杉智行缴费');
        $sign_data->SetBody("云杉智行");
        $sign_data->SetTotalFee($pay_amount);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_2_usepay_alipay_pay_notify"));
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



    /**
     * @Route("/wechat/order", methods="POST")
     */
    public function wechatOrderAction(Request $req){

        $order_id = $req->request->get('orderID');

        $member = $this->getUser();

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:PaymentOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);


        if($order->getPayTime()) {

            return new JsonResponse([
                'errorCode'  =>  self::E_ORDER_PAYED,
                'errorMessage'       =>  self::M_ORDER_PAYED
            ]);

        }

        $pay_amount = $order->getAmount();


        $tools = new JsApiPay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智行缴费");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        $input->SetTotal_fee($pay_amount*100);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_2_payment_wechat_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'payParameters'=>$payParameters,
        ]);

    }



    /**
     * @Route("/details", methods="POST")
     */
    public function detailsAction(Request $req)
    {
        $paymentId = $order_id = $req->request->get('paymentID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $paymentOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:PaymentOrder')
            ->findOneBy(['id'=>$paymentId,'member'=>$this->getUser()]);


        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'payment' => call_user_func($this->get('auto_manager.payment_helper')->get_payment_normalizer(),
                $paymentOrder),

        ]);



    }



}
