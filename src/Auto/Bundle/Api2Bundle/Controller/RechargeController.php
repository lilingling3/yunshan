<?php
namespace Auto\Bundle\Api2Bundle\Controller;
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
    //默认的活动
    const DEFAULT_ACTIVITY = 1;

    //2.3.0充值活动
    /**
     * 充值活动信息查询
     * @Route("/activity/list", methods="POST")
     */
    public function activityListAction(Request $req)
    {

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();

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
        $isDefault = $activity === self::DEFAULT_ACTIVITY ? $isExistFirstRecharge ? 2: 1: 0;

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
            'rechargeList' => $recharge_step,
            'isDefaultActivity'    => $isDefault,
            'rechargeActivity'     => "2"
        ]);
    }

    /**
     * @Route("/new/order", methods="POST")
     */
    public function orderNewAction(Request $req){

        $amount = $req->request->get('amount');

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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
     * @Route("/info", methods="POST")
     */
    public function infoAction(Request $req)
    {
        $recharge_id = $req->request->getInt('rechargeOrderID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();

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
     * @Route("/alipay2/order", methods="POST")
     */
    public function alipay2OrderAction(Request $req)
    {
        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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
        $sign_data->SetSellerId("yszhcw_fszl@win-sky.com.cn");//lpcarx@yahoo.com
        $sign_data->SetOutTradeNo(time().$order_id);
        $sign_data->SetSubject('云杉智行充值服务');
        $sign_data->SetBody("云杉智行");
        $sign_data->SetTotalFee($pay_amount);
        //$sign_data->SetTotalFee(0.01);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $sign_data->SetNotifyUrl($base_url.$this->generateUrl("auto_api_2_recahrge_alipay_pay_notify"));
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
     * @Route("/wechat2/order", methods="POST")
     */
    public function wechatOrder2Action(Request $req){

        $order_id = $req->request->get('orderID');

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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
        $input->SetBody("云杉智行充值服务");
        $input->SetOut_trade_no(date("YmdHis").$order->getId());
        $input->SetTotal_fee($pay_amount*100);
        $base_url = $this->get("auto_manager.curl_helper")->base_url();
        $input->SetNotify_url($base_url.$this->generateUrl("auto_api_2_recahrge_wechat_pay_notify"));
        $input->SetTrade_type("APP");
        $wxorder = WxPayApi::unifiedOrder($input);
        $payParameters = $tools->GetJsApiParameters($wxorder);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'payParameters'=>$payParameters,
        ]);

    }

    /**
     * @Route("/record", methods="POST")
     */
    public function getRechargeRecord(Request $req)
    {
        $page  = $req->request->get('page');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();

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
            'list'         => $list
        ]);
    }




}
