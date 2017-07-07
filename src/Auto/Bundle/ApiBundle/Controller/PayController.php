<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/11
 * Time: 下午3:57
 */

namespace Auto\Bundle\ApiBundle\Controller;
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
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayResults;

/**
 * @Route("/pay")
 */

class PayController extends BaseController
{

    //首充活动是否开启
    const FIRST_RECHARGE = '1STRECHARGE';
    //设置首充活动开始时间
    const FIRST_RECHARGE_START_TIME = '2017-05-01 09:00:00';
    //设置首充活动结束时间
    const FIRST_RECHARGE_END_TIME = '2017-05-02 00:00:00';
    //首充活动追加金额
    const FIRST_RECHARGE_ADD_AMOUNT = 100;


    /**
     * @Route("/wechat/deposit", methods="POST")
     */
    public function wechatDepositAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
            ('auto_api_deposit_order'),
            ['userID' =>$uid]);
        $data = json_decode($post_json, true);

        if ($data['errorCode'] != 0) {

            return new JsonResponse([
                'errorCode' => $data['errorCode'],
                'errorMessage' => $data['errorMessage']
            ]);

        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d');

        $deposit_info =
            $qb
                ->select('d')
                ->orderby('d.createTime', 'DESC')
                ->where($qb->expr()->eq('d.member', ':member'))
                ->setParameter('member', $this->getUser())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

        if (empty($deposit_info)) {

            return new JsonResponse([
                'errorCode' => self::E_NO_DEPOSIT_ORDER,
                'errorMessage' => self::M_NO_DEPOSIT_ORDER
            ]);
        }

        $info = call_user_func($this->get('auto_manager.deposit_helper')->get_deposit_normalizer()
            , $deposit_info);

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("------" . $deposit_info->getId());
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智慧-押金费用");
        $input->SetAttach($deposit_info->getMember()->getId());
        $input->SetOut_trade_no(date("YmdHis") . $deposit_info->getId());
        // $input->SetTotal_fee(1);
        $input->SetTotal_fee(500*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('车辆分时租赁服务');
        $input->SetNotify_url($req->server->get('HTTP_HOST') . $this->generateUrl("auto_api_deposit_wechat_pay_notify"));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $wechat_order = WxPayApi::unifiedOrder($input);
        $jsApiParametersArr = $tools->GetJsApiParameters($wechat_order);


        $editAddress = $tools->GetEditAddressParameters();

        // 是否已经缴纳押金
        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'editAddress'=>$editAddress,
            'jsApiParametersArr'=>$jsApiParametersArr,'order'=>$info
        ]);
    }




    /**
     * @Route("/alipay/order", methods="POST")
     */
    public function alipayOrderAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->getInt('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);


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

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);

        $coupon_amount = empty($rentalOrder['coupon'])?0:$rentalOrder['coupon']['amount'];

        $pay_amount = $rentalOrder['costDetail']['cost'] - $coupon_amount -$rentalOrder['walletAmount'];

        $pay_amount = $pay_amount >0?round($pay_amount, 2):0;

        $sign_data = new AlipaySignData();
        $sign_data->SetPartner(AlipayConfig::PARTNER);
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");
        $sign_data->SetOutTradeNo(date("YmdHis").$order_id);
        $sign_data->SetSubject('云杉智行');
        $sign_data->SetBody("车辆分时租赁服务");
        $sign_data->SetTotalFee($pay_amount);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_alipay_pay_notify"));
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
     * @Route("/alipay/notify.html", methods="POST",name="auto_api_alipay_pay_notify")
     */
    public function alipayNotifyAction(Request $req)
    {
        //计算得出通知验证结果


        if($_POST['trade_status'] == 'TRADE_SUCCESS'){


            $order_id = substr($_POST['out_trade_no'],14);

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->find($order_id);

            if($order->getPayTime()||empty($order)){
                echo "fail";
            }
            $order->setPayTime(new \DateTime());
            $order->setAlipayTradeNo($trade_no);
            $order->setAmount($_POST['total_fee']);

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

            echo "success";
        }

        exit;

    }



    /**
     * @Route("/free/order", methods="POST")
     */
    public function freeOrderAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);


        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);



        if (empty($order)) {
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_ORDER,
                'errorMessage'    =>  self::M_NO_ORDER,
            ]);
        }

        if ($order->getPayTime()){
            return new JsonResponse([
                'errorCode'    =>  self::E_ORDER_PAYED,
                    'errorMessage'    =>  self::M_ORDER_PAYED,
                ]
            );

        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);

        $coupon_amount = empty($rentalOrder['coupon']) ? 0 : $rentalOrder['coupon']['amount'];
        // $pay_amount = (intval($rentalOrder['costDetail']['cost']*100) - $coupon_amount*100 - intval($order->getWalletAmount()*100))/100;
        
        $pay_amount = round($rentalOrder['costDetail']['cost'] - $order->getWalletAmount() - $coupon_amount, 2); 
        
        if($pay_amount<=0.01){

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
     * @Route("/wechat/order", methods="POST")
     */
    public function wechatOrderAction(Request $req){

        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);



        if($order->getPayTime()) {

            return new JsonResponse([
                'errorCode'  =>  self::E_ORDER_PAYED,
                'errorMessage'       =>  self::M_ORDER_PAYED
            ]);

        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);

        $coupon_amount = empty($rentalOrder['coupon'])?0:$rentalOrder['coupon']['amount'];

        $pay_amount = $rentalOrder['costDetail']['cost'] - $coupon_amount -$rentalOrder['walletAmount'];

        $pay_amount = $pay_amount >0?round($pay_amount, 2):0;

        $tools = new JsApiPay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("车辆分时租赁服务");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        $input->SetTotal_fee($pay_amount*100);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_wechat_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'payParameters'=>$payParameters,
        ]);

    }


    /**
     * @Route("/wechat/notify.html", methods="POST",name="auto_api_wechat_pay_notify",)
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

            /**
             * @var $order \Auto\Bundle\ManagerBundle\Entity\RentalOrder
             */

            $orderId = substr($result['out_trade_no'],14);
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->find($orderId);

            $this->get("auto_manager.logs_helper")->addWeChatPayLog("订单号:".$orderId);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }

            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $order->setAmount($result['total_fee']/100);
            $coupon = $order->getCoupon();
            if(!empty($coupon)){
                $order->getCoupon()->setUseTime(new \DateTime());
            }
            $this->get("auto_manager.logs_helper")->addWeChatPayLog("5");

            if($order->getWalletAmount()>0){
                $this->get("auto_manager.logs_helper")->addWeChatPayLog("6");

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
            // $this->get("auto_manager.coupon_helper")->coupon_activity_entrance($order);
            
            $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                ('auto_api_coupon_activity'), ['OrderID'=>$order->getId()]);
            
            $this->get("auto_manager.logs_helper")->addWeChatPayLog($result['out_trade_no']."--"
                .($result['total_fee']/100)."--".$result['trade_type'])."--".$result['transaction_id'];
        }

        exit;

    }


    /**
     * @Route("/wechat/charge/notify.html", methods="POST",name="auto_api_wechat_charge_pay_notify",)
     */
    public function wechatChargeNotifyAction()
    {

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("h5 pay begin:".date('Ymdhis'));
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);

        if($notify->GetReturn_code()){

            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            //如果返回成功则验证签名
            $result = WxPayResults::Init($xml);

            /**
             * @var $order \Auto\Bundle\ManagerBundle\Entity\RechargeOrder
             */

            $orderId = substr($result['out_trade_no'],14);
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->find($orderId);

            $this->get("auto_manager.logs_helper")->addWeChatPayLog("充值订单号:".$orderId);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }


            $nowTime = $order->getCreateTime()->getTimestamp();
            $add_amount = 0;

            // 首充
            if (self::FIRST_RECHARGE == '1STRECHARGE' && $nowTime <= strtotime(self::FIRST_RECHARGE_END_TIME) &&
                $nowTime > strtotime(self::FIRST_RECHARGE_START_TIME)) {

                $qbs =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RechargeOrder')
                        ->createQueryBuilder('o');



                $recharge_order =
                    $qbs
                        ->select('COUNT(o)')
                        ->where($qbs->expr()->isNotNull('o.payTime'))
                        ->andWhere($qbs->expr()->eq('o.member', ':member'))
                        ->setParameter('member', $order->getMember())
                        ->getQuery()
                        ->getSingleScalarResult()
                ;

                !empty($recharge_order) ? $add_amount =0 : $add_amount = self::FIRST_RECHARGE_ADD_AMOUNT;
            }


            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);


            $this->get("auto_manager.logs_helper")->addWeChatPayLog("55555555555555555");

            $man = $this->getDoctrine()->getManager();

            $man->persist($order);
            $man->flush();

            $member=$order->getMember();
            $member->setWallet($member->getWallet()+$order->getAmount());
            $man->persist($member);
            $man->flush();

            //1.4余额变更 1充值返现
            //1充值返现 2人工充值 3租车费用 4退款 5扣款 6邀请返现
            //动作 用户id 充值金额 到账金额 消费金额

            $consumptionAmount = 0;
            $actualAmount = $order->getActualAmount();
            $amount = $order->getAmount();

            $balance = $this->get('auto_manager.recharge_helper')->balance_record(1,$order->getMember()->getId(),$actualAmount,$amount,$consumptionAmount);

             // 充值记录
            $operate = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOperate')
                ->find(1);

            $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
            $record->setCreateTime(new \DateTime());
            $record->setMember($member);
            $record->setOperate($operate);
            $record->setWalletAmount($member->getWallet());
            $record->setAmount($order->getAmount()+$add_amount);
            $record->setRemark($operate->getName());
            $man->persist($record);
            $man->flush();

            $this->get("auto_manager.logs_helper")->addWeChatPayLog($result['out_trade_no']."--"
                .($result['total_fee']/100)."--".$result['trade_type'])."--".$result['transaction_id'];
        }

        exit;

    }






}