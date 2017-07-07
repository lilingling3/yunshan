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
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipayCheckNotify;
use Auto\Bundle\ManagerBundle\Payment\Alipay\AlipaySignData;

use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayConfig;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayUnifiedOrder;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\PayNotifyCallBack;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayResults;

/**
 * @Route("/usePay")
 */

class UsePayController extends BaseController {


    //首充活动是否开启
    const FIRST_RECHARGE = '1STRECHARGE';
    //设置首充活动开始时间
    const FIRST_RECHARGE_START_TIME = '2017-05-01 09:00:00';
    //设置首充活动结束时间
    const FIRST_RECHARGE_END_TIME = '2017-05-02 00:00:00';
    //首充活动追加金额
    const FIRST_RECHARGE_ADD_AMOUNT = 100;


    /**
     * 用户支付宝充值回调接口
     * @Route("/alipay2/notify.html", methods="POST",name="auto_api_2_recahrge_alipay_pay_notify")
     */
    public function alipay2NotifyAction(Request $req)
    {

        $alipayNotify = new AlipayCheckNotify();

        $public_patner_key = $this->container->getParameter("alipay_public_key");

        $resule = $alipayNotify->getSignVeryfy($_POST,$_POST['sign'],$public_patner_key);


        if(!$resule){

            echo "signFail";
            exit;
        }



        $jsonContent = json_encode($_POST);

        $this->get('auto_manager.logs_helper')->addPayNotifyLog(1, $jsonContent);


        //计算得出通知验证结果
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){

            $order_id = $_POST['out_trade_no'];

            $order_id = substr($order_id,10);

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->find($order_id);


            if(empty($order)){

                echo "fail";
                exit;

            }

            if(!empty($order->getPayTime())){

                echo "fail";
                exit;

            }

            $order->setPayTime(new \DateTime());
            $order->setAlipayTradeNo($trade_no);
            $order->setActualAmount($_POST['total_fee']);

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
                        ->andWhere($qbs->expr()->gte('o.payTime', ':startTime'))
                        ->andWhere($qbs->expr()->lte('o.payTime', ':endTime'))
                        ->setParameter('startTime', new \DateTime(self::FIRST_RECHARGE_START_TIME))
                        ->setParameter('endTime', new \DateTime(self::FIRST_RECHARGE_END_TIME))
                        ->andWhere($qbs->expr()->eq('o.member', ':member'))
                        ->setParameter('member', $order->getMember())
                        ->getQuery()
                        ->getSingleScalarResult()
                ;

                !empty($recharge_order) ? $add_amount =0 : $add_amount = self::FIRST_RECHARGE_ADD_AMOUNT;

            }



            // 更新余额
            $member = $order->getMember();

            $wallet = empty($member->getWallet()) ? 0: $member->getWallet();
            $total = $wallet+ $order->getAmount();
            $member->setWallet($total);

            $man = $this->getDoctrine()->getManager();
            $man->persist($member);
            $man->flush();

            $man->persist($order);
            $man->flush();

            // 充值记录
            $operate = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOperate')
                ->find(1);

            $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
            $record->setCreateTime(new \DateTime());
            $record->setMember($member);
            $record->setOperate($operate);
            $record->setWalletAmount($total);
            $record->setAmount($order->getAmount()+$add_amount);
            $record->setRemark($operate->getName());
            $man->persist($record);
            $man->flush();

            $this->get('auto_manager.sms_helper')->add($order->getMember()->getMobile(),'您在'.$order->getPayTime()
                    ->format('Y年m月d日 H点i分').' 成功充值'.$order->getActualAmount().'元，账户余额'.$order->getMember()->getWallet()
                .'元');

            //充值成功后消息列表提醒用户
            $recharge_message = '您于'.$order->getPayTime()->format('Y年m月d日 H点i分').' 成功充值'.number_format($order->getActualAmount(),2).'元，账户余额'.number_format($order->getMember()->getWallet(),2)
                .'元。';

            $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
            $message->setContent($recharge_message)
                ->setKind(1)
                ->setMember($order->getMember())
                ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
            ;
            $man = $this->getDoctrine()->getManager();
            $man->persist($message);
            $man->flush();

            echo "success";
        }

        exit;

    }





    /**
     * 用户充值微信充值回调接口
     * @Route("/wechat2/notify.html", methods="POST",name="auto_api_2_recahrge_wechat_pay_notify",)
     */
    public function wechat2NotifyAction()
    {

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("begin:".date('Hmdhis'));

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
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->find($orderId);

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
                        ->andWhere($qbs->expr()->gte('o.payTime', ':startTime'))
                        ->andWhere($qbs->expr()->lte('o.payTime', ':endTime'))
                        ->setParameter('startTime', new \DateTime(self::FIRST_RECHARGE_START_TIME))
                        ->setParameter('endTime', new \DateTime(self::FIRST_RECHARGE_END_TIME))
                        ->setParameter('member', $order->getMember())
                        ->getQuery()
                        ->getSingleScalarResult()
                ;

                !empty($recharge_order) ? $add_amount =0 : $add_amount = self::FIRST_RECHARGE_ADD_AMOUNT;
		        $this->get("auto_manager.logs_helper")->addWeChatPayLog('===||'.$recharge_order);
            }


            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $actual_amount = $result['total_fee']/100;
            $order->setActualAmount($actual_amount);

            // 更新余额
            $member = $order->getMember();
            $wallet = empty($member->getWallet()) ? 0: $member->getWallet();
            $total = $wallet+$order->getAmount();
            $member->setWallet($total);

            $man = $this->getDoctrine()->getManager();
            $man->persist($member);
            $man->flush();

            $man->persist($order);
            $man->flush();

            // 充值记录
            $operate = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOperate')
                ->find(1);

            $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
            $record->setCreateTime(new \DateTime());
            $record->setMember($member);
            $record->setOperate($operate);
            $record->setWalletAmount($total);
            $record->setAmount($order->getAmount()+$add_amount);
            $record->setRemark($operate->getName());
            $man->persist($record);
            $man->flush();

            $this->get('auto_manager.sms_helper')->add($order->getMember()->getMobile(),'您在'.$order->getPayTime()
                    ->format('Y年m月d日 H点i分').' 成功充值'.$order->getActualAmount().'元，账户余额'.$order->getMember()->getWallet()
                .'元。');

            echo "success";

        }

        //充值成功后消息列表提醒用户
        $recharge_message = '您于'.$order->getPayTime()->format('Y年m月d日 H点i分').' 成功充值'.number_format($order->getActualAmount(),2).'元，账户余额'.number_format($order->getMember()->getWallet(),2).'元。';

        $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
        $message->setContent($recharge_message)
            ->setKind(1)
            ->setMember($order->getMember())
            ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
        ;
        $man = $this->getDoctrine()->getManager();
        $man->persist($message);
        $man->flush();

        exit;
    }





    /**
     * 订单支付宝回调接口
     * @Route("/orderAlipay/notify.html", methods="POST",name="auto_api_2_order_alipay_pay_notify")
     */
    public function OrderAlipayNotifyAction(Request $req)
    {


        $jsonContent = json_encode($_POST);

        $this->get('auto_manager.logs_helper')->addPayNotifyLog(1, $jsonContent);

        $alipayNotify = new AlipayCheckNotify();

        $public_patner_key = $this->container->getParameter("alipay_public_key");

        $resule = $alipayNotify->getSignVeryfy($_POST,$_POST['sign'],$public_patner_key);


        if(!$resule){

            echo "signFail";
            exit;
        }

        //计算得出通知验证结果
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){


            $order_id = substr($_POST['out_trade_no'],14);

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->find($order_id);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
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

            // $this->get("auto_manager.coupon_helper")->coupon_activity_entrance($order);
            $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                ('auto_api_coupon_activity'), ['OrderID'=>$order->getId()]);

            echo "success";
        }

        exit;

    }

    /**
     * 订单微信支付回调接口
     * @Route("/orderWechat/notify.html", methods="POST",name="auto_api_2_order_wechat_pay_notify",)
     */
    public function orderWechatNotifyAction()
    {

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("begin:".date('Ymdhis'));

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


            // $this->get("auto_manager.coupon_helper")->coupon_activity_entrance($order);

            $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                ('auto_api_coupon_activity'), ['OrderID'=>$order->getId()]);

            $this->get("auto_manager.logs_helper")->addWeChatPayLog($result['out_trade_no']."--"
                .($result['total_fee']/100)."--".$result['trade_type'])."--".$result['transaction_id'];

            echo "success";
        }

        exit;

    }


    /**
     * 缴费微信支付回调接口
     * @Route("/wechat/notify.html", methods="POST",name="auto_api_2_payment_wechat_pay_notify",)
     */
    public function wechatNotifyAction()
    {
        $this->get("auto_manager.logs_helper")->addWeChatPayLog("begin:".date('Ymdhis'));

        $notify = new PayNotifyCallBack();
        $notify->Handle(false);

        if($notify->GetReturn_code()){

            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];

            //如果返回成功则验证签名

            $result = WxPayResults::Init($xml);

            /**
             * @var $order \Auto\Bundle\ManagerBundle\Entity\PaymentOrder
             */

            $orderId = substr($result['out_trade_no'],14);
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:PaymentOrder')
                ->find($orderId);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }

            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $order->setAmount($result['total_fee']/100);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();


        }

        exit;

    }





    /**
     * 缴费支付宝回调接口
     * @Route("/alipay/notify.html", methods="POST",name="auto_api_2_usepay_alipay_pay_notify")
     */
    public function alipayNotifyAction(Request $req)
    {

        $jsonContent = json_encode($_POST);

        $this->get('auto_manager.logs_helper')->addPayNotifyLog(1, $jsonContent);

        $alipayNotify = new AlipayCheckNotify();

        $public_patner_key = $this->container->getParameter("alipay_public_key");

        $resule = $alipayNotify->getSignVeryfy($_POST,$_POST['sign'],$public_patner_key);


        if(!$resule){

            echo "signFail";
            exit;
        }


        if($_POST['trade_status'] == 'TRADE_SUCCESS'){

            $order_id = $_POST['out_trade_no'];

            $order_id = substr($order_id,10);

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:PaymentOrder')
                ->find($order_id);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }
            $order->setPayTime(new \DateTime());
            $order->setAlipayTradeNo($trade_no);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
            echo "success";
        }

        exit;
    }

    /**
     * 押金微信支付回调接口
     * @Route("/wechat/depositNotify.html", methods="POST",name="auto_api_2_deposit_wechat_pay_notify",)
     */
    public function wechatDepositNotifyAction()
    {

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("Deposit-Notify-Begin:".date('Ymdhis'));

        $notify = new PayNotifyCallBack();
        $notify->Handle(false);
        $this->get("auto_manager.logs_helper")->addWeChatPayLog("rtn Code:".$notify->GetReturn_code());
        if($notify->GetReturn_code()){

            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];

            //如果返回成功则验证签名
            $result = WxPayResults::Init($xml);

            /**
             * @var $order \Auto\Bundle\ManagerBundle\Entity\DepositOrder
             */

            $orderId = substr($result['out_trade_no'],14);
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->find($orderId);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }

            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $order->setAmount($result['total_fee']/100);

            $deposit = 
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Deposit')
                    ->findOneBy(["member" => $order->getMember()->getId()]);

            if (empty($deposit)) {
                echo "deposit fail";exit;
            }

            $deposit->setStatus(399);

            $amount = $deposit->getTotal() ? $deposit->getTotal() + $result['total_fee']/100 : $result['total_fee']/100;
            $deposit->setTotal($amount);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $man->persist($deposit);
            $man->flush();
        }

        exit;
    }


    /**
     * 押金支付宝回调接口
     * @Route("/alipay/depositNotify.html", methods="POST",name="auto_api_2_deposit_alipay_pay_notify",)
     */
    public function alipayDepositNotifyAction()
    {
        $jsonContent = json_encode($_POST);

        $this->get('auto_manager.logs_helper')->addPayNotifyLog(1, $jsonContent);

        $alipayNotify = new AlipayCheckNotify();

        $public_patner_key = $this->container->getParameter("alipay_public_key");

        $result = $alipayNotify->getSignVeryfy($_POST,$_POST['sign'],$public_patner_key);

        if(!$result){

            echo "signFail";
            exit;
        }


        if($_POST['trade_status'] == 'TRADE_SUCCESS'){

            $order_id = $_POST['out_trade_no'];

            $order_id = substr($order_id,14);

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->find($order_id);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }

            $order->setPayTime(new \DateTime());
            $order->setAlipayTradeNo($trade_no);
            $order->setAmount($_POST['total_fee']);

            $deposit = 
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Deposit')
                    ->findOneBy(["member" => $order->getMember()->getId()]);

            if (empty($deposit)) {
                echo "deposit fail";exit;
            }

            $deposit->setStatus(399);

            $amount = $deposit->getTotal() ? $deposit->getTotal() + $_POST['total_fee'] : $_POST['total_fee'];
            $deposit->setTotal($amount);


            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $man->persist($deposit);
            $man->flush();

            echo "success";
        }

        exit;
    }

}
