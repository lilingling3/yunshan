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


//use Greedying\Zhima\Foundation\Application; 芝麻信用的sdk,目前使用的是支付宝的sdk

/**
 * @Route("/zhima")
 */
class ZhiMaController extends BaseController
{
    private $gatewayUrl = "https://openapi.alipay.com/gateway.do";
    private $appId = "2016121604322084"; //永远不变
    private $rsaPrivateKeyFilePath = "/data/keys/zhima/rsa_pri_key.pen";
    private $format = "json";
    private $charset = "UTF-8";
    private $signType = "RSA2";
    private $alipayrsaPublicKeyPath = "/data/keys/zhima/zhima_rsa_public_key.pen";
    private $apiVersion = "1.0";

    //授权芝麻信用
    /**
     * @Route("/authorization", methods="GET")
     */
    public function authorizationAction()
    {
        //Appid是应用id
        $appId = $this->appId;
        //要授权的应用
        $scope = 'auth_zhima';
        //回调的方法
        $redirectUri = 'https://gotest.win-sky.com.cn/api/zhima/zhimacallback';
        echo "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=$appId&scope=$scope&redirect_uri=$redirectUri";die;
        header("Location: https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=$appId&scope=$scope&redirect_uri=$redirectUri");

        return new JsonResponse([
            'errorCode'=> self::E_OK,
            'code' => ''
        ]);
    }

    //获取芝麻信用分数
    /**
     * @Route("/getzhimazcore/{member_id}", methods="GET")
     */
    public function getZhimaScoreAction($member_id)
    {
        //实例化member表
        $Member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($member_id)
        ;
        if(empty($Member)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_THIS_MEMBER_ON_ZHIMA,
                'code' => self::M_NO_THIS_MEMBER_ON_ZHIMA
            ]);
        }
        $AuthMember = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(["member"=>$Member]);
        ;
        if(empty($AuthMember)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_THIS_AUTH_MEMBER_ON_ZHIMA,
                'code' => self::M_NO_THIS_AUTH_MEMBER_ON_ZHIMA
            ]);
        }
        //请求唯一标识,transactionId
        $transactionId = $this->createUuid();
        //身份证号
        $idCard = $AuthMember->getIdnumber();
        if(empty($idCard)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_IDNUMBER_AUTH_MEMBER_ON_ZHIMA,
                'code' => self::M_NO_IDNUMBER_AUTH_MEMBER_ON_ZHIMA
            ]);
        }
        //姓名
        $name = $Member->getName();
        if(empty($name)){
            return new JsonResponse([
                'errorCode'=> self::E_NO_NAME_MEMBER_ON_ZHIMA,
                'code' => self::M_NO_NAME_MEMBER_ON_ZHIMA
            ]);
        }
        //分数段
        $admittanceScore = ":650";
        //实例化连接芝麻信用的类
        $aliPaySdkHome = dirname(dirname(dirname(__FILE__)))
            . DIRECTORY_SEPARATOR . "ManagerBundle"
            . DIRECTORY_SEPARATOR . "Payment"
            . DIRECTORY_SEPARATOR . "AliPaySdk" . DIRECTORY_SEPARATOR;

        include($aliPaySdkHome . "AopSdk.php");
        $c = new \AopClient ();

        $c->gatewayUrl = $this->gatewayUrl;
        $c->appId = $this->appId;
        $c->rsaPrivateKeyFilePath = $this->rsaPrivateKeyFilePath;
        $c->alipayrsaPublicKey = file_get_contents("$this->alipayrsaPublicKeyPath");
        $c->apiVersion = $this->apiVersion;
        $c->signType = $this->signType;
        $c->postCharset= $this->charset;
        $c->format= $this->format;

        $request = new \ZhimaCreditScoreBriefGetRequest ();

        $request->setBizContent("{" .
            "    \"transaction_id\":\"$transactionId\"," .
            "    \"product_code\":\"w1010100000000002733\"," .
            "    \"cert_type\":\"IDENTITY_CARD\"," .
            "    \"cert_no\":\"$idCard\"," .
            "    \"name\":\"$name\"," .
            "    \"admittance_score\"$admittanceScore" .
            "  }");

        $result = $c->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            //成功状态
            $isAdmittance = $result->zhima_credit_score_brief_get_response->is_admittance;
            $bizNo = $result->zhima_credit_score_brief_get_response->biz_no;
            //判断数据表是否已保存该用户的芝麻信用
            $zhimaSave = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Zhima')
                ->findBy(["memberId"=>$member_id]);
            if(empty($zhimaSave)){
                //实例化zhima表
                $Zhima = new \Auto\Bundle\ManagerBundle\Entity\Zhima();
                $Zhima->setMemberId($member_id);
                $Zhima->setIsAdmittance($isAdmittance);
                $Zhima->setBizNo($bizNo);

                $man = $this->getDoctrine()->getManager();
                $man->persist($Zhima);
                $man->flush();
            }

            return new JsonResponse([
                'errorCode'=> self::E_OK,
                'message' => $result
            ]);
        }
        return new JsonResponse([
            'errorCode'=> self::E_ZHIMA_FAILED,
            'code' => self::M_ZHIMA_FAILED,
            'message' => $result
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