<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/9
 * Time: 下午4:04
 */

namespace Auto\Bundle\ManagerBundle\Helper;

use Auto\Bundle\ManagerBundle\Dayu\top\TopClient;
use Auto\Bundle\ManagerBundle\Dayu\top\request\AlibabaAliqinFcSmsNumSendRequest;

class SMSHelper extends AbstractHelper{

    private $appkey = "23487586";
    private $secretKey = "1dc2035c50760b6435348a586f72dfad";


    public function sendCodeSMS($mobile,$code)
    {

        $c = new TopClient($this->appkey,$this->secretKey);

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("云杉智慧");
        $req->setSmsParam(json_encode(['code'=>"$code"]));
        $req->setRecNum("$mobile");
        $req->setSmsTemplateCode("SMS_26280226");
        $resp = $c->execute($req);

        return $resp;

    }

    public function rentalSMS($mobile,$license,$station)
    {

        $c = new TopClient($this->appkey,$this->secretKey);

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("云杉智慧");
        $req->setSmsParam(json_encode(['license'=>"$license",'station'=>$station]));
        $req->setRecNum("$mobile");
        $req->setSmsTemplateCode("SMS_34055002");
        $resp = $c->execute($req);

        return $resp;

    }



    public function cancelRentalSMS($mobile)
    {

        $c = new TopClient($this->appkey,$this->secretKey);

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("云杉智慧");
        $req->setRecNum("$mobile");
        $req->setSmsTemplateCode("SMS_26110128");
        $resp = $c->execute($req);

        return $resp;

    }

    public function authSuccessSMS($mobile)
    {

        $c = new TopClient($this->appkey,$this->secretKey);

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("云杉智慧");
        $req->setRecNum("$mobile");
        $req->setSmsTemplateCode("SMS_26250330");
        $resp = $c->execute($req);

        return $resp;

    }

    public function authFailedSMS($mobile)
    {

        $c = new TopClient($this->appkey,$this->secretKey);

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("云杉智慧");
        $req->setRecNum("$mobile");
        $req->setSmsTemplateCode("SMS_26265181");
        $resp = $c->execute($req);

        return $resp;

    }


    public function send($mobile, $message)
    {
        $url = "http://ms.go.le.com/service/message?usr=lsyd-0201-js-01&pwd=".substr(md5("23f85s20"),8,16)."&ext=&to=".$mobile."&msg=".urlencode($message);

        return $this->get_url_contents($url);

    }

    public function add($mobile, $message)
    {
        $sms = new \Auto\Bundle\ManagerBundle\Entity\SMS();
        $sms->setMessage($message)
            ->setMobile($mobile);

        $man = $this->em;
        $man->persist($sms);
        $man->flush();

    }

    function get_url_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1")
            return file_get_contents($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result =  curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}