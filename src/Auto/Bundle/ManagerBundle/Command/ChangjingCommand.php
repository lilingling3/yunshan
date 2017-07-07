<?php
/**
 * Created by PhpStorm.
 * User: Tau
 * Date: 2016/12/8
 * Time: 下午9:09
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ChangjingCommand extends ContainerAwareCommand{

    const CHANG_JING_CODE = "797853";
    const CHANG_JING_KEY = "k9rpsMeOAQomVsQM1LgC";

    public function configure()
    {
        $this
            ->setName('auto:chang:jing')
            ->setDescription('add color')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        var_dump($this->phone());exit;









    }





    function request_post($url, $post_data) {

        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    function requestPost($url = '', $data = '') {
        $data_string = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);
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


    public function idNumber(){



    }


    public function phone(){

        $name = urlencode("徐虎");
        $mobile = "15652669326";
        $IDnumber = "370921198709234519";
        $url = "http://auth.context.cn/cjService/service/api/v14/phone3";


        $secret = md5(self::CHANG_JING_CODE."|60018|".$name.'|'.$mobile.'|'.$IDnumber."|001|".self::CHANG_JING_KEY);
        $requestParam = ['name'=>$name,'phoneNumber'=>$mobile,'idNumber'=>$IDnumber,'cjOrderID'=>'001','cjCompanyID'=>self::CHANG_JING_CODE,'cjApiID'=>'60018','cjSecret'=>$secret];

        return  $this->do_post($url,$requestParam);


    }


    public function record(){

        $name = urlencode("徐虎");
        $mobile = "15652669326";
        $IDnumber = "370921198709234519";
        $url = "http://auth.context.cn/cjService/service/api/v3/crime";


        $secret = md5(self::CHANG_JING_CODE."60006".$name.$mobile.$IDnumber."001".self::CHANG_JING_KEY);

        $requestParsam = ['name'=>$name,'phoneNumber'=>$mobile,'idNumber'=>$IDnumber,'cjOrderID'=>'001','cjCompanyID'=>self::CHANG_JING_CODE,'cjApiID'=>'60006','cjSecret'=>$secret];

        return  $this->do_post($url,$requestParsam);


    }

    public function driver(){

        $name = urlencode("徐虎");
        $IDnumber = "370921198709234519";
        $url = "http://auth.context.cn/cjService/service/api/v4/driver";
        $province = urlencode("北京市");
        $city=urlencode("北京市");


        $secret = md5(self::CHANG_JING_CODE."60007".$name.$IDnumber.$province.$city."001".self::CHANG_JING_KEY);

        $requestParsam = ['name'=>$name,'idNumber'=>$IDnumber,'province'=>$province,'city'=>$city,'cjOrderID'=>'001','cjCompanyID'=>self::CHANG_JING_CODE,'cjApiID'=>'60007','cjSecret'=>$secret];

        return  $this->do_post($url,$requestParsam);


    }

}




















