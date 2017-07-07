<?php

namespace Auto\Bundle\WapBundle\Controller;

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
 * @Route("/pay2")
 */
class Pay2Controller extends Controller
{

    /**
     * @Route("/wechat/order_{id}.html", methods="GET",name="auto_wap_pay_order_fee2")
     *@Template()
     */
    public function wechatOrderAction(Request $req,$id){

        $coupon_id = $req->query->getInt('couponID');
        $wallet = $req->query->getInt('useWalletAmount');

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


        $pay_amount = ($order['costDetail']['cost'] - $coupon_amount)>0?($order['costDetail']['cost'] -
            $coupon_amount):0;


        if($pay_amount == 0){

            $rentalOrder->setPayTime(new \DateTime());
            $rentalOrder->setAmount(0);
            $coupon = $rentalOrder->getCoupon();
            if(!empty($coupon)){
                $rentalOrder->getCoupon()->setUseTime(new \DateTime());
            }

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalOrder);
            $man->flush();

            return $this->redirect($this->generateUrl("auto_wap_rental_order_show2",['id'=>$rentalOrder->getId()]));

        }





        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

//②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("云杉智慧-租赁费用");
        $input->SetAttach($rentalOrder->getMember()->getId());
        $input->SetOut_trade_no(date("YmdHis").$rentalOrder->getId());
        // $input->SetTotal_fee(1);
        $input->SetTotal_fee($pay_amount*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag('车辆分时租赁服务');
        $input->SetNotify_url($req->server->get('HTTP_HOST').$this->generateUrl("auto_api_wechat_pay_notify"));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $wechat_order = WxPayApi::unifiedOrder($input);
        $jsApiParametersArr = $tools->GetJsApiParameters($wechat_order);

//获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
        //  $signPackage = $this->get("auto_manager.wechat_helper")->getSignPackage();

        return ['editAddress'=>$editAddress,
            'jsApiParametersArr'=>$jsApiParametersArr,'order'=>$order];
    }

    /**
     * @Route("/rental/order", methods="POST",name="auto_wap_pay_rental_order2")
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

                return $this->redirect($this->generateUrl('auto_wap_rental_order_show2',['id'=>$order->getId()]));

            }else{

                return $this->render(
                    "AutoWapBundle:Default:message.html.twig",
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


}
