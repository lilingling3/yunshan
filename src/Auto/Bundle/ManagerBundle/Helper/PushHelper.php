<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/12/31
 * Time: 下午4:18
 */

namespace Auto\Bundle\ManagerBundle\Helper;

use Auto\Bundle\ManagerBundle\PushMessage\XingeApp;
use Auto\Bundle\ManagerBundle\PushMessage\XinApp;

class PushHelper extends AbstractHelper{

    //订单消息 1
    //优惠券 2
    //违章 3
    //用户退出 4
    //用户认证 5
    //活动h5页面 6
    //租赁点 7
    //活动列表 8

    const ANDROID_ACCESS_ID = 2100244438;
    const ANDROID_SECRET_KEY = "630d9ac860aa37b6506908799df176e7";

    const IOS_ACCESS_ID = 2200244439;
    const IOS_SECRET_KEY = "519569e0dacb163d29ea60d4a3a1a1ae";

    public function pushIOS(){

        $dump = XingeApp::PushTokenIos(self::IOS_ACCESS_ID,self::IOS_SECRET_KEY, "干啥嘞", "c72ea3078e6127024d02b8edb6a697d5647b612ccf2c2536bc4b47a51682a7e0",XingeApp::IOSENV_DEV,['subject'=>'6', 'link'=>'http://baidu.com']);
        $dump = XingeApp::PushTokenAndroid(self::ANDROID_ACCESS_ID,self::ANDROID_SECRET_KEY, "消息", "干啥嘞", "c5bb9094338022534e22a69bc15f628fc60d7269",['subject'=>'6', 'link'=>'http://baidu.com']);

    }

    public function pushTokenIos($message,$token,$custom){

        if($this->container->getParameter('development_environment')=='dev'){
            $environment = XingeApp::IOSENV_DEV;
        }else{
            $environment = XingeApp::IOSENV_PROD;
        }

        return XingeApp::PushTokenIos(self::IOS_ACCESS_ID,self::IOS_SECRET_KEY, $message,$token,$environment,$custom);
    }

    public function pushTokenAndroid($message,$token,$custom){

        XingeApp::PushTokenAndroid(self::ANDROID_ACCESS_ID,self::ANDROID_SECRET_KEY,'消息',$message,$token,$custom);

    }

}