<?php
/**
 * Created by PhpStorm.
 * User: Tau
 * Date: 2016/12/18
 * Time: 下午2:56
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class ChangJingHelper {

    const cjCompanyID = 797853;
    const cjSecret = "k9rpsMeOAQomVsQM1LgC";

    function IDCardOCR($file){

        $url = "http://auth.context.cn/cjService/service/api/v12/ocrIdentity";
        $cfile = new \CURLFile($file,'jpeg');

        $data = ["cjCompanyID"=>self::cjCompanyID,"cjApiID"=>60016,"cjSecret"=>self::cjSecret,"photo"=>$cfile];
        $result = $this->curlHelper->do_post($url,$data);
        return json_decode($result,true);
    }


    function DriverOCR($file){

        $url = "http://auth.context.cn/cjService/service/api/v9/ocrDriver";
        $cfile = new \CURLFile($file,'jpeg');

        $data = ["cjCompanyID"=>self::cjCompanyID,"cjApiID"=>60010,"cjSecret"=>self::cjSecret,"photo"=>$cfile];
        $result = $this->curlHelper->do_post($url,$data);
        return json_decode($result,true);
    }



    public function phone($name,$mobile,$IDnumber){

        $name = urlencode($name);
        $url = "http://auth.context.cn/cjService/service/api/v14/phone3";

        $secret = md5(self::cjCompanyID."|60018|".$name.'|'.$mobile.'|'.$IDnumber."|001|".self::cjSecret);
        $data = ['name'=>$name,'phoneNumber'=>$mobile,'idNumber'=>$IDnumber,'cjOrderID'=>'001','cjCompanyID'=>self::cjCompanyID,'cjApiID'=>'60018','cjSecret'=>$secret];

        $result = $this->do_post($url,$data);
        return json_decode($result,true);
    }


    public function crime($name,$mobile,$IDnumber){

        $name = urlencode($name);
        $url = "http://auth.context.cn/cjService/service/api/v3/crime";

        $secret = md5(self::cjCompanyID."|60006|".$name."|".$mobile."|".$IDnumber."|001|".self::cjSecret);

        $requestParsam = ['name'=>$name,'phoneNumber'=>$mobile,'idNumber'=>$IDnumber,'cjOrderID'=>'001','cjCompanyID'=>self::cjCompanyID,'cjApiID'=>'60006','cjSecret'=>$secret];

        $result = $this->do_post($url,$requestParsam);
        return json_decode($result,true);

    }



    public function driver($name,$IDnumber,$province,$city){

        $name = urlencode($name);
        $url = "http://auth.context.cn/cjService/service/api/v4/driver";

        $secret = md5(self::cjCompanyID."|60007|".$name."|".$IDnumber."|".$province."|".$city."|001|".self::cjSecret);
        $requestParsam = ['name'=>$name,'idNumber'=>$IDnumber,'province'=>$province,'city'=>$city,'cjOrderID'=>'001','cjCompanyID'=>self::cjCompanyID,'cjApiID'=>'60007','cjSecret'=>$secret];

        $result = $this->do_post($url,$requestParsam);
        return json_decode($result,true);
    }


    public function IDnumber($name,$IDnumber){

        $name = urlencode($name);
        $url = "http://auth.context.cn/cjService/service/api/v10/identityCk";

        $secret = md5(self::cjCompanyID."|60012|".$name.'|'.$IDnumber."|001|".self::cjSecret);
        $data = ['name'=>$name,'idNumber'=>$IDnumber,'cjOrderID'=>'001','cjCompanyID'=>self::cjCompanyID,'cjApiID'=>'60012','cjSecret'=>$secret];

        $result = $this->do_post($url,$data);
        return json_decode($result,true);
    }



    function do_post($url, $data)
    {
        $header = array(
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }


    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }


}