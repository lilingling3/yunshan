<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayRefund;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayApi;
use Auto\Bundle\ManagerBundle\Payment\WeChatAppPay\WxPayConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RefundRecord;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/zhimaxinyong")
 */
class ZhimaXinyongController extends BaseController
{
    private $gatewayUrl = "https://zmopenapi.zmxy.com.cn/openapi.do";
    private $appId = "1003047";
    private $charset = "utf-8";
    private $privateKeyFile = "/data/keys/zhima/zhimaxinyong_private_key.pem";
    private $zmPublicKeyFile = "/data/keys/zhima/zhimaxinyong_public_key.pem";
    //验证方式 1：手机号 + 身份证后四位 2：身份证 + 姓名
    private $identityType = 2;
    //用户等级判断
    private $levelStart = 350;
    //E级
    private $levelE = 550;
    //D级
    private $levelD = 600;
    //C级
    private $levelC = 650;
    //B级
    private $levelB = 700;
    //A级
    private $levelA = 950;

    //授权芝麻信用
    /**
     * @Route("/authorization/{member_id}", methods="GET")
     */
    public function authorizationAction($member_id)
    {
        //查找是否存在此用户
        $Member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($member_id)
        ;
        if(empty($Member)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_THIS_MEMBER_ON_ZHIMAXINYONG,
                'code' => self::M_NO_THIS_MEMBER_ON_ZHIMAXINYONG
            ]);
        }
        $AuthMember = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(["member"=>$Member]);
        ;
        if(empty($AuthMember)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_THIS_AUTH_MEMBER_ON_ZHIMAXINYONG,
                'code' => self::M_NO_THIS_AUTH_MEMBER_ON_ZHIMAXINYONG
            ]);
        }
        //姓名
        $name = !empty($Member->getName())?$Member->getName():'';
        //身份证号
        $idCard = !empty($AuthMember->getIdnumber())?$AuthMember->getIdnumber():'';
        //手机号
        $mobile = !empty($Member->getMobile())?$Member->getMobile():'';

        //初次验证用户信息，根据授权方式
        if(1 == $this->identityType){
            if(empty($mobile)){
                return new JsonResponse([
                    'errorCode'=> self::E_NO_MOBILE_AUTH_MEMBER_ON_ZHIMAXINYONG,
                    'code' => self::M_NO_MOBILE_AUTH_MEMBER_ON_ZHIMAXINYONG
                ]);
            }
        }
        if(2 == $this->identityType){
            if(empty($idCard || empty($name))){
                return new JsonResponse([
                    'errorCode'=> self::E_NO_NAME_OR_IDNUMBER_MEMBER_ON_ZHIMAXINYONG,
                    'code' => self::M_NO_NAME_OR_IDNUMBER_MEMBER_ON_ZHIMAXINYONG
                ]);
            }
        }
        //实例化连接芝麻信用的类
        $zhiMaSdkHome = dirname(dirname(dirname(__FILE__)))
            . DIRECTORY_SEPARATOR . "ManagerBundle"
            . DIRECTORY_SEPARATOR . "Zhima"
            . DIRECTORY_SEPARATOR . "zmop" . DIRECTORY_SEPARATOR;

        include($zhiMaSdkHome . "/ZmopClient.php");
        include($zhiMaSdkHome . "/request/ZhimaAuthInfoAuthorizeRequest.php");

        $client = new \ZmopClient($this->gatewayUrl,$this->appId,$this->charset,$this->privateKeyFile,$this->zmPublicKeyFile);
        $request = new \ZhimaAuthInfoAuthorizeRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        if(1 == $this->identityType){
            $request->setIdentityType("1");// 必要参数
            $request->setIdentityParam("{\"mobileNo\":\"$mobile\"}");
        }elseif(2 == $this->identityType){
            $request->setIdentityType("2");// 必要参数
            $request->setIdentityParam("{\"name\":\"$name\",\"certType\":\"IDENTITY_CARD\",\"certNo\":\"$idCard\"}");// 必要参数
        }
        $request->setBizParams("{\"auth_code\":\"M_H5\",\"channelType\":\"app\",\"state\":\"$member_id\"}");//
        $url = $client->generatePageRedirectInvokeUrl($request);
        header("Location: $url");

        return new JsonResponse([
            'errorCode'=> self::E_OK,
            'code' => ''
        ]);

    }

    //芝麻信用回调
    /**
     * @Route("/zhimaxinyongCallback", methods="GET")
     */
    public function zhimaxinyongCallbackAction()
    {
        //实例化连接芝麻信用的类
        $zhiMaSdkHome = dirname(dirname(dirname(__FILE__)))
            . DIRECTORY_SEPARATOR . "ManagerBundle"
            . DIRECTORY_SEPARATOR . "Zhima"
            . DIRECTORY_SEPARATOR . "zmop" . DIRECTORY_SEPARATOR;

        include($zhiMaSdkHome . "/ZmopClient.php");
        include($zhiMaSdkHome . "/request/ZhimaAuthInfoAuthorizeRequest.php");
        include($zhiMaSdkHome . "/request/ZhimaCreditScoreGetRequest.php");

        $params = isset($_GET['params'])?$_GET['params']:null;
        $sign = isset($_GET['sign'])?$_GET['sign']:null;

        if(empty($params) || empty($sign)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_THIS_PARAMS_OR_SINE_ON_ZHIMAXINYONG,
                'code' => self::M_NO_THIS_PARAMS_OR_SINE_ON_ZHIMAXINYONG
            ]);
        }
        //解密数据
        $params = strstr ( $params, '%' ) ? urldecode ( $params ) : $params;
        $sign = strstr ( $sign, '%' ) ? urldecode ( $sign ) : $sign;

        $client = new \ZmopClient ( $this->gatewayUrl, $this->appId, $this->charset, $this->privateKeyFile, $this->zmPublicKeyFile );
        //数据字符串
        $result = $client->decryptAndVerifySign ( $params, $sign );
        //分割字符串取得数据
        $arr = explode('&',$result);
        //var_dump($arr);die;
        //判断是否查询成功
        $successOrFailure = '';
        foreach($arr as $k=>$v){
            if(0 === strpos($v,'success')){
                $successOrFailure = substr($v, strpos($v,'=')+1);
            }
        }
        //如果查询失败：信息不正确
        if('false' == $successOrFailure){
            return new JsonResponse([
                'errorCode'=> self::E_ZHIMAXINYONG_FAILED,
                'code' => self::M_ZHIMAXINYONG_FAILED
            ]);
        }
        //获取查询芝麻信用分数的openId
        $openId = '';
        foreach($arr as $k=>$v){
            if(0 === strpos($v,'open_id')){
                $openId = substr($v, strpos($v,'=')+1);
            }
        }
        //获取用户的member_id
        $memberId = '';
        foreach($arr as $k=>$v){
            if(0 === strpos($v,'state')){
                $memberId = substr($v, strpos($v,'=')+1);
            }
        }
        //transactionId ,唯一标识
        $transactionId = $this->createUuid();
        //查询分数的类
        $request = new \ZhimaCreditScoreGetRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $request->setTransactionId("$transactionId");
        $request->setProductCode("w1010100100000000001");
        $request->setOpenId("$openId");
        $response = $client->execute($request);

        //芝麻信用biz_no,对账
        $bizNo = $response ->biz_no;
        $zmScore = $response->zm_score;
        //var_dump($bizNo,$zmScore);die;
        //芝麻无法评分时，返回 N/A
        if('N/A' == $zmScore){
            return new JsonResponse([
                'errorCode'=> self::E_GET_SCORE_FAILED,
                'code' => self::M_GET_SCORE_FAILED
            ]);
        }
        //根据分数判断用户等级

        if($this->levelStart <= $zmScore && $zmScore <= $this->levelE){
            $level = 'E';
        }elseif($this->levelE <= $zmScore && $zmScore < $this->levelD){
            $level = 'D';
        }elseif($this->levelD <= $zmScore && $zmScore < $this->levelC){
            $level = 'C';
        }elseif($this->levelC <= $zmScore && $zmScore < $this->levelB){
            $level = 'B';
        }elseif($this->levelB <= $zmScore && $zmScore < $this->levelA){
            $level = 'A';
        }
        //存数据到数据表
        //var_dump($memberId,$zmScore,$level,$bizNo);die;
        $ZhimaXinyong = new \Auto\Bundle\ManagerBundle\Entity\ZhimaXinyong();
        $ZhimaXinyong->setMemberId($memberId);
        $ZhimaXinyong->setZmScore($zmScore);
        $ZhimaXinyong->setLevel($level);
        $ZhimaXinyong->setBizNo($bizNo);

        $man = $this->getDoctrine()->getManager();
        $man->persist($ZhimaXinyong);
        $man->flush();

        return new JsonResponse([
            'errorCode'=> self::E_OK,
            'message' => $response,
            'level' => $level
        ]);

    }

    //UUID,可以指定前缀
    public function createUuid($prefix = "")
    {
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,4) . '-';
        $uuid .= substr($str,8,2) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }

}