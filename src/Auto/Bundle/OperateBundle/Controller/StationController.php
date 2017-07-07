<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/10/26
 * Time: 上午10:29
 */

namespace Auto\Bundle\OperateBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/station")
 */

class StationController extends Controller{

    /**
     * @Route("/list", methods="GET", name="auto_operate_station_list")
     * @Template()
     */
    public function listAction(Request $req)
    {
        $rentalCar = $req->query->get('rentalCar');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_station'),
            ['userID'=>$this->getUser()->getToken()]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['rental_stations'=>$data['stations'],'rentalCar'=>$rentalCar];

    }




    /**
     * @Route("/overload/list", methods="GET", name="auto_operate_overload_station_list")
     * @Template()
     */
    public function overloadListAction(){

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_overload_rental_station'),
            ['userID'=>$this->getUser()->getToken()]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ['rental_stations'=>$data['stations']];

    }

    /**
     * @Route("/empty/list", methods="GET", name="auto_operate_empty_station_list")
     * @Template()
     */
    public function emptyListAction(){
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_empty_rental_station'),
            ['userID'=>$this->getUser()->getToken()]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['rental_stations'=>$data['stations']];

    }
}