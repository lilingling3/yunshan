<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/station")
 */
class StationController extends Controller{
	 /**
     * @Route("", methods="GET", name="auto_wap_station_list")
     *
     * @Template()
     */
    public function listAction(){

    	$stations =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findBy(['online'=>1]);
        $stations_normalizer=array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),$stations);
    //    var_dump($stations_normalizer);
        $count_json = count($stations_normalizer);
     //   echo '<p>length'.$count_json.'</p>';
    	 return [

             'stations'=>array_map($this->get('auto_manager.station_helper')->get_station_normalizer()
             ,$stations)
        ];
    }

    /**
     * @Route("/map/{id}", methods="GET", name="auto_wap_station_map",
     * requirements={"id"="\d+"})
     *
     * @Template()
     */

    public function mapAction($id){
        $station =
            $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);
        //var_dump(call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer(),$station));
        return [
            'station'=>call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer()
                ,$station)
        ];
    }
}