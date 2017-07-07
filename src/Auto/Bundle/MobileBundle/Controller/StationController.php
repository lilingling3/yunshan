<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
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
 * @Route("/station")
 */
class StationController extends Controller{
	 /**
     * @Route("", methods="POST", name="auto_mobile_station_list")
     * 
     * @Template()
     */
    public function listAction(Request $req){


        $lng = $req->request->get('lng');
        $lat = $req->request->get('lat');
        $address = $req->request->get('address');
       $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_search_by_coordinate'),
            ['latitude'=>$lat,"longitude"=>$lng]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
    	 return [ 'stations'=>$data["stations"],"lng"=>$lng,"lat"=>$lat,"address"=>$address];

    }


    /**
     * @Route("/back", methods="POST", name="auto_mobile_back_station",
     * requirements={"id"="\d+"})
     * @Template()
     */

    public function backStationAction(Request $req)
    {
        $orderID=$req->request->get('orderID');
        $backSid = $req->request->get('backSid');
        $rentalCarID=$req->request->get("rentalCarID");
        $rentalCarID = $req->request->get('rentalCarID');
        $backStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($backSid);
        $s=call_user_func($this->get('auto_manager.station_helper')
        ->get_station_normalizer(),
        $backStation);


        return ["backStation"=>call_user_func($this->get('auto_manager.station_helper')
            ->get_station_normalizer(),
            $backStation),"rentalCarID"=>$rentalCarID,"orderID"=>$orderID];
    }


    /**
     * @Route("/map/{sid}", methods="GET", name="auto_mobile_station_map",
     * requirements={"id"="\d+"})
     * @Template()
     */

    public function mapAction( $sid )
    {
        $station =
            $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->find($sid);
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return [
            'station'=>call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer()
                ,$station),'signPackage'=>$signPackage,'url'=>$url
        ];
    }

}