<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/rentalCar")
 */
class RentalCarController extends Controller
{

    /**
     * @Route("/list/{sid}/{page}", methods="GET", name="auto_mobile_rental_car_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($sid,$page){

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_get_cars_by_station'),
            ['stationID'=>$sid,"page"=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();
            return [
                'rentalCars'  => $data["rentalCars"],
                'rentalStation'=> $data["rentalStation"],
                'signPackage'=>$signPackage
            ];


    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_mobile_rental_car_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id,Request $req)
    {
        $backSid=$req->query->get('backSid');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_rentalCar_choose'),
            ['rentalCarID'=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        if(empty($backSid)){
            $backStation=$data["rentalCar"]["rentalStation"];
        }
        else {
            $backStation=   $car = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->find($backSid);
            $backStation=call_user_func($this->get('auto_manager.station_helper')->get_rental_car_normalizer()
                ,$backStation);
        }

        return [
            'rentalCar'  => $data["rentalCar"],
            "backStation"=>$backStation
        ];
    }

    /**
     * @Route("/show/back", methods="POST", name="auto_mobile_rental_car_show_back")
     * @Template("AutoMobileBundle:RentalCar:show.html.twig")
     */
    public function showBackAction(Request $req)
    {
        $id=$req->request->get('rentalCarID');
        $backSid=$req->request->get('backSid');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_rentalCar_choose'),
            ['rentalCarID'=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        if(empty($backSid)){
            $backStation=$data["rentalCar"]["rentalStation"];
        }
        else {
            $backStation=   $car = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->find($backSid);
            $backStation=call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer()
                ,$backStation);
        }
        return [
            'rentalCar'  => $data["rentalCar"],
            "backStation"=>$backStation
        ];
    }

    /**
     * @Route("/order", methods="POST", name="auto_mobile_rental_car_order")
     * @Template()
     */
    public function orderAction(Request $req)
    {
        $rentalCarID = $req->request->get('rentalCarID');
        $returnStationID = $req->request->get('returnStationID');
        $rentalstatID = $req->request->get('rentalStationID');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_rental_car_order'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$rentalCarID,"returnStationID"=>$returnStationID,"source"=>3,"rentalStationID"=>$rentalstatID]);
        $data = json_decode($post_json,true);
        if($data['errorCode']==-180001){
            return $this->render(
                "AutoMobileBundle:Default:depositMessage.html.twig",
                ['message' => $data['errorMessage']]);
        }

        if($data['errorCode']==-180005){
            return $this->render(
                "AutoMobileBundle:Default:rechargeMessage.html.twig",
                ['message' => $data['errorMessage']]);
        }

        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return $this->redirect($this->generateUrl('auto_mobile_rental_order_show',
            ['id'=>$data['order']['orderID']]));
    }
    /**
     * @Route("/question/{id}", methods="GET", name="auto_mobile_rental_car_question",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function questionAction($id)
    {

        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();
        $AccessToken = $this->get('auto_manager.wechat_helper')->getAccessToken();
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_qiniu_getuploadtoken'),
            ['bucket_id'=>1]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        $problems=array();
        foreach($data["problem"] as $key=>$value ){
            if( $key%2 ===1 ){
                $problems[ceil( $key/2 )]=array();
            }
            $problems[ceil( $key/2 )][]=[
                "id"=>$key,
                "name"=>$value
            ];
        }
        return [
            "signPackage"=>$signPackage,
            "AccessToken"=>$AccessToken,
            "problems"=>$problems,
            "orderId"=>$id,
            "uptoken"=>$data["uptoken"]
        ];
    }

    /**
     * @Route("/question/upload", methods="POST", name="auto_mobile_rentalCar_upload")
     * @Template()
     */
    public function questionUploadAction(Request $req)
    {
        $orderId=$req->request->get("orderId");
        $questions=$req->request->get("questions");
        $uptoken=$req->request->get("uptoken");
        $mediaIds = explode(',',$req->request->get("images"));

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$this->getUser()->getToken()]);
        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$orderId,'member'=>$member]);
        if(empty($order) || !empty($order->getUseTime())){  //订单合法性
            return $this->render(
                "AutoMobileBundle:Default:depositMessage.html.twig",
                ['message' => '非法订单！']);
        }

        $imgtempName="pin".time().$this->getUser()->getToken().".jpeg";

        $accessToken=$this->get('auto_manager.wechat_helper')->getAccessToken();
        $imgurl="http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$accessToken."&media_id=";
        $imagsArr=array();
        //从微信服务器获取图片
        foreach($mediaIds as $value){
            $r=file_get_contents($imgurl.$value);
            if($r[0]=="{"){
                return $this->render(
                    "AutoMobileBundle:Default:depositMessage.html.twig",
                    ['message' => '获取图片失败，稍后重试！']);
            }else{
                $image=Imagecreatefromjpeg($imgurl.$value);
                $imagsArr[]=[
                    "source"=> $image,
                    "size"=>getimagesize($imgurl.$value)
                ];
            }
        }

        $target_img = imagecreatetruecolor($imagsArr[0]["size"][0]*count($imagsArr),$imagsArr[0]["size"][1]);
        $tmpx=0;
        //合并图片
        for($i = 0; $i < count($imagsArr); $i++){
            $result=imagecopy($target_img, $imagsArr[$i]['source'], $tmpx, 0, 0, 0, $imagsArr[$i]['size'][0], $imagsArr[$i]['size'][1]);
            $tmpx+=$imagsArr[$i]['size'][0];
          if(!$result){
              return $this->render(
                  "AutoMobileBundle:Default:depositMessage.html.twig",
                  ['message' => '失败，稍后重试！']);
          }
        }

        imagejpeg($target_img,'temp/'.$imgtempName,100);
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $baseUrl = "http://gotest.win-sky.com.cn";
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
             ('auto_api_qiniu_upload'),
             ["fileName"=>$imgtempName,"question"=>$questions,"imageLabel"=>$orderId,'uptoken'=>$uptoken]);
        $data = json_decode($post_json,true);

        imagedestroy($target_img);
        unlink('temp/'.$imgtempName);
        if($data["errorCode"]!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        else
            return $this->redirect($this->generateUrl('auto_mobile_rental_order_show',
                ['id'=>$orderId]));

    }



}