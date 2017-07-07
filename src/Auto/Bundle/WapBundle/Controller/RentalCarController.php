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
 * @Route("/rentalCar")
 */
class RentalCarController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/list/{sid}/{page}", methods="GET", name="auto_wap_rental_car_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($sid,$page){

        /*
         * var $rentalStation = \Auto\Bundle\ManagerBundle\Entity\RentalStation
         */
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($sid);

        if(empty($rentalStation)){
            return $this->redirect($this->generateUrl('auto_wap_index'));
        }else{
            $start = ($page-1)*self::PER_PAGE;


            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:rentalCar')
                    ->createQueryBuilder('c')
            ;
            $station_cars =
                new Paginator(
                    $qb
                        ->select('c')
                        ->orderBy('c.id', 'DESC')
                        ->join('c.online','o')
                        ->where($qb->expr()->eq('c.rentalStation', ':station'))
                        ->andWhere($qb->expr()->eq('o.status', ':status'))
                        ->setParameter('station', $rentalStation)
                        ->setParameter('status', 1)
                        ->setMaxResults(self::PER_PAGE)
                        ->setFirstResult(self::PER_PAGE * ($page - 1))

                );


            $rental_cars = array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$station_cars->getIterator()->getArrayCopy());


            uasort($rental_cars,function($a,$b){

                if(($a['mileage'] == $b['mileage']) ){
                    return 0;
                }

                if ($a['mileage'] == $b['mileage']) return 0;
                return ($a['mileage'] < $b['mileage']) ? 1 : -1;
            });



            return [
                'rentalCars'  => $rental_cars,
                'page'      =>$page,
                'pageCount' =>ceil($station_cars->count() / self::PER_PAGE),
                'rentalStation'=>  call_user_func($this->get('auto_manager.station_helper')
                    ->get_station_normalizer(),
                    $rentalStation),
            ];
        }


    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_wap_rental_car_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction(Request $req,$id)
    {
        $station_id = $req->query->get('backStation');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
       $car=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
           ,$rentalCar);
        $has_order = $this->get('auto_manager.order_helper')->get_progress_rental_order($this->getUser());
       if(!isset($station_id)){
            //默认使用最高金额优惠券
            $station_id=$car["rentalStation"]["rentalStationID"];

        }
        $backStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($station_id);

        return ['rentalCar'=>$car,'has_order'=>$has_order,
            "backStation"=>call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer(),$backStation)];
    }
    
     /**
     * @Route("/cost/{id}", methods="GET", name="auto_wap_rental_car_cost",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function costAction($id)
    {
       
		 		$rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

     		$has_order = $this->get('auto_manager.order_helper')->get_progress_rental_order($this->getUser());

        return ['rentalCar'=>call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
            ,$rentalCar),'has_order'=>$has_order];
    
    }
    

    /**
     * @Route("/order", methods="POST", name="auto_wap_rental_car_order")
     * @Template()
     */
    public function orderAction(Request $req)
    {
        $userID = $req->request->get('userID');
        $rentalCarID = $req->request->get('rentalCarID');
        $returnStationID = $req->request->get('returnStationID');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_rental_car_order'),
            ['userID'=>$userID,"rentalCarID"=>$rentalCarID,"returnStationID"=>$returnStationID]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return $this->redirect($this->generateUrl('auto_wap_rental_order_show',
            ['id'=>$data['order']['orderID']]));

    }

    /**
     * @Route("/changeStation/{id}", methods="GET", name="auto_wap_rental_car_change_station",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function changeStationAction(Request $req,$id)
    {
        $order=$req->query->get('order');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $car=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
            ,$rentalCar);
        $backStationId = $req->query->get('backStation');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       // $baseUrl='http://lecarshare.com';

            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                ('auto_api_station_back_list'),
                ['rentalStationID'=>$backStationId,"pickUpRentalStationID"=>$car["rentalStation"]["rentalStationID"]]);
            $data = json_decode($post_json,true);
            if($data['errorCode']!=0){
                return $this->render(
                    "AutoWapBundle:Default:message.html.twig",
                    ['message' => $data['errorMessage']]);
            }

        return ['rentalCar'=>$car,"checkedId"=>$backStationId,
            "backStations"=>$data["stations"],"order"=>$order
        ];
    }


    /**
     * @Route("/changeStationSearchByName", methods="POST", name="auto_wap_rental_car_change_station_searchByName")
     * @Template("AutoWapBundle:RentalCar:changeStation.html.twig")
     */
    public function searchByNameStationAction(Request $req)
    {
        $id=$req->request->get('rentalCarID');
        $name=$req->request->get('searchByName');
        $order=$req->request->get('order');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $car=call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
            ,$rentalCar);
        $backStationId = $req->request->get('checkedId');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       // $baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_station_searchByName'),
            ['name'=>$name]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ['rentalCar'=>$car,"checkedId"=>$backStationId,
            "backStations"=>$data["stations"],"order"=>$order
        ];
    }
}