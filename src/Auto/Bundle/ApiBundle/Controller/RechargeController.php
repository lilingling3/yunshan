<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/19
 * Time: 下午2:43
 */

namespace Auto\Bundle\ApiBundle\Controller;
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
 * @Route("/recharge")
 */

class RechargeController extends BaseController {

    //首充活动是否开启
    const FIRST_RECHARGE = '1STRECHARGE';
    //设置首充活动开始时间
    const FIRST_RECHARGE_START_TIME = '2017-05-01 09:00:00';
    //设置首充活动结束时间
    const FIRST_RECHARGE_END_TIME = '2017-05-02 00:00:00';
    //首充活动追加金额
    const FIRST_RECHARGE_ADD_AMOUNT = 100;
    //充值记录每页显示10条
    const PER_PAGE = 10;

    const DEFAULT_ACTIVITY = 1;

    //2.3.0充值活动
    /**
     * 充值活动信息查询
     * @Route("/activity/list", methods="POST",name="auto_api_recharge_step_list")
     */
    public function activityListAction(Request $req)
    {

        $uid  = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }

        $wallet = empty($member->getWallet()) ? 0: $member->getWallet();

        // 当前是否存在活动
        $activity = $this->get('auto_manager.recharge_helper')->get_current_activity();

        if (!isset($activity)) {
            // 数据错误
            return new JsonResponse([
                'errorCode'    => self::E_NO_COUPON_ACTIVITY,
                'errorMessage' => self::M_NO_COUPON_ACTIVITY,
            ]);
        }


        $qbs =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->createQueryBuilder('o');

        $recharge_order =
            $qbs
                ->select('o')
                ->where($qbs->expr()->isNotNull('o.payTime'))
                ->andWhere($qbs->expr()->eq('o.member', ':member'))
                ->andWhere($qbs->expr()->gte('o.payTime', ':startTime'))
                ->andWhere($qbs->expr()->lte('o.payTime', ':endTime'))
                ->setParameter('startTime', new \DateTime(self::FIRST_RECHARGE_START_TIME))
                ->setParameter('endTime', new \DateTime(self::FIRST_RECHARGE_END_TIME))
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult()
        ;

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargePriceStep')
                ->createQueryBuilder('r')
        ;

        $stepList =
            $qb
                ->select('r')
                ->orderBy('r.step', 'ASC')
                ->where($qb->expr()->eq('r.activity', ':activity'))
                ->setParameter('activity', $activity)
                ->getQuery()
                ->getResult()
        ;

        if (empty($stepList)) {

            //数据错误
            return new JsonResponse([
                'errorCode'    => self::E_NO_RECHARGE_ACTIVITY,
                'errorMessage' => self::M_NO_RECHARGE_ACTIVITY,
            ]);
        }

        $nowTime = (new \DateTime())->getTimestamp();

        $isExistFirstRecharge = (empty($recharge_order) && $nowTime <= strtotime(self::FIRST_RECHARGE_END_TIME) &&
            $nowTime > strtotime(self::FIRST_RECHARGE_START_TIME)) ? true: false;

        // 是否首充
        $isFirstRecharge = !empty($recharge_order) ? false: true;

        // 是否为默认活动
        // 1: 是 0:否
        // 如果存在额外充反则为false
        $isDefault = $activity === self::DEFAULT_ACTIVITY ? $isExistFirstRecharge ? false: true: false;

        $recharge_step = array_map( $this->get('auto_manager.recharge_helper')->get_recharge_price_step_normalizer(), $stepList);


        if (!empty($recharge_step)) {

            foreach ($recharge_step as &$value) {

                // 五一活动，充反
                if (self::FIRST_RECHARGE == '1STRECHARGE' &&
                    $nowTime <= strtotime(self::FIRST_RECHARGE_END_TIME) &&
                    $nowTime > strtotime(self::FIRST_RECHARGE_START_TIME) &&
                    $isFirstRecharge) {

                    $value['addAmount'] = self::FIRST_RECHARGE_ADD_AMOUNT;
                } else {

                    $value['addAmount'] = 0;
                }
            }
        }

        return new JsonResponse([
            'errorCode'    => self::E_OK,
            'wallet'       => $wallet,
            'steplist'     => $recharge_step,
            'isDefaultActivity'    => $isDefault
        ]);

    }


    /**
     * @Route("/new/order", methods="POST",name="auto_api_recharge_new_order")
     */
    public function orderNewAction(Request $req){

        $uid = $req->request->get('userID');
        $amount = $req->request->get('amount');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }
        $activity_id=$this->get('auto_manager.recharge_helper')->get_current_activity();
      
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargePriceStep')
                ->createQueryBuilder('c');

        $activity_step =
            $qb
                ->select('c')
                ->join('c.activity','a')
                ->where($qb->expr()->eq('c.price',':amount'))
                ->andWhere($qb->expr()->eq('a.id',':id'))
                ->setParameter('id', $activity_id)
                ->setParameter('amount',$amount)
                ->getQuery()
                ->getOneOrNullResult();
        ;
        
        if(empty($activity_step)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_RECHARGE_ACTIVITY,
                'errorMessage' =>self::M_NO_RECHARGE_ACTIVITY,
            ]);
        }

        $nowTime = (new \DateTime())->getTimestamp();
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
                    ->setParameter('member', $member)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;

            !empty($recharge_order) ? $add_amount =0 : $add_amount = self::FIRST_RECHARGE_ADD_AMOUNT;

        }

        $recharge = new \Auto\Bundle\ManagerBundle\Entity\RechargeOrder();

        $recharge->setMember($member)
            ->setAmount($amount+$activity_step->getCashBack()+$add_amount)
            ->setActivity($activity_step->getActivity())
            ->setActualAmount($amount)
        ;

        $man = $this->getDoctrine()->getManager();
        $man->persist($recharge);
        $man->flush();

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'rechargeOrderID'=>$recharge->getId()
        ]);
    }

    /**
     * @Route("/alipay2/order", methods="POST")
     */
    public function alipay2OrderAction(Request $req)
    {

        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $order_id = $req->request->getInt('orderID');

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        $pay_amount = $order->getActualAmount();

        $sign_data = new AlipaySignData();
        $sign_data->SetPartner(AlipayConfig::PARTNER);
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");
        $sign_data->SetOutTradeNo(time().$order_id);
        $sign_data->SetSubject('充值服务');
        $sign_data->SetBody("云杉智行");
        $sign_data->SetTotalFee($pay_amount);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_recahrge_alipay2_pay_notify"));
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
     * @Route("/alipay2/notify.html", methods="POST",name="auto_api_recahrge_alipay2_pay_notify")
     */
    public function alipay2NotifyAction(Request $req)
    {
        //计算得出通知验证结果


            $jsonContent = json_encode($_POST);

            $this->get('auto_manager.logs_helper')->addPayNotifyLog(2, $jsonContent);

            if($_POST['trade_status'] == 'TRADE_SUCCESS'){

                $alipayRSA = new AlipayRSA();

                $order_id = $_POST['out_trade_no'];

            $order_id = substr($order_id,10);

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->find($order_id);

//            if($order->getPayTime()||empty($order)){
//                echo "fail";exit;
//            }

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

            //首充活动

            if(self::FIRST_RECHARGE == 'start'){

                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RechargeOrder')
                        ->createQueryBuilder('r');

                $auth_recharge_order =
                    $qb
                        ->select('r')
                        ->where($qb->expr()->isNull('r.refundTime '))
                        ->andWhere($qb->expr()->gte('r.payTime', ':start_time'))
                        ->setParameter('start_time', self::FIRST_RECHARGE_START_TIME)
                        ->andWhere($qb->expr()->lte('r.payTime', ':end_time'))
                        ->setParameter('end_time',self::FIRST_RECHARGE_END_TIME)
                        ->andWhere($qb->expr()->eq('r.member',':member'))
                        ->setParameter('member', $order->getMember())
                        ->getQuery()
                        ->getResult();
                ;

                !empty($auth_recharge_order)?$add_amount = 0:$add_amount = self::FIRST_RECHARGE_ADD_AMOUNT;

            }else{

                $add_amount = 0;

            }
            //首充活动

            $amount = $_POST['total_fee']+$add_amount;

            if(!empty($order->getActivity())){
                $amount = $_POST['total_fee']*$order->getActivity()->getDiscount()+$add_amount;
            }
            $order->setAmount($amount);
            $order->getMember()->setWallet($order->getMember()->getWallet()+$amount);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
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
     * @Route("/wechat2/order", methods="POST")
     */
    public function wechatOrder2Action(Request $req){

        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');


        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if($order->getPayTime()) {
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message'=>'该订单已付款完成']);
        }


        $pay_amount = $order->getActualAmount();

        $tools = new JsApiPay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("充值服务");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        $input->SetTotal_fee($pay_amount*100);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_recahrge_wechat2_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'payParameters'=>$payParameters,
        ]);

    }


    /**
     * @Route("/wechat2/notify.html", methods="POST",name="auto_api_recahrge_wechat2_pay_notify",)
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

            //首充活动是否进行


            if(self::FIRST_RECHARGE == 'start'){

                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:RechargeOrder')
                        ->createQueryBuilder('r');

                $auth_recharge_order =
                    $qb
                        ->select('r')
                        ->where($qb->expr()->isNull('r.refundTime '))
                        ->andWhere($qb->expr()->gte('r.payTime', ':start_time'))
                        ->setParameter('start_time', self::FIRST_RECHARGE_START_TIME)
                        ->andWhere($qb->expr()->lte('r.payTime', ':end_time'))
                        ->setParameter('end_time',self::FIRST_RECHARGE_END_TIME)
                        ->andWhere($qb->expr()->eq('r.member',':member'))
                        ->setParameter('member', $order->getMember())
                        ->getQuery()
                        ->getResult();
                ;

                !empty($auth_recharge_order)?$add_amount = 0:$add_amount = self::FIRST_RECHARGE_ADD_AMOUNT;

            }else{

                $add_amount = 0;

            }
            //首充活动


            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $actual_amount = $result['total_fee']/100;
            $order->setActualAmount($actual_amount);
            $amount = $actual_amount+$add_amount;
            if(!empty($order->getActivity())){

                $amount = $actual_amount*$order->getActivity()->getDiscount()+$add_amount;
            }
            $order->setAmount($amount);
            $order->getMember()->setWallet($order->getMember()->getWallet()+$amount);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
            $this->get('auto_manager.sms_helper')->add($order->getMember()->getMobile(),'您在'.$order->getPayTime()
                    ->format('Y年m月d日 H点i分').' 成功充值'.$order->getActualAmount().'元，账户余额'.$order->getMember()->getWallet()
                .'元。');

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

    }
    //new recharge






    /**
     * @Route("/order/new", methods="POST")
     */
    public function newOrderAction(Request $req){

        $uid = $req->request->get('userID');
        $amount = $req->request->getInt('amount');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeActivity')
                ->createQueryBuilder('a');

        $activity =
            $qb
            ->select('a')
            ->andWhere($qb->expr()->lte('a.startTime',':time'))
            ->andWhere($qb->expr()->gte('a.endTime',':time'))
            ->setParameter('time', (new \DateTime()))
                ->setMaxResults(1)
                ->getQuery()
            ->getOneOrNullResult();
        ;



        $recharge = new \Auto\Bundle\ManagerBundle\Entity\RechargeOrder();
        if(!empty($activity)){
            $recharge->setActivity($activity);
        }

        $recharge->setMember($member)
                 ->setActualAmount($amount)

                ;

        $man = $this->getDoctrine()->getManager();
        $man->persist($recharge);
        $man->flush();

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'rechargeOrderID'=>$recharge->getId()
        ]);
    }

    /**
     * @Route("/activity/info", methods="POST")
     */
    public function activityInfoAction(Request $req)
    {
        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeActivity')
                ->createQueryBuilder('a');

        $activity =
            $qb
                ->select('a')
                ->andWhere($qb->expr()->lte('a.startTime',':time'))
                ->andWhere($qb->expr()->gte('a.endTime',':time'))
                ->setParameter('time', (new \DateTime()))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        ;


        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'rechargeActivity' =>empty($activity)?'':$activity->getName().",充的多返的多",
            'rechargeAmountList'=>[100,200,300,500,800,1000],
            'minAmount'=>100,
            'discount'=>empty($activity)?1:$activity->getDiscount(),
            'wallet'=>$member->getWallet()

        ]);

    }


    /**
     * @Route("/info", methods="POST")
     */
    public function infoAction(Request $req)
    {
        $recharge_id = $req->request->getInt('rechargeOrderID');
        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $recharge = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->findOneBy(['id'=>$recharge_id,'member'=>$member]);

        if(empty($recharge)){

            return new JsonResponse([
                'errorCode'  =>  self::E_NO_RECHARGE_ORDER,
                'errorMessage'=> self::M_NO_RECHARGE_ORDER

            ]);
        }

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'rechargeStatus'=>$recharge->getStatus()

        ]);

    }

    /**
     * @Route("/alipay/order", methods="POST")
     */
    public function alipayOrderAction(Request $req)
    {
        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $order_id = $req->request->getInt('orderID');

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER,
            ]);

        }

        $pay_amount = $order->getActualAmount();

        $sign_data = new AlipaySignData();
        $sign_data->SetPartner(AlipayConfig::PARTNER);
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");
        $sign_data->SetOutTradeNo($order_id);
        $sign_data->SetSubject('充值服务');
        $sign_data->SetBody("云杉智行");
        $sign_data->SetTotalFee($pay_amount);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_recahrge_alipay_pay_notify"));
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
     * @Route("/alipay/notify.html", methods="POST",name="auto_api_recahrge_alipay_pay_notify")
     */
    public function alipayNotifyAction(Request $req)
    {
        //计算得出通知验证结果

        $jsonContent = json_encode($_POST);

        $this->get('auto_manager.logs_helper')->addPayNotifyLog(1, $jsonContent);

        if($_POST['trade_status'] == 'TRADE_SUCCESS'){

            $alipayRSA = new AlipayRSA();

            $result = $alipayRSA->rsaVerify($_POST,$this->container->getParameter("alipay_public_key"),$_POST['sign']);

            if(!$result){
                echo "fail";
                exit;
            }

            $order_id = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->find($order_id);

            if($order->getPayTime()||empty($order)){
                echo "fail";exit;
            }
            $order->setPayTime(new \DateTime());
            $order->setAlipayTradeNo($trade_no);
            $order->setActualAmount($_POST['total_fee']);

            $amount = $_POST['total_fee'];

            if(!empty($order->getActivity())){
                $amount = $_POST['total_fee']*$order->getActivity()->getDiscount();
            }
            $order->setAmount($amount);
            $order->getMember()->setWallet($order->getMember()->getWallet()+$amount);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();

            $this->get('auto_manager.sms_helper')->add($order->getMember()->getMobile(),'您在'.$order->getPayTime()
                    ->format('Y年m月d日 H点i分').' 成功充值'.$order->getActualAmount().'元，账户余额'.$order->getMember()->getWallet()
                .'元');

            echo "success";
        }

        exit;

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

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOrder')
            ->findOneBy(['id'=>$order_id,'member'=>$member]);

        if($order->getPayTime()) {
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message'=>'该订单已付款完成']);
        }


        $pay_amount = $order->getActualAmount();

        $tools = new JsApiPay();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("充值服务");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        $input->SetTotal_fee($pay_amount*100);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_recahrge_wechat_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'payParameters'=>$payParameters,
        ]);

    }


    /**
     * @Route("/wechat/notify.html", methods="POST",name="auto_api_recahrge_wechat_pay_notify",)
     */
    public function wechatNotifyAction()
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

            $order->setPayTime(new \DateTime());
            $order->setWechatTransactionId($result['transaction_id']);
            $actual_amount = $result['total_fee']/100;
            $order->setActualAmount($actual_amount);
            $amount = $actual_amount;
            if(!empty($order->getActivity())){

                $amount = $actual_amount*$order->getActivity()->getDiscount();
            }
            $order->setAmount($amount);
            $order->getMember()->setWallet($order->getMember()->getWallet()+$amount);

            $man = $this->getDoctrine()->getManager();
            $man->persist($order);
            $man->flush();
            $this->get('auto_manager.sms_helper')->add($order->getMember()->getMobile(),'您在'.$order->getPayTime()
                    ->format('Y年m月d日 H点i分').' 成功充值'.$order->getActualAmount().'元，账户余额'.$order->getMember()->getWallet()
                .'元');

        }

    }

    /**
     * @Route("/record", methods="POST",name="auto_api_recharge_record")
     */
    public function getRechargeRecord(Request $req)
    {

        $uid  = $req->request->get('userID');
        $page  = $req->request->get('page');

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
                ->getRepository('AutoManagerBundle:RechargeRecord')
                ->createQueryBuilder('r');

        $recordList =
            new Paginator(
                $qb
                    ->select('r')
                    ->orderBy('r.createTime', 'DESC')
                    ->where($qb->expr()->eq('r.member', ':member'))
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $list = array_map($this->get('auto_manager.recharge_helper')->get_recharge_record(),
                    $recordList->getIterator()->getArrayCopy());

        return new JsonResponse([
            'errorCode'    => self::E_OK,
            'pageCount'    => ceil($recordList->count() / self::PER_PAGE),
            'page'         => $page,
            'list'         => $list
        ]);
    }


    /**
     * @Route("/activity/delete", methods="POST")
     */
    public function deleteRechargeActivity(Request $req)
    {

        $uid       = $req->request->get('userID');
        $activity  = $req->request->getInt('rechargeActivityId');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }

        // 管理员权限
        $roles = $member->getRoles();
        if(!in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN, $roles)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_RIGHT,
                'errorMessage' =>  self::M_NO_RIGHT
            ]);

        }

        // 验证是否存在该活动
        $activityInfo = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeActivity')
            ->findOneBy(['id'=>$activity]);

        if (empty($activityInfo)) {

            // 参数错误
            return new JsonResponse([
                'errorCode'    =>  self::E_WRONG_PARAMETER,
                'errorMessage' =>  self::M_WRONG_PARAMETER
            ]);

        }

        $stepList = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargePriceStep')
            ->findBy(['activity'=>$activity]);

        $man = $this->getDoctrine()->getManager();

        if (!empty($stepList)) {
            
            // 存在活动 => 删除阶梯数据
            foreach ($stepList as $value) {
                $man->remove($value);
                $man->flush();
                // $man->clear();
            } 
        }

        $man->remove($activityInfo);
        $man->flush();

        return new JsonResponse([
            'errorCode'    => self::E_OK,
        ]);
    }

    /**
     * @Route("/activity/validate", methods="POST")
     */
    public function validateRechargeActivity(Request $req)
    {

        $st   = $req->request->get('startime');
        $et   = $req->request->get('endtime');
        $curActivity = $req->request->getInt('activity');
        // 
        if ($st >= $et) {
            return new JsonResponse([
                'errorCode'    =>  -1,
                'errorMessage' =>  "时间数据有误！"
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeActivity')
                ->createQueryBuilder('r')
        ;

        
        $qb
            ->select('r')
            ->orderBy('r.id', 'DESC')
            ->andWhere($qb->expr()->neq('r.id',':default'))
            ->setParameter('default',1)
        ;

        if (!empty($curActivity)) {
                $qb
                    ->andWhere($qb->expr()->neq('r.id',':curActivity'))
                    ->setParameter('curActivity', $curActivity)
                ;
        }

        $activity =
                $qb
                    ->getQuery()
                    ->getResult()
                ;
        
        if (!empty($activity)) {

            foreach ($activity as $val) {
                if( strcmp($st, $val->getEndTime()->format('Y-m-d H:i:s')) < 0 && 
                    strcmp($et, $val->getStartTime()->format('Y-m-d H:i:s')) > 0 ) {

                    return new JsonResponse([
                        'errorCode'    =>  -2,
                        'errorMessage' =>  "同一时间只能有一个活动"
                    ]);
                } 
            }
        }


        return new JsonResponse([
            'errorCode'    => self::E_OK,
        ]);
    }

    /**
     * @Route("/test", methods="post")
     */
    public function initRechargeRecord()
    {

        // 找出
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->createQueryBuilder('c')
        ;

        $memberList =
            $qb
                ->select('c')
                ->andWhere($qb->expr()->isNotNull('c.wallet'))
                ->getQuery()
                ->getResult()
        ;

        if (empty($memberList)) {
            // 没有消费过余额
            return new JsonResponse([
                'errorMessage' => 'gogogo',
                'errorCode'    => self::E_OK,
            ]);
        }
// echo $memberList->getCount();
        $test = [];
        $person = [];

        foreach ($memberList as $member) {
    
            $person[] = $member->getId();
            // 先充值记录
            $qb = 
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOrder')
                    ->createQueryBuilder('r')
            ;

            // 人工充值
            $recharge = 
                $qb
                    ->select('r')
                    ->andWhere($qb->expr()->isNull('r.wechatTransactionId'))
                    ->andWhere($qb->expr()->isNull('r.alipayTradeNo'))
                    ->andWhere($qb->expr()->isNotNull('r.payTime'))
                    ->andWhere($qb->expr()->eq('r.member', ':member'))
                    ->setParameter('member', $member)
                    ->getQuery()
                    ->getResult()
            ;

            if (empty($recharge)) {

                // 没有人工充过值
                continue;
            }

            
            $total = 0;
            $now = new \DateTime();

            for ($i=0; $i < count($recharge); $i++) { 

                // 保存充值
                $total = $total + $recharge[$i]->getAmount();
                self::saveRechargeRecord($member,2,$recharge[$i]->getAmount(),$total,$recharge[$i]->getPayTime());


                $startTime = $recharge[$i]->getPayTime();
                $endTime   = isset($recharge[$i+1]) &&  $recharge[$i+1] ? $recharge[$i+1]->getPayTime(): $now;

                $qb = 
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:BaseOrder')
                        ->createQueryBuilder('o')
                ;


                // 消耗余额订单
                // 非取消、已付款、member、还消耗了余额
                $orderList = 
                    $qb
                        ->select('o')
                        ->orderBy('o.payTime','ASC')
                        ->andWhere($qb->expr()->isNull('o.cancelTime'))
                        ->andWhere($qb->expr()->isNotNull('o.walletAmount'))
                        ->andWhere($qb->expr()->neq('o.walletAmount',':wallet'))
                        ->andWhere($qb->expr()->isNotNull('o.payTime'))
                        ->andWhere($qb->expr()->eq('o.member', ':member'))
                        ->andWhere($qb->expr()->gte('o.payTime',':startTime'))
                        ->andWhere($qb->expr()->lte('o.payTime',':endTime'))
                        ->setParameter('member', $member)
                        ->setParameter('wallet', 0)
                        ->setParameter('startTime', $startTime)
                        ->setParameter('endTime', $endTime)                        
                        ->getQuery()
                        ->getResult()                
                ;

                if (empty($orderList)) {

                    continue;
                }

                foreach ($orderList as $order) {
                    
                    $total = $total - $order->getWalletAmount();
                    self::saveRechargeRecord($member,
                                             3,
                                             $order->getWalletAmount(),
                                             $total,
                                             $order->getPayTime(),
                                             $order);

                }

            }


        }


        return new JsonResponse([
            'memberList'    => $person,
            'count'         => $test,
            'errorCode'    => self::E_OK,
        ]);
    }


    private function saveRechargeRecord($member,$opType,$amount,$walletAmount,$time,$orderId,$operator=null)
    {

        $operate = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeOperate')
            ->find($opType);

        $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
        $record->setCreateTime($time);
        // $record->setRechargeOrder($rechargeOrder);
        $record->setMember($member);
        $record->setAmount($amount);
        $record->setWalletAmount($walletAmount);
        // $record->setOperater($operater);
        $record->setOperate($operate);
        $record->setRemark($operate->getName());

        $man = $this->getDoctrine()->getManager();
        $man->persist($record);
        $man->flush();
    }

}