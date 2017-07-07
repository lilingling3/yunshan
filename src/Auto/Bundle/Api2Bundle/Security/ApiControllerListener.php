<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/6/3
 * Time: 下午5:01
 */

namespace Auto\Bundle\Api2Bundle\Security;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ApiControllerListener {

    public function onKernelController(FilterControllerEvent $event){

        $controller = $event->getController();
        $controller = $controller[0];
        $new = new \ReflectionObject($controller);


        $controller_array = [
            'Auto\Bundle\Api2Bundle\Controller\AccountController',
            'Auto\Bundle\Api2Bundle\Controller\ActivityController',
            'Auto\Bundle\Api2Bundle\Controller\CouponController',
            'Auto\Bundle\Api2Bundle\Controller\DefaultController',
            'Auto\Bundle\Api2Bundle\Controller\IllegalRecordController',
            'Auto\Bundle\Api2Bundle\Controller\InvoiceController',
            'Auto\Bundle\Api2Bundle\Controller\MessageController',
//            'Auto\Bundle\Api2Bundle\Controller\OrderController',
            'Auto\Bundle\Api2Bundle\Controller\PayController',
            'Auto\Bundle\Api2Bundle\Controller\PaymentController',
            'Auto\Bundle\Api2Bundle\Controller\RechargeController',
            'Auto\Bundle\Api2Bundle\Controller\RemindController',
            'Auto\Bundle\Api2Bundle\Controller\RentalCarController',
            'Auto\Bundle\Api2Bundle\Controller\StationController',
            'Auto\Bundle\Api2Bundle\Controller\DepositController',
            'Auto\Bundle\Api2Bundle\Controller\AreaController',
            'Auto\Bundle\Api2Bundle\Controller\InviteController',
        ];

        if(in_array($new->getName(),$controller_array))
        {

            $uid = $event->getRequest()->headers->get('userID');
            $post_data = $token = $event->getRequest()->request->getIterator();

            $post_array = [];

            foreach($post_data as $key=>$val){
                $post_array[$key] = $val;
            }

            if(!$uid){

                $kString =  substr(md5('jiabei'),8, 16);

            }else{
                $kString = substr($event->getRequest()->headers->get('userID'),8, 16);
            }

            $key = $kString.$post_array['timestamp'];

            $sign=$post_array['sign'];

            unset($post_array['sign']);

            $useSign= $this->getSign($post_array, $key);

            
            if ($sign != $useSign) {
                throw new UnauthorizedHttpException('This action needs a valid token!');

            }


        }

    }


    /**
     * 获得签名
     */
    public function getSign($dataArr,$key){

        krsort($dataArr);

        $queryStr = '';

        foreach ($dataArr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }

        $resultStr=$queryStr.$key;

        // 拼接签名串
        $signature =urlencode($resultStr);

        // md5加密

        $signValue =md5($signature);

        return $signValue;
    }
}