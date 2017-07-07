<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/rentalCar2")
 */
class RentalCar2Controller extends Controller
{
    /**
     * @Route("/rentalCar", methods="POST", name="auto_wap_rentalCar1")
     * @Template()
     */
    public function rentalCarAction(Request $req)
    {
        $lng = $req->request->get('lng');
        $lat = $req->request->get('lat');
        $stationid = $req->request->get('stationid');
        $carid = $req->request->get('carid');



        return ["lng"=>$lng ,"lat"=>$lat,"stationid"=>$stationid,"carid"=>$carid];
    }


    /**
     * @Route("/show", methods="POST", name="auto_wap_rental_car2_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction(Request $req)
    {
        if (empty($this->getUser())) {

            return $this->redirect($this->generateUrl("auto_wap_index3"));
        }
        $station_id = $req->request->get('stationid');
        $back_sid = $req->request->get('backsid');
        $id = $req->request->get('carid');
        $lng = $req->request->get('lng');
        $lat = $req->request->get('lat');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $car=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
            ,$rentalCar);
        if(!isset($back_sid)){
            //默认使用最高金额优惠券
            $back_sid=$car["rentalStation"]["rentalStationID"];

        }

        $backStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($back_sid);

        return ['rentalCar'=>$car,"stationId"=>$station_id,"lng"=>$lng,"lat"=>$lat,"backStation"=>call_user_func($this->get('auto_manager.station_helper')
            ->get_station_normalizer(),
            $backStation)];
    }

    /**
     * @Route("/changeStation", methods="POST", name="auto_wap_rental_car2_change_station2",
     * requirements={"id"="\d+"})
     * @Template()
     */

    public function changeStationAction(Request $req)
    {
        $orderID=$req->request->get('orderID');
        $station_id = $req->request->get('stationid');
        $back_sid = $req->request->get('backSid');
        $rentalCarID = $req->request->get('rentalCarID');
        $lng = $req->request->get('lng');
        $lat = $req->request->get('lat');
        $backStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($back_sid);

        return ["stationid"=>$station_id,"backStation"=>call_user_func($this->get('auto_manager.station_helper')
        ->get_station_normalizer(),
        $backStation),"rentalCarID"=>$rentalCarID,"lng"=>$lng,"lat"=>$lat,"orderID"=>$orderID];
    }

    /**
     * @Route("/order", methods="POST", name="auto_wap_rental_car_order2")
     * @Template()
     */
    public function orderAction(Request $req)
    {

        $userID = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $returnStationID = $req->request->get('returnStationID');
       $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       // $baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_rental_car_order'),
            ['userID'=>$userID,"rentalCarID"=>$rentalCarID,"returnStationID"=>$returnStationID,"source"=>3]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return $this->redirect($this->generateUrl('auto_wap_rental_order_show2',
            ['id'=>$data['order']['orderID']]));

    }

}