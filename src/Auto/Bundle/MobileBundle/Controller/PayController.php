<?php

namespace Auto\Bundle\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayConfig;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayUnifiedOrder;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\PayNotifyCallBack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * @Route("/pay")
 */
class PayController extends Controller
{

    const AUTH_SUCCESS = 299;

    /**
     * @Route("/wechat/order_{id}.html", methods="GET",name="auto_mobile_pay_order_fee")
     *@Template()
     */
    public function wechatOrderAction(Request $req,$id){

        $coupon_id = $req->query->getInt('coupon');
        $wallet = $req->query->get('wallet');

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);

        if(empty($rentalOrder)){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message'=>'无效订单!']
            );
        }


        if($coupon_id){
            $coupon = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->findOneBy(['id'=>$coupon_id,'member'=>$this->getUser()]);

            if(!empty($coupon))
                $rentalOrder->setCoupon($coupon);
            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalOrder);
            $man->flush();

        }


        if($rentalOrder->getPayTime()) {

            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message'=>'订单已付款完成!']
            );

        }

        $order = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder);

        $coupon_amount = empty($order['coupon'])?0:$order['coupon']['amount'];

        $wallet_amount = $order['walletAmount']>0?$order['walletAmount']:0;

        $pay_amount = ($order['costDetail']['cost'] - $coupon_amount - $wallet_amount)>0?($order['costDetail']['cost'] -
            $coupon_amount - $wallet_amount):0;

        $pay_amount = $pay_amount >0?round($pay_amount, 2):0;

        if($pay_amount < 0.01){

            $rentalOrder->setPayTime(new \DateTime());
            $rentalOrder->setAmount(0);
            $coupon = $rentalOrder->getCoupon();
            if(!empty($coupon)){
                $rentalOrder->getCoupon()->setUseTime(new \DateTime());
            }

            if($rentalOrder->getWalletAmount()>0 && $wallet>0){
                $rentalOrder->getMember()->setWallet($rentalOrder->getMember()->getWallet()-$rentalOrder->getWalletAmount());

                $wallet_record = new \Auto\Bundle\ManagerBundle\Entity\WalletRecord();
                $wallet_record->setAmount($rentalOrder->getWalletAmount())
                    ->setRentalOrder($rentalOrder);

                $man = $this->getDoctrine()->getManager();
                $man->persist($wallet_record);
                $man->flush();

                // 余额消耗记录
                $operate = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOperate')
                    ->find(3);

                $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
                $record->setCreateTime(new \DateTime());
                $record->setMember($rentalOrder->getMember());
                $record->setOperate($operate);
                $record->setWalletAmount($rentalOrder->getMember()->getWallet());
                $record->setAmount($rentalOrder->getWalletAmount());
                $record->setRemark($operate->getName());
                $man->persist($record);
                $man->flush();

            }


            $man = $this->getDoctrine()->getManager();

            $man->persist($rentalOrder);
            $man->flush();
            return $this->redirect($this->generateUrl("auto_mobile_rental_order_show",['id'=>$rentalOrder->getId()]));

        }





        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

//②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智慧-租赁费用");
        $input->SetAttach($rentalOrder->getMember()->getId());
        $input->SetOut_trade_no(date("YmdHis").$rentalOrder->getId());
        //$input->SetTotal_fee(1);
        $input->SetTotal_fee($pay_amount*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('车辆分时租赁服务');
        $input->SetNotify_url($req->server->get('HTTP_HOST').$this->generateUrl("auto_api_wechat_pay_notify"));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $wechat_order = WxPayApi::unifiedOrder($input);
        $this->get("auto_manager.logs_helper")->addWeChatPayLog('时间----'.date("YmdHis").'-----订单'.$id."----总金额".json_encode($pay_amount));
        $this->get("auto_manager.logs_helper")->addWeChatPayLog('时间----'.date("YmdHis").'-----订单'.$id.'------wechat_order----'.json_encode($wechat_order));
        $jsApiParametersArr = $tools->GetJsApiParameters($wechat_order);

//获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
        //  $signPackage = $this->get("auto_manager.wechat_helper")->getSignPackage();

        return ['editAddress'=>$editAddress,
            'jsApiParametersArr'=>$jsApiParametersArr,'order'=>$order];
    }

    /**
     * @Route("/rental/order", methods="POST",name="auto_mobile_pay_rental_order")
     */
    public function rentalOrderAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $order_id = $req->request->get('orderID');
        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($order_id);
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_rental_order_car_back'),
            ['userID'=>$uid,'orderID'=>$order_id]);
        $post_result = $this->get('auto_manager.curl_helper')->object_array(json_decode($post_json));
        if($post_result){
            if($post_result['errorCode'] == 0){
                $client = $this->get('auto_manager.sms_helper');
                $client->add(
                    '18810781246',
                    $this->renderView(
                        'AutoManagerBundle:Order:back.sms.twig',['license'=>$order->getRentalCar()->getLicense(),
                            'name'=>$order->getMember()->getName(),
                            'mobile'=>$order->getMember()->getMobile()]
                    )
                );
                return $this->redirect($this->generateUrl('auto_mobile_rental_order_show',['id'=>$order->getId()]));
            }else{
                return $this->render(
                    "AutoMobileBundle:Default:message.html.twig",
                    ['message'=>$post_result['errorMessage']]
                );
            }

        }else{

            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message'=>'还车失败,请拨打客服电话联系运营人员!']
            );
        }

    }


    /**
     * @Route("/wechat/deposit.html", methods="GET",name="auto_mobile_pay_order")
     * @Template()
     */
    public function wechatDepositAction(Request $req){

        $baseUrl   = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_deposit_order'),
            ['userID'=>$this->getUser()->getToken()]);
        $data = json_decode($post_json, true);

        if ($data['errorCode'] != 0) {

            return $this->render(

                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
        ;

        $deposit_info =
            $qb
                ->select('d')
                ->orderby('d.createTime','DESC')
                ->where($qb->expr()->eq('d.member',':member'))
                ->setParameter('member', $this->getUser())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if (empty($deposit_info)) {

            return $this->render(

               "AutoMobileBundle:Default:message.html.twig",
               ['message'=>'无效订单!']);
        }

        $info  = call_user_func($this->get('auto_manager.deposit_helper')->get_deposit_normalizer()
                    ,$deposit_info);

        $this->get("auto_manager.logs_helper")->addWeChatPayLog("------".$deposit_info->getId());
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智慧-押金费用");
        $input->SetAttach($deposit_info->getMember()->getId());
        $input->SetOut_trade_no(date("YmdHis").$deposit_info->getId());
        // $input->SetTotal_fee(50);
        $input->SetTotal_fee(500*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('车辆分时租赁服务');
        $input->SetNotify_url($req->server->get('HTTP_HOST').$this->generateUrl("auto_api_deposit_wechat_pay_notify"));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $wechat_order = WxPayApi::unifiedOrder($input);
        $jsApiParametersArr = $tools->GetJsApiParameters($wechat_order);


        $editAddress = $tools->GetEditAddressParameters();

        // 是否已经缴纳押金
        return [
            'editAddress'       =>$editAddress,
            'jsApiParametersArr'=>$jsApiParametersArr,
            'order'             =>$info];
    }



    /**
     * @Route("/wechat/recharge.html", methods="GET",name="auto_mobile_pay_wechat_recharge")
     *@Template()
     */
    public function wechatRechargeAction(Request $req){

        $amount = $req->query->get('amount');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_recharge_new_order'),
            ['userID' => $this->getUser()->getToken(),"amount"=>$amount]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        $this->get("auto_manager.logs_helper")->addWeChatPayLog("------".$data['rechargeOrderID']);
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();


//②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智慧-租充值");
        $input->SetAttach($this->getUser()->getId());
        $input->SetOut_trade_no(date("YmdHis").$data["rechargeOrderID"]);
        // $input->SetTotal_fee(1);
        $input->SetTotal_fee($amount*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('车辆分时租赁服务');
        $input->SetNotify_url($req->server->get('HTTP_HOST').$this->generateUrl("auto_api_wechat_charge_pay_notify"));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $wechat_order = WxPayApi::unifiedOrder($input);

        $jsApiParametersArr = $tools->GetJsApiParameters($wechat_order);
        $editAddress = $tools->GetEditAddressParameters();
        return ['editAddress'=>$editAddress,
            'jsApiParametersArr'=>$jsApiParametersArr];
    }
}

