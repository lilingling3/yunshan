<?php

namespace Auto\Bundle\Api2Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/station")
 */
class StationController extends BaseController
{
    const PER_PAGE = 10;
    /**
     * @Route("/cluster/list", methods="POST" ,name="auto_api2_station_cluster_list")
     */
    public function clusterListAction(Request $req)
    {
        $max_lat = $req->request->get('maxlat');
        $min_lat = $req->request->get('minlat');
        $max_lng = $req->request->get('maxlng');
        $min_lng = $req->request->get('minlng');
        $zoom = $req->request->get('zoom');
        $back_type = $req->request->get('backType');
        $pick_up_id = $req->request->get('pickUpRentalStationID');
        $city = $req->request->get('city');
        $stations = $this->get('auto_manager.station_helper')->getStationByLatlng($max_lat,$min_lat,$max_lng,
            $min_lng,$back_type);

        if(empty($stations)){
            if(empty($city)){
                $s = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalStation')
                    ->findOneBy(['id'=>21]);
                $rental_stations=[];
                $rental_stations[]= call_user_func($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $s);
                return new JsonResponse([
                    'errorCode' => self::E_OK,
                    'stations' => $rental_stations,
                    'zoom' => 0
                ]);

            }
            $city = mb_substr($city,mb_strlen($city,'utf-8')-1,1,'utf-8')=="市"? $city : $city."市";
            $area = $this->get('auto_manager.area_helper')->getArea($city,null);
            if(empty($area)){
                return new JsonResponse([
                    'errorCode' =>  self::E_NO_STATION,
                    "errorMessage" =>"您当前的城市暂无租赁车点，是否切换城市享受服务"
                ]);
            }

            $araStations = $this->get('auto_manager.station_helper')->getUseableStationByArea($area);
            if(empty($araStations)){

                return new JsonResponse([
                    'errorCode' =>  self::E_NO_STATION,
                    'errorMessage' => "您当前的城市暂无租赁车点，是否切换城市享受服务"
                ]);
            }

        }
        $rental_stations=[];
        $districtIds=[];
        $district=null;
        $district2=null;

        if($zoom==3 || $zoom==2 || $zoom==1){
            foreach($stations as $value){
                if($zoom==1){
                    $districtEntity=$value->getBusinessDistrict();
                }
                else if($zoom==2){
                    $districtEntity=$value->getArea();
                }
                else {
                    $districtEntity=$value->getArea()->getParent();
                }

                if(empty($districtEntity)){
                    continue;
                }

                if(! in_array($districtEntity->getId(),$districtIds)){
                    $districtIds[]=$districtEntity->getId();
                    $rental_stations[$districtEntity->getId()]=[
                        "rentalStationID"=>$districtEntity->getId(),
                        "name"=>$districtEntity->getName(),
                        "latitude"=>$districtEntity->getLatitude(),
                        "longitude"=>$districtEntity->getLongitude(),
                        "amount"=>0
                    ];
                }


                $data=$this->get('auto_manager.station_helper')->get_rental_car_status_count($value);

                $rental_stations[$districtEntity->getId()]["amount"]+=$data["usable"];

            }
            $rental_stations=array_values($rental_stations);
        }
        else{
            $rental_stations = array_map($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $stations);
            if($pick_up_id){

                foreach($rental_stations as $key=>$station){

                    if($station['rentalStationID']==$pick_up_id){
                        $rental_stations[$key]['parkingSpaceCount']+=1;
                        break;
                    }

                }
            }
        }
        if(empty($rental_stations)){
            $s = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findOneBy(['id'=>21]);
            $rental_stations[]= call_user_func($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $s);
            return new JsonResponse([
                'errorCode' => self::E_OK,
                'stations' => $rental_stations,
                'zoom' => 0
            ]);

        }

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'stations' => $rental_stations,
            'zoom' => $zoom
        ]);


    }
    /**
     * @Route("/list", methods="POST")
     */
    public function indexAction(Request $req)
    {

        $max_lat = $req->request->get('maxlat');
        $min_lat = $req->request->get('minlat');
        $max_lng = $req->request->get('maxlng');
        $min_lng = $req->request->get('minlng');
        $back_type = $req->request->get('backType');
        $pick_up_id = $req->request->get('pickUpRentalStationID');



        $stations = $this->get('auto_manager.station_helper')->getStationByLatlng($max_lat,$min_lat,$max_lng,
            $min_lng,$back_type);



        if(empty($stations)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_STATION,
            ]);

        }

        $rental_stations = array_map($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $stations)
        ;

        if($pick_up_id){

            foreach($rental_stations as $key=>$station){

                if($station['rentalStationID']==$pick_up_id){
                    $rental_stations[$key]['parkingSpaceCount']+=1;
                    break;
                }

            }
        }

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'      => $rental_stations]);
    }


    /**
     * @Route("/getLatlngImage", methods="POST")
     */

    public function getLatlngImageAction(Request $req)
    {
        $sid = $req->request->get('stationID');
        $rental_station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($sid);

        if(empty($rental_station)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_STATION,
                'errorMessage' =>  self::M_NO_STATION,
            ]);

        }
        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'rentalStation' => call_user_func($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $rental_station)
        ]);
    }


    /**
     * @Route("/hot", methods="POST")
     */
    public function hotAction(Request $req)
    {
        /**
         * @var $repos \Doctrine\ORM\EntityManager
         */
        $repos = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Station');


        $cityName = $req->request->get('cityName');

        $qb = $repos->createQueryBuilder('s')
            ->select('s')
            ->join('s.area','a')
            ->join('a.parent','p')
        ;

        $stations = $qb->where($qb->expr()->orX(
            $qb->expr()->like('p.name', ':name'),
            $qb->expr()->like('a.name', ':name')
        ))
            ->andWhere($qb->expr()->eq('s.online', ':online'))
            ->setParameter('online',1)
            ->setParameter('name', '%'.$cityName.'%')
            ->getQuery()
            ->execute();

        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'=>array_map($this->get('auto_manager.station_helper')->get_station_data_normalizer(),$stations),
        ]);
    }


    /**
     * @Route("/recommend", methods="POST")
     */
    public function recommendAction(Request $req)
    {
        $name = $req->request->get('name');
        $address = $req->request->get('address');
        $latitude = $req->request->get('latitude');
        $longitude = $req->request->get('longitude');
        $reason = $req->request->get('reason');


        $station = new \Auto\Bundle\ManagerBundle\Entity\RecommendStation();
        $station
            ->setAddress($address)
            ->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setName($name)
            ->setReason($reason)
        ;

        if($this->getUser()){

            $station ->setMember($this->getUser());


        }

        $man = $this->getDoctrine()->getManager();
        $man->persist($station);
        $man->flush();

        return new JsonResponse([
            'errorCode'     =>  self::E_OK
        ]);


    }
}